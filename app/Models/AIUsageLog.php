<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AIUsageLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only record of every AI provider call.
 *
 * Written by App\Services\AI\AIUsageTracker. Never mutated after insert
 * (admin can prune via scheduled command but never edit individual rows).
 */
class AIUsageLog extends Model
{
    /** @use HasFactory<AIUsageLogFactory> */
    use HasFactory;

    /**
     * Laravel's default snake_case converter renders `AIUsageLog` as
     * `a_i_usage_logs`. The migration uses the cleaner `ai_usage_logs`,
     * so we pin the table name explicitly.
     */
    protected $table = 'ai_usage_logs';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_FILTERED = 'filtered';

    public const STATUS_RATE_LIMITED = 'rate_limited';

    public const STATUS_QUOTA_EXCEEDED = 'quota_exceeded';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
        self::STATUS_FILTERED,
        self::STATUS_RATE_LIMITED,
        self::STATUS_QUOTA_EXCEEDED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'feature_key',
        'prompt_template_key',
        'prompt_template_version',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost_usd',
        'duration_ms',
        'status',
        'error_message',
        'request_metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'estimated_cost_usd' => 'decimal:6',
            'duration_ms' => 'integer',
            'prompt_template_version' => 'integer',
            'request_metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForProvider(Builder $query, string $providerName): Builder
    {
        return $query->where('provider', $providerName);
    }

    public function scopeForFeature(Builder $query, string $featureKey): Builder
    {
        return $query->where('feature_key', $featureKey);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }
}
