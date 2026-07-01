<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PageTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-language row for Page — title, slug, content, meta, og_image, is_published.
 *
 * is_published is a *per-locale* publishing gate (distinct from `pages.status`).
 * The English translation can be live while the Bangla translation is still
 * marked as draft awaiting editorial review.
 */
class PageTranslation extends Model
{
    /** @use HasFactory<PageTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'page_id',
        'language_id',
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'og_image',
        'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Page, PageTranslation>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * @return BelongsTo<Language, PageTranslation>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
