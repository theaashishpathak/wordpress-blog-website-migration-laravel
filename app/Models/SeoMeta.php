<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SeoMetaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic, locale-aware SEO meta storage.
 *
 * Attached to Post / Page / Category / Tag / Author when an admin needs
 * advanced overrides (custom schema type, OG/Twitter card image overrides,
 * canonical URL, robots directive, schema.org JSON-LD payload).
 *
 * The unique constraint (seoable_type, seoable_id, language_id) ensures
 * a single SEO row per (subject, locale) pair — language_id may be null
 * for seoables that have only one global SEO record (e.g., Author profile).
 */
class SeoMeta extends Model
{
    /** @use HasFactory<SeoMetaFactory> */
    use HasFactory;

    public const SCHEMA_ARTICLE = 'Article';

    public const SCHEMA_NEWS_ARTICLE = 'NewsArticle';

    public const SCHEMA_BLOG_POSTING = 'BlogPosting';

    public const SCHEMA_FAQ_PAGE = 'FAQPage';

    public const SCHEMA_BREADCRUMB_LIST = 'BreadcrumbList';

    public const SCHEMA_ORGANIZATION = 'Organization';

    public const SCHEMA_WEBSITE = 'WebSite';

    public const SCHEMA_PERSON = 'Person';

    public const SCHEMA_VIDEO_OBJECT = 'VideoObject';

    public const SCHEMA_REVIEW = 'Review';

    /** @var list<string> */
    public const SCHEMA_TYPES = [
        self::SCHEMA_ARTICLE,
        self::SCHEMA_NEWS_ARTICLE,
        self::SCHEMA_BLOG_POSTING,
        self::SCHEMA_FAQ_PAGE,
        self::SCHEMA_BREADCRUMB_LIST,
        self::SCHEMA_ORGANIZATION,
        self::SCHEMA_WEBSITE,
        self::SCHEMA_PERSON,
        self::SCHEMA_VIDEO_OBJECT,
        self::SCHEMA_REVIEW,
    ];

    /** @var list<string> */
    protected $fillable = [
        'seoable_type',
        'seoable_id',
        'language_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'focus_keyword',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_type',
        'schema_data',
        'seo_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema_data' => 'array',
            'seo_score' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, SeoMeta>
     */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Language, SeoMeta>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function scopeForLocale(Builder $query, ?int $languageId): Builder
    {
        return $languageId === null
            ? $query->whereNull('language_id')
            : $query->where('language_id', $languageId);
    }

    public function scopeOfSchemaType(Builder $query, string $schemaType): Builder
    {
        return $query->where('schema_type', $schemaType);
    }
}
