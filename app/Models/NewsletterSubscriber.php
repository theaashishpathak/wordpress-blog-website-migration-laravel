<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Newsletter subscriber — captured via the public signup widget.
 *
 * Lifecycle:
 *   pending      → just created, awaiting double-opt-in click
 *   confirmed    → opt-in confirmed, eligible for campaigns
 *   unsubscribed → opt-out (one-click via unsubscribe token)
 *   bounced      → hard bounce reported by mail provider (manual flag for now)
 *   complained   → marked as spam by recipient (manual flag for now)
 */
class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    public const STATUS_BOUNCED = 'bounced';

    public const STATUS_COMPLAINED = 'complained';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_UNSUBSCRIBED,
        self::STATUS_BOUNCED,
        self::STATUS_COMPLAINED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'email',
        'name',
        'status',
        'confirmed_at',
        'unsubscribed_at',
        'confirmation_token',
        'unsubscribe_token',
        'source',
        'language_id',
        'ip_address',
        'user_agent',
        'tags',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'tags' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * @return BelongsTo<Language, NewsletterSubscriber>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeUnsubscribed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNSUBSCRIBED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Reachable, ready-for-campaign subscribers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === self::STATUS_UNSUBSCRIBED;
    }
}
