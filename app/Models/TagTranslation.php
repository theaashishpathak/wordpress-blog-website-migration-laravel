<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TagTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-language row for Tag — name, slug, description, meta.
 *
 * UNIQUE(tag_id, language_id) and UNIQUE(language_id, slug) so slugs
 * differ across locales but collide within one locale.
 */
class TagTranslation extends Model
{
    /** @use HasFactory<TagTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'tag_id',
        'language_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
    ];

    /**
     * @return BelongsTo<Tag, TagTranslation>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * @return BelongsTo<Language, TagTranslation>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
