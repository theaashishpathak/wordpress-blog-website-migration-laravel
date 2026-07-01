<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AdZoneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AdZone — a named placement slot in the frontend (e.g.
 * "homepage_top", "sidebar_300x250").
 *
 * The blade template references zones by their `key`, not by id, so
 * an admin can swap creatives without redeploying the theme.
 */
class AdZone extends Model
{
    /** @use HasFactory<AdZoneFactory> */
    use HasFactory;

    public const POSITION_TOP = 'top';

    public const POSITION_SIDEBAR = 'sidebar';

    public const POSITION_INLINE = 'inline';

    public const POSITION_FOOTER = 'footer';

    public const POSITION_POPUP = 'popup';

    /** @var list<string> */
    public const POSITIONS = [
        self::POSITION_TOP,
        self::POSITION_SIDEBAR,
        self::POSITION_INLINE,
        self::POSITION_FOOTER,
        self::POSITION_POPUP,
    ];

    /** @var list<string> */
    protected $fillable = [
        'key',
        'name',
        'description',
        'width',
        'height',
        'position',
        'is_active',
        'max_creatives',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
            'max_creatives' => 'integer',
        ];
    }

    /**
     * @return HasMany<AdCreative>
     */
    public function creatives(): HasMany
    {
        return $this->hasMany(AdCreative::class, 'zone_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }
}
