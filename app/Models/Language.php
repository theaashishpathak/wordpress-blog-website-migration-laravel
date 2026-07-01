<?php

namespace App\Models;

use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Language model — drives multi-language URL routing, slug uniqueness,
 * RTL detection, and AI translation workflow.
 *
 * Authoritative schema spec: docs/Multilanguage Schema.txt
 */
class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory;

    public const DIRECTION_LTR = 'ltr';

    public const DIRECTION_RTL = 'rtl';

    /** @var list<string> */
    public const DIRECTIONS = [
        self::DIRECTION_LTR,
        self::DIRECTION_RTL,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag_emoji',
        'flag_icon',
        'direction',
        'is_default',
        'is_active',
        'is_admin_locale',
        'sort_order',
        'locale_php',
        'date_format',
        'number_format',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_admin_locale' => 'boolean',
            'sort_order' => 'integer',
            'date_format' => 'array',
            'number_format' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAdminLocale(Builder $query): Builder
    {
        return $query->where('is_admin_locale', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isRtl(): bool
    {
        return $this->direction === self::DIRECTION_RTL;
    }

    public function isLtr(): bool
    {
        return $this->direction === self::DIRECTION_LTR;
    }

    /**
     * Return a URL for the flag icon, falling back to the emoji when no image is uploaded.
     */
    public function getFlagUrl(): ?string
    {
        if ($this->flag_icon === null || $this->flag_icon === '') {
            return null;
        }

        if (Str::startsWith($this->flag_icon, ['http://', 'https://', '//', '/'])) {
            return $this->flag_icon;
        }

        return Storage::disk('public')->url($this->flag_icon);
    }
}
