<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasContextualActivityLog;
use App\Concerns\HasTranslations;
use App\Contracts\Translatable;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

/**
 * Category — taxonomy bucket for posts and news.
 *
 * Non-translatable structural fields live on this model:
 *   parent_id, image_id, icon, color, show_in_menu, show_on_homepage,
 *   is_featured, sort_order, layout
 *
 * Per-locale name/slug/description/meta lives on CategoryTranslation.
 * Access via `$category->translate('name')` or `$category->translation()`.
 *
 * Authoritative spec: docs/Multilanguage Schema.txt Section 5.
 */
class Category extends Model implements Translatable
{
    /** @use HasFactory<CategoryFactory> */
    use HasContextualActivityLog, HasFactory, HasTranslations, SoftDeletes;

    public function activityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'parent_id', 'image_id', 'icon', 'color',
                'show_in_menu', 'show_on_homepage', 'is_featured',
                'sort_order', 'layout',
            ])
            ->logOnlyDirty()
            ->useLogName('category')
            ->setDescriptionForEvent(fn (string $event): string => "Category {$event}")
            ->dontSubmitEmptyLogs();
    }

    public const LAYOUT_GRID = 'grid';

    public const LAYOUT_LIST = 'list';

    public const LAYOUT_MAGAZINE = 'magazine';

    public const LAYOUT_SIDEBAR = 'sidebar';

    public const LAYOUT_FULL = 'full';

    /** @var list<string> */
    public const LAYOUTS = [
        self::LAYOUT_GRID,
        self::LAYOUT_LIST,
        self::LAYOUT_MAGAZINE,
        self::LAYOUT_SIDEBAR,
        self::LAYOUT_FULL,
    ];

    /** @var list<string> */
    protected $fillable = [
        'parent_id',
        'image_id',
        'icon',
        'color',
        'show_in_menu',
        'show_on_homepage',
        'is_featured',
        'sort_order',
        'layout',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_in_menu' => 'boolean',
            'show_on_homepage' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function translationModel(): string
    {
        return CategoryTranslation::class;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * @return BelongsTo<Category, Category>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeOnHomepage(Builder $query): Builder
    {
        return $query->where('show_on_homepage', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeByParent(Builder $query, ?int $parentId): Builder
    {
        return $parentId === null
            ? $query->whereNull('parent_id')
            : $query->where('parent_id', $parentId);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Count direct children only (cheaper than full descendant tree).
     */
    public function childrenCount(): int
    {
        return $this->children()->count();
    }

    /**
     * Build the public URL for this category in a given locale.
     *
     * Falls back to the default-language slug when no translation
     * exists for the requested locale.
     */
    public function urlFor(?string $locale = null): string
    {
        $slug = (string) ($this->translate('slug', $locale) ?? '');

        if ($slug === '') {
            return '/';
        }

        $localePrefix = $locale !== null ? "/{$locale}" : '';

        return "{$localePrefix}/{$slug}";
    }
}
