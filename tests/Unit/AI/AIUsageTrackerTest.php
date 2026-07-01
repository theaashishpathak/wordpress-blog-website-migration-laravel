<?php

declare(strict_types=1);

use App\Models\AIUsageLog;
use App\Models\User;
use App\Services\AI\AIUsageTracker;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\TokenUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function trackerSampleRequest(int $userId, string $featureKey = 'article_writer'): CompletionRequest
{
    return new CompletionRequest(
        model: 'gpt-4o-mini',
        systemPrompt: 'sys',
        userPrompt: 'usr',
        featureKey: $featureKey,
        userId: $userId,
        metadata: ['template_version' => 3],
    );
}

function trackerSampleResponse(float $cost = 0.0123, int $totalTokens = 1500): CompletionResponse
{
    return new CompletionResponse(
        content: 'Generated content here',
        usage: new TokenUsage(
            promptTokens: 500,
            completionTokens: 1000,
            totalTokens: $totalTokens,
            estimatedCostUsd: $cost,
        ),
        providerName: 'openai',
        model: 'gpt-4o-mini',
        finishReason: 'stop',
    );
}

test('record() writes a success row to ai_usage_logs', function (): void {
    $user = User::factory()->create();
    $tracker = new AIUsageTracker;

    $log = $tracker->record(
        request: trackerSampleRequest($user->id),
        response: trackerSampleResponse(),
        durationMs: 1234,
    );

    expect($log)->toBeInstanceOf(AIUsageLog::class);
    expect($log->status)->toBe(AIUsageLog::STATUS_SUCCESS);
    expect($log->user_id)->toBe($user->id);
    expect($log->provider)->toBe('openai');
    expect($log->model)->toBe('gpt-4o-mini');
    expect($log->feature_key)->toBe('article_writer');
    expect($log->total_tokens)->toBe(1500);
    expect((float) $log->estimated_cost_usd)->toBe(0.0123);
    expect($log->duration_ms)->toBe(1234);
    expect($log->prompt_template_version)->toBe(3);
});

test('recordFailure() writes a failed row with redacted error', function (): void {
    $user = User::factory()->create();
    $tracker = new AIUsageTracker;

    $log = $tracker->recordFailure(
        request: trackerSampleRequest($user->id),
        providerName: 'gemini',
        status: AIUsageLog::STATUS_RATE_LIMITED,
        exception: new RuntimeException('Rate limit exceeded for organization-xyz'),
        durationMs: 200,
    );

    expect($log->status)->toBe(AIUsageLog::STATUS_RATE_LIMITED);
    expect($log->provider)->toBe('gemini');
    expect($log->error_message)->toContain('Rate limit');
    expect($log->total_tokens)->toBe(0);
    expect((float) $log->estimated_cost_usd)->toBe(0.0);
});

test('monthlyCostForUser sums only current-month successful rows', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    AIUsageLog::factory()->create(['user_id' => $user->id, 'estimated_cost_usd' => 1.50]);
    AIUsageLog::factory()->create(['user_id' => $user->id, 'estimated_cost_usd' => 2.25]);
    AIUsageLog::factory()->create(['user_id' => $other->id, 'estimated_cost_usd' => 9.99]);

    // Pretend an old row from last month — should NOT be included.
    AIUsageLog::factory()->create([
        'user_id' => $user->id,
        'estimated_cost_usd' => 100.00,
        'created_at' => now()->subMonths(2),
        'updated_at' => now()->subMonths(2),
    ]);

    $tracker = new AIUsageTracker;

    expect($tracker->monthlyCostForUser($user->id))->toBe(3.75);
    expect($tracker->monthlyCostForUser($other->id))->toBe(9.99);
});

test('featureBreakdown groups counts and costs by feature_key', function (): void {
    $user = User::factory()->create();

    AIUsageLog::factory()->feature('article_writer')->create(['user_id' => $user->id, 'estimated_cost_usd' => 0.5, 'total_tokens' => 1000]);
    AIUsageLog::factory()->feature('article_writer')->create(['user_id' => $user->id, 'estimated_cost_usd' => 0.5, 'total_tokens' => 1000]);
    AIUsageLog::factory()->feature('seo_meta')->create(['user_id' => $user->id, 'estimated_cost_usd' => 0.05, 'total_tokens' => 100]);

    $tracker = new AIUsageTracker;
    $breakdown = $tracker->featureBreakdown($user->id);

    expect($breakdown)->toHaveKey('article_writer');
    expect($breakdown)->toHaveKey('seo_meta');
    expect($breakdown['article_writer']['count'])->toBe(2);
    expect($breakdown['article_writer']['tokens'])->toBe(2000);
    expect($breakdown['article_writer']['cost'])->toBe(1.0);
    expect($breakdown['seo_meta']['count'])->toBe(1);
});

test('topConsumers returns users ordered by descending cost', function (): void {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $c = User::factory()->create();

    AIUsageLog::factory()->count(3)->create(['user_id' => $a->id, 'estimated_cost_usd' => 1.0]);
    AIUsageLog::factory()->create(['user_id' => $b->id, 'estimated_cost_usd' => 10.0]);
    AIUsageLog::factory()->create(['user_id' => $c->id, 'estimated_cost_usd' => 0.5]);

    $tracker = new AIUsageTracker;
    $top = $tracker->topConsumers(3);

    expect($top)->toHaveCount(3);
    expect((int) $top->first()->user_id)->toBe($b->id);
    expect((int) $top->last()->user_id)->toBe($c->id);
});
