<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Models that have per-language translation rows (Category, Tag, Page,
 * Post, Menu, EmailTemplate) implement this contract.
 *
 * Concrete implementation is provided by App\Concerns\HasTranslations —
 * models just need to `use HasTranslations;` and declare
 * `translationModel()` to satisfy this contract.
 *
 * Authoritative spec: docs/Multilanguage Schema.txt Section 12.
 */
interface Translatable
{
    /**
     * Class string of the translation Eloquent model
     * (e.g., CategoryTranslation::class for Category).
     *
     * @return class-string<Model>
     */
    public static function translationModel(): string;

    /**
     * @return HasMany<Model>
     */
    public function translations(): HasMany;

    /**
     * Return the translation row for the given locale (or current app
     * locale when null). Returns null if no translation exists.
     */
    public function translation(?string $locale = null): ?Model;

    /**
     * Return a single field value from the locale's translation, falling
     * back to the parent's `default_language_id` translation, then to the
     * caller-supplied default. Useful in Blade: {{ $category->translate('name') }}.
     */
    public function translate(string $field, ?string $locale = null, mixed $default = null): mixed;

    /**
     * Return the Language model whose translation should serve as fallback
     * when the requested locale is missing. Default: app's configured
     * default language. Models may override (e.g., Post can return its
     * own `default_language_id`).
     */
    public function fallbackLanguage(): ?Language;
}
