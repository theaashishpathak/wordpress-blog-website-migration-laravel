<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'location',
        'parent_id',
        'title',
        'url',
        'type',
        'target',
        'icon',
        'order',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * Parent item for dropdown menus.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Submenu / child dropdown items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    /**
     * Active items only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Top-level items (not child dropdown items).
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Items for a specific location (default: header).
     */
    public function scopeForLocation(Builder $query, string $location = 'header'): Builder
    {
        return $query->where('location', $location);
    }

    /**
     * Ordered items.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('id');
    }

    /**
     * Whether this menu item has children (is a dropdown).
     */
    public function isDropdown(): bool
    {
        return $this->children->isNotEmpty();
    }

    /**
     * Seed sensible default header navigation items.
     */
    public static function seedDefaults(): void
    {
        if (static::count() > 0) {
            return;
        }

        static::create([
            'title' => 'Home',
            'url' => '/',
            'type' => 'route',
            'target' => '_self',
            'order' => 1,
            'is_active' => true,
            'location' => 'header',
        ]);

        $catDropdown = static::create([
            'title' => 'Categories',
            'url' => '#',
            'type' => 'custom',
            'target' => '_self',
            'order' => 2,
            'is_active' => true,
            'location' => 'header',
        ]);

        $topCategories = Category::query()
            ->whereHas('posts', fn ($q) => $q->where('status', \App\Enums\PostStatus::Published->value))
            ->withCount(['posts' => fn ($q) => $q->where('status', \App\Enums\PostStatus::Published->value)])
            ->orderByDesc('posts_count')
            ->take(6)
            ->get();

        $subOrder = 1;
        foreach ($topCategories as $cat) {
            $slug = $cat->translate('slug');
            if ($slug) {
                static::create([
                    'parent_id' => $catDropdown->id,
                    'title' => html_entity_decode((string) ($cat->translate('name') ?? ('#' . $cat->id)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'url' => '/category/' . $slug,
                    'type' => 'category',
                    'icon' => $cat->icon,
                    'order' => $subOrder++,
                    'is_active' => true,
                    'location' => 'header',
                ]);
            }
        }

        static::create([
            'title' => 'About',
            'url' => '/page/about-us',
            'type' => 'page',
            'target' => '_self',
            'order' => 3,
            'is_active' => true,
            'location' => 'header',
        ]);

        static::create([
            'title' => 'Contacts',
            'url' => '/page/contact-us',
            'type' => 'page',
            'target' => '_self',
            'order' => 4,
            'is_active' => true,
            'location' => 'header',
        ]);
    }
}
