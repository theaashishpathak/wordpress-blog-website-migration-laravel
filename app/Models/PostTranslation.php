<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PostTranslationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-language row for Post — title, slug, content, inline SEO, and
 * translation lifecycle metadata.
 *
 * Slug is unique per language (UNIQUE language_id + slug) so the same
 * "ai-tools" slug can map to two different posts in English and Bangla.
 */
class PostTranslation extends Model
{
    /** @use HasFactory<PostTranslationFactory> */
    use HasFactory;

    public const TRANSLATION_STATUS_MANUAL = 'manual';

    public const TRANSLATION_STATUS_AI_GENERATED = 'ai_generated';

    public const TRANSLATION_STATUS_AI_REVIEWED = 'ai_reviewed';

    public const TRANSLATION_STATUS_HUMAN_REVIEWED = 'human_reviewed';

    public const TRANSLATION_STATUS_PUBLISHED = 'published';

    /** @var list<string> */
    public const TRANSLATION_STATUSES = [
        self::TRANSLATION_STATUS_MANUAL,
        self::TRANSLATION_STATUS_AI_GENERATED,
        self::TRANSLATION_STATUS_AI_REVIEWED,
        self::TRANSLATION_STATUS_HUMAN_REVIEWED,
        self::TRANSLATION_STATUS_PUBLISHED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'post_id',
        'language_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'reading_time',
        'meta_title',
        'meta_description',
        'focus_keyword',
        'canonical_url',
        'og_image',
        'seo_score',
        'translation_status',
        'is_published',
        'translated_at',
        'translated_by',
        'ai_translation_provider',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seo_score' => 'integer',
            'is_published' => 'boolean',
            'translated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Post, PostTranslation>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<Language, PostTranslation>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return BelongsTo<User, PostTranslation>
     */
    public function translatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'translated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeAiGenerated(Builder $query): Builder
    {
        return $query->where('translation_status', self::TRANSLATION_STATUS_AI_GENERATED);
    }

    public function scopeAwaitingReview(Builder $query): Builder
    {
        return $query->whereIn('translation_status', [
            self::TRANSLATION_STATUS_AI_GENERATED,
            self::TRANSLATION_STATUS_AI_REVIEWED,
        ]);
    }
}
