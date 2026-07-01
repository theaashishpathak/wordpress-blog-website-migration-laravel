<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasContextualActivityLog;
use App\Concerns\HasTranslations;
use App\Contracts\Translatable;
use App\Enums\PostStatus;
use App\Enums\PostType;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

/**
 * Post — single table for every content format the CMS supports.
 *
 * The `type` discriminator (post / news / page_article / video / gallery / short)
 * decides which Blade template and admin sidebar bucket the row renders in;
 * the schema otherwise is uniform.
 *
 * Per-locale fields (title, slug, content, inline SEO) live on PostTranslation.
 * Advanced SEO overrides (schema, twitter cards) live on the polymorphic
 * seo_metas table accessed via `$post->seoMetas()`.
 *
 * Authoritative spec: docs/Multilanguage Schema.txt Section 3.
 */
class Post extends Model implements Translatable
{
    /** @use HasFactory<PostFactory> */
    use HasContextualActivityLog, HasFactory, HasTranslations, SoftDeletes;

    /**
     * Activity log configuration — captures post lifecycle in the
     * admin audit trail (Activity Logs page). Only meaningful columns
     * are tracked so noise from auto-updated counters stays out.
     */
    public function activityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'type', 'status', 'visibility', 'category_id', 'author_id',
                'is_featured', 'is_breaking', 'is_trending', 'is_editors_pick',
                'is_sponsored', 'is_premium', 'allow_comments',
                'published_at', 'scheduled_at',
            ])
            ->logOnlyDirty()
            ->useLogName('post')
            ->setDescriptionForEvent(fn (string $event): string => "Post {$event}")
            ->dontSubmitEmptyLogs();
    }

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_PASSWORD_PROTECTED = 'password_protected';

    public const VISIBILITY_PREMIUM = 'premium';

    /** @var list<string> */
    public const VISIBILITIES = [
        self::VISIBILITY_PUBLIC,
        self::VISIBILITY_PRIVATE,
        self::VISIBILITY_PASSWORD_PROTECTED,
        self::VISIBILITY_PREMIUM,
    ];

    /** @var list<string> */
    protected $fillable = [
        'type',
        'category_id',
        'subcategory_id',
        'author_id',
        'default_language_id',
        'status',
        'visibility',
        'is_featured',
        'is_breaking',
        'is_trending',
        'is_editors_pick',
        'is_sponsored',
        'is_premium',
        'allow_comments',
        'published_at',
        'scheduled_at',
        'breaking_expires_at',
        'view_count',
        'like_count',
        'share_count',
        'comment_count',
        'featured_image_id',
        'source_name',
        'source_url',
        'rss_source_id',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'status' => PostStatus::class,
            'is_featured' => 'boolean',
            'is_breaking' => 'boolean',
            'is_trending' => 'boolean',
            'is_editors_pick' => 'boolean',
            'is_sponsored' => 'boolean',
            'is_premium' => 'boolean',
            'allow_comments' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'breaking_expires_at' => 'datetime',
            'view_count' => 'integer',
            'like_count' => 'integer',
            'share_count' => 'integer',
            'comment_count' => 'integer',
        ];
    }

    public static function translationModel(): string
    {
        return PostTranslation::class;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * @return BelongsTo<Category, Post>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Category, Post>
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /**
     * @return BelongsTo<User, Post>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<Language, Post>
     */
    public function defaultLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'default_language_id');
    }

    /**
     * @return BelongsTo<Media, Post>
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    /**
     * @return BelongsToMany<Tag>
     */
    public function tags(): BelongsToMany
    {
        // post_tag pivot tracks attach time only (no updated_at column —
        // re-tagging is a detach+attach, not an update).
        return $this->belongsToMany(Tag::class, 'post_tag')
            ->withPivot('created_at');
    }

    /**
     * @return MorphMany<SeoMeta>
     */
    public function seoMetas(): MorphMany
    {
        return $this->morphMany(SeoMeta::class, 'seoable');
    }

    /**
     * @return HasMany<EditorialNote>
     */
    public function editorialNotes(): HasMany
    {
        return $this->hasMany(EditorialNote::class)->latest();
    }

    /**
     * @return HasMany<PostRevision>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class)->orderByDesc('revision_number');
    }

    public function latestRevision(): ?PostRevision
    {
        return $this->revisions()->first();
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published->value)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Draft->value);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Scheduled->value)
            ->whereNotNull('scheduled_at');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PostStatus::PendingReview->value,
            PostStatus::InReview->value,
        ]);
    }

    public function scopeBreaking(Builder $query): Builder
    {
        return $query->published()
            ->where('is_breaking', true)
            ->where(function (Builder $q): void {
                $q->whereNull('breaking_expires_at')
                    ->orWhere('breaking_expires_at', '>', now());
            });
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('is_trending', true);
    }

    public function scopeEditorsPick(Builder $query): Builder
    {
        return $query->where('is_editors_pick', true);
    }

    public function scopePremium(Builder $query): Builder
    {
        return $query->where('is_premium', true);
    }

    public function scopeOfType(Builder $query, PostType|string $type): Builder
    {
        $value = $type instanceof PostType ? $type->value : $type;

        return $query->where('type', $value);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByAuthor(Builder $query, int $authorId): Builder
    {
        return $query->where('author_id', $authorId);
    }

    /**
     * Posts visible to public visitors in a specific language: published
     * post + a published translation for that locale.
     */
    public function scopeVisibleIn(Builder $query, int $languageId): Builder
    {
        return $query->published()
            ->whereHas('translations', function (Builder $q) use ($languageId): void {
                $q->where('language_id', $languageId)->where('is_published', true);
            });
    }

    public function scopeRecentlyPublished(Builder $query, int $days = 7): Builder
    {
        return $query->published()
            ->where('published_at', '>=', now()->subDays($days));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published
            && ($this->published_at === null || $this->published_at <= now());
    }

    public function isScheduled(): bool
    {
        return $this->status === PostStatus::Scheduled
            && $this->scheduled_at !== null
            && $this->scheduled_at > now();
    }

    public function isBreakingActive(): bool
    {
        if (! $this->is_breaking || ! $this->isPublished()) {
            return false;
        }

        return $this->breaking_expires_at === null
            || $this->breaking_expires_at > now();
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->author_id === (int) $user->id;
    }

    public function urlFor(?string $locale = null): string
    {
        $slug = (string) ($this->translate('slug', $locale) ?? '');

        if ($slug === '') {
            return '/';
        }

        $localePrefix = $locale !== null ? "/{$locale}" : '';
        $categorySlug = $this->category?->translate('slug', $locale);

        if ($categorySlug !== null && $categorySlug !== '') {
            return "{$localePrefix}/{$categorySlug}/{$slug}";
        }

        return "{$localePrefix}/{$slug}";
    }
}
