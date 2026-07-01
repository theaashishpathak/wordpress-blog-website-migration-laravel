<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-language row for Category — name, slug, description, meta.
 *
 * One row per (category_id, language_id). Slug is unique per language
 * so /en/technology and /bn/technology can coexist with different
 * Category targets if the admin wants.
 */
class CategoryTranslation extends Model
{
    /** @use HasFactory<CategoryTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'category_id',
        'language_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
    ];

    /**
     * @return BelongsTo<Category, CategoryTranslation>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Language, CategoryTranslation>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
