<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AIUsageLog;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\TokenUsage;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Append-only writer + aggregator for ai_usage_logs.
 *
 * AIManager invokes record() / recordStreamed() / recordFailure() around
 * every provider call. Aggregation helpers feed the admin AI cost
 * dashboard and AIManager's per-user cost-ceiling guard.
 */
class AIUsageTracker
{
    /**
     * Record a successful synchronous completion.
     */
    public function record(
        CompletionRequest $request,
        CompletionResponse $response,
        ?int $durationMs = null,
    ): AIUsageLog {
        return AIUsageLog::query()->create([
            'user_id' => $request->userId,
            'provider' => $response->providerName,
            'model' => $response->model,
            'feature_key' => $request->featureKey ?? 'unknown',
            'prompt_template_key' => $request->promptTemplateKey,
            'prompt_template_version' => $this->resolveTemplateVersion($request),
            'prompt_tokens' => $response->usage->promptTokens,
            'completion_tokens' => $response->usage->completionTokens,
            'total_tokens' => $response->usage->totalTokens,
            'estimated_cost_usd' => $response->usage->estimatedCostUsd,
            'duration_ms' => $durationMs,
            'status' => AIUsageLog::STATUS_SUCCESS,
            'request_metadata' => $this->buildRequestMetadata($request, $response->finishReason),
        ]);
    }

    /**
     * Record a successful streamed completion (final usage tallied after the stream closes).
     */
    public function recordStreamed(
        CompletionRequest $request,
        string $providerName,
        string $accumulatedContent,
        TokenUsage $usage,
        ?int $durationMs = null,
    ): AIUsageLog {
        return AIUsageLog::query()->create([
            'user_id' => $request->userId,
            'provider' => $providerName,
            'model' => $request->model,
            'feature_key' => $request->featureKey ?? 'unknown',
            'prompt_template_key' => $request->promptTemplateKey,
            'prompt_template_version' => $this->resolveTemplateVersion($request),
            'prompt_tokens' => $usage->promptTokens,
            'completion_tokens' => $usage->completionTokens,
            'total_tokens' => $usage->totalTokens,
            'estimated_cost_usd' => $usage->estimatedCostUsd,
            'duration_ms' => $durationMs,
            'status' => AIUsageLog::STATUS_SUCCESS,
            'request_metadata' => array_merge(
                $this->buildRequestMetadata($request, null),
                ['streamed' => true, 'content_length' => mb_strlen($accumulatedContent)],
            ),
        ]);
    }

    /**
     * Record a failed call (any status other than success).
     */
    public function recordFailure(
        CompletionRequest $request,
        string $providerName,
        string $status,
        Throwable $exception,
        ?int $durationMs = null,
    ): AIUsageLog {
        return AIUsageLog::query()->create([
            'user_id' => $request->userId,
            'provider' => $providerName,
            'model' => $request->model,
            'feature_key' => $request->featureKey ?? 'unknown',
            'prompt_template_key' => $request->promptTemplateKey,
            'prompt_template_version' => $this->resolveTemplateVersion($request),
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'estimated_cost_usd' => 0,
            'duration_ms' => $durationMs,
            'status' => $status,
            'error_message' => mb_substr($exception->getMessage(), 0, 1000),
            'request_metadata' => $this->buildRequestMetadata($request, null),
        ]);
    }

    // -------------------------------------------------------------------------
    // Aggregations
    // -------------------------------------------------------------------------

    public function monthlyCostForUser(int $userId): float
    {
        return (float) AIUsageLog::query()
            ->forUser($userId)
            ->thisMonth()
            ->sum('estimated_cost_usd');
    }

    public function monthlyTokensForUser(int $userId): int
    {
        return (int) AIUsageLog::query()
            ->forUser($userId)
            ->thisMonth()
            ->sum('total_tokens');
    }

    public function monthlyCostForProvider(string $providerName): float
    {
        return (float) AIUsageLog::query()
            ->forProvider($providerName)
            ->thisMonth()
            ->sum('estimated_cost_usd');
    }

    public function monthlyPlatformCost(): float
    {
        return (float) AIUsageLog::query()
            ->thisMonth()
            ->sum('estimated_cost_usd');
    }

    /**
     * @return array<string, array{count:int, tokens:int, cost:float}>
     */
    public function featureBreakdown(?int $userId = null): array
    {
        $query = AIUsageLog::query()->thisMonth()->successful();

        if ($userId !== null) {
            $query->forUser($userId);
        }

        return $query
            ->selectRaw('feature_key, COUNT(*) as call_count, SUM(total_tokens) as tokens, SUM(estimated_cost_usd) as cost')
            ->groupBy('feature_key')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                (string) $row->feature_key => [
                    'count' => (int) $row->call_count,
                    'tokens' => (int) $row->tokens,
                    'cost' => (float) $row->cost,
                ],
            ])
            ->all();
    }

    /**
     * @return Collection<int, object{user_id:int, cost:float, total_tokens:int}>
     */
    public function topConsumers(int $limit = 10): Collection
    {
        return AIUsageLog::query()
            ->successful()
            ->thisMonth()
            ->whereNotNull('user_id')
            ->selectRaw('user_id, SUM(estimated_cost_usd) as cost, SUM(total_tokens) as total_tokens')
            ->groupBy('user_id')
            ->orderByDesc('cost')
            ->limit($limit)
            ->get();
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function resolveTemplateVersion(CompletionRequest $request): ?int
    {
        return isset($request->metadata['template_version'])
            ? (int) $request->metadata['template_version']
            : null;
    }

    /**
     * Build the redacted metadata blob stored on each ai_usage_logs row.
     *
     * Merges the request's own metadata (which may include model
     * substitution audit fields like `model_substituted_from`,
     * `streamed`, etc.) with the canonical fixed fields. Canonical fields
     * take precedence so callers can't accidentally overwrite them.
     *
     * @return array<string, mixed>
     */
    private function buildRequestMetadata(CompletionRequest $request, ?string $finishReason): array
    {
        $canonical = [
            'model' => $request->model,
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxTokens,
            'feature_key' => $request->featureKey,
            'prompt_template_key' => $request->promptTemplateKey,
            'prompt_template_version' => $this->resolveTemplateVersion($request),
            'finish_reason' => $finishReason,
        ];

        // Caller-supplied metadata first, then canonical fields override.
        return $request->metadata + $canonical;
    }
}
