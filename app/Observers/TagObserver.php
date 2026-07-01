<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Language;
use App\Models\Tag;
use App\Support\LocaleResolver;

/**
 * Keep the legacy tags.name / tags.slug columns in sync with the
 * default-language tag_translations row.
 *
 * - On Tag save, ensure a default-language translation exists.
 *   If absent, materialize one from tags.name/tags.slug.
 *   If present but the parent's name/slug changed (legacy code path),
 *   update the translation to match.
 *
 * This invariant lets new translation-aware code (->translate('name'))
 * work transparently for tags created through the legacy TagFormModal,
 * without forcing the existing UI to be rewritten in this phase.
 *
 * Future Phase 2B-2 will retire the legacy columns and remove this sync.
 */
class TagObserver
{
    public function saved(Tag $tag): void
    {
        $defaultLanguage = $this->defaultLanguage();

        if ($defaultLanguage === null) {
            return;
        }

        $translation = $tag->translations()
            ->where('language_id', $defaultLanguage->id)
            ->first();

        $name = (string) ($tag->name ?? '');
        $slug = (string) ($tag->slug ?? '');

        if ($name === '' && $slug === '') {
            return;
        }

        if ($translation === null) {
            $tag->translations()->create([
                'language_id' => $defaultLanguage->id,
                'name' => $name,
                'slug' => $slug !== '' ? $slug : str($name)->slug()->value(),
            ]);

            return;
        }

        // Drift check — sync legacy column edits into the translation row.
        $needsUpdate = ($translation->name !== $name && $name !== '')
            || ($translation->slug !== $slug && $slug !== '');

        if ($needsUpdate) {
            $translation->fill([
                'name' => $name !== '' ? $name : $translation->name,
                'slug' => $slug !== '' ? $slug : $translation->slug,
            ])->save();
        }
    }

    private function defaultLanguage(): ?Language
    {
        try {
            return app(LocaleResolver::class)->default();
        } catch (\Throwable) {
            // Languages table not yet seeded — invariant cannot be maintained
            // until the next save, after seeding completes.
            return null;
        }
    }
}
