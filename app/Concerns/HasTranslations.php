<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Language;
use App\Support\LocaleResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Provides the standard translation API for any model that has a
 * companion `{model}_translations` table.
 *
 * Usage:
 *
 *     class Category extends Model implements Translatable
 *     {
 *         use HasTranslations;
 *
 *         public static function translationModel(): string
 *         {
 *             return CategoryTranslation::class;
 *         }
 *     }
 *
 * The trait assumes the translation table follows the
 * `{model_snake}_translations` naming convention with a
 * `{model_snake}_id` foreign key — Laravel's default for hasMany.
 *
 * Authoritative spec: docs/Multilanguage Schema.txt Section 12.
 */
trait HasTranslations
{
    /**
     * @return HasMany<Model>
     */
    public function translations(): HasMany
    {
        /** @var class-string<Model> $translationClass */
        $translationClass = static::translationModel();

        /** @var HasMany<Model> $relation */
        $relation = $this->hasMany($translationClass);

        return $relation;
    }

    public function translation(?string $locale = null): ?Model
    {
        $language = $this->resolveLanguage($locale);

        if ($language === null) {
            return null;
        }

        return $this->resolveTranslationRow($language->id);
    }

    public function translate(string $field, ?string $locale = null, mixed $default = null): mixed
    {
        $translation = $this->translation($locale);

        if ($translation !== null && $translation->getAttribute($field) !== null) {
            return $translation->getAttribute($field);
        }

        // Fallback chain: caller locale → model's fallbackLanguage → caller default
        $fallback = $this->fallbackLanguage();

        if ($fallback !== null) {
            $fallbackTranslation = $this->resolveTranslationRow($fallback->id);

            if ($fallbackTranslation !== null && $fallbackTranslation->getAttribute($field) !== null) {
                return $fallbackTranslation->getAttribute($field);
            }
        }

        return $default;
    }

    /**
     * Resolve a translation row by language id, preferring the
     * already-loaded `translations` collection. Without this, every
     * call to `translate()` ran a fresh SELECT — a classic N+1 in
     * listing views (admin posts index, post-card, etc.).
     */
    private function resolveTranslationRow(int $languageId): ?Model
    {
        // Hit the eager-loaded collection when present — zero queries.
        if ($this->relationLoaded('translations')) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, Model> $loaded */
            $loaded = $this->getRelation('translations');
            $found = $loaded->firstWhere('language_id', $languageId);
            if ($found instanceof Model) {
                return $found;
            }
        }

        /** @var Model|null $translation */
        $translation = $this->translations()
            ->where('language_id', $languageId)
            ->first();

        return $translation;
    }

    public function fallbackLanguage(): ?Language
    {
        // If the model carries an explicit `default_language_id` column
        // (Post does), prefer that. Otherwise fall back to the app default.
        if ($this->getAttribute('default_language_id') !== null) {
            return Language::query()->find($this->getAttribute('default_language_id'));
        }

        return app(LocaleResolver::class)->default();
    }

    /**
     * Convenience: return all translations keyed by language code.
     *
     * @return array<string, Model>
     */
    public function translationsByLocale(): array
    {
        return $this->translations()
            ->with('language')
            ->get()
            ->mapWithKeys(function (Model $translation): array {
                /** @var Language|null $language */
                $language = $translation->getRelation('language');

                return $language !== null ? [(string) $language->code => $translation] : [];
            })
            ->all();
    }

    public function hasTranslationFor(string $locale): bool
    {
        $language = $this->resolveLanguage($locale);

        if ($language === null) {
            return false;
        }

        return $this->translations()
            ->where('language_id', $language->id)
            ->exists();
    }

    private function resolveLanguage(?string $locale): ?Language
    {
        $resolver = app(LocaleResolver::class);

        if ($locale === null) {
            return $resolver->current() ?? $resolver->default();
        }

        return $resolver->byCode($locale) ?? $resolver->default();
    }
}
