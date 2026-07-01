<?php

declare(strict_types=1);

namespace App\Actions\Language;

use App\Models\Language;
use App\Support\LocaleResolver;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Delete a language. Refuses when:
 *   - the language is currently the default
 *   - any post / page / category translation references it
 *
 * Callers should reassign the default + migrate translations to a
 * different language before invoking this Action.
 */
class DeleteLanguageAction
{
    public function __construct(private LocaleResolver $localeResolver) {}

    public function handle(Language $language): void
    {
        if ($language->is_default) {
            throw new InvalidArgumentException(
                'Cannot delete the default language. Promote another language to default first.'
            );
        }

        DB::transaction(function () use ($language): void {
            // Quick existence checks against the translation tables.
            // We don't try to migrate content — that's the user's job.
            $hasPostTranslations = DB::table('post_translations')->where('language_id', $language->id)->exists();
            $hasPageTranslations = DB::table('page_translations')->where('language_id', $language->id)->exists();
            $hasCategoryTranslations = DB::table('category_translations')->where('language_id', $language->id)->exists();

            if ($hasPostTranslations || $hasPageTranslations || $hasCategoryTranslations) {
                throw new InvalidArgumentException(
                    'Cannot delete a language while posts, pages, or categories still reference it.'
                );
            }

            $language->delete();
        });

        $this->localeResolver->flush();
    }
}
