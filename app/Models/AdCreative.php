<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AdCreativeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AdCreative — the actual ad content shown in an AdZone slot.
 *
 * Three render modes (`type`):
 *   image     — render <a><img></a> with Media.url + target_url
 *   html      — render raw html_code (used for AdSense, Mediavine,
 *               OpenAd, custom <script> blocks). Trusted source only.
 *   sponsored — render a styled "Sponsored" card with media + target_url
 *
 * `priority` controls weight inside a zone's max_creatives rotation
 * (lower number = shown more). status = active + within start_at/end_at
 * window = eligible for serving.
 */
class AdCreative extends Model
{
    /** @use HasFactory<AdCreativeFactory> */
    use HasFactory, SoftDeletes;

    public const TYPE_IMAGE = 'image';

    public const TYPE_HTML = 'html';

    public const TYPE_SPONSORED = 'sponsored';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_IMAGE,
        self::TYPE_HTML,
        self::TYPE_SPONSORED,
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_EXPIRED = 'expired';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_EXPIRED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'zone_id',
        'name',
        'type',
        'media_id',
        'target_url',
        'alt_text',
        'html_code',
        'status',
        'start_at',
        'end_at',
        'priority',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'priority' => 'integer',
            'impression_count' => 'integer',
            'click_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AdZone, AdCreative>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(AdZone::class, 'zone_id');
    }

    /**
     * @return BelongsTo<Media, AdCreative>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * Active creatives currently within their scheduling window.
     */
    public function scopeServable(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($now): void {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now): void {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            });
    }

    public function ctrPercent(): float
    {
        if ($this->impression_count <= 0) {
            return 0.0;
        }

        return round(($this->click_count / $this->impression_count) * 100, 2);
    }
}
