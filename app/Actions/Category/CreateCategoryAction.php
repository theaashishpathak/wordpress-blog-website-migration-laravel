<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Create a Category along with its per-locale translation rows.
 *
 * Input shape:
 *
 *   [
 *       'parent_id'         => ?int,
 *       'image_id'          => ?int,
 *       'icon'              => ?string,
 *       'color'             => ?string,
 *       'show_in_menu'      => bool,
 *       'show_on_homepage'  => bool,
 *       'is_featured'       => bool,
 *       'sort_order'        => int,
 *       'layout'            => string,
 *       'translations'      => [
 *           [
 *               'language_id'      => int,
 *               'name'             => string (required),
 *               'slug'             => ?string,                 // auto-slugged from name when blank
 *               'description'      => ?string,
 *               'meta_title'       => ?string,
 *               'meta_description' => ?string,
 *           ],
 *           ...
 *       ],
 *   ]
 *
 * At least one translation in the default language is required —
 * otherwise listing pages would have no name to show.
 */
class CreateCategoryAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Category
    {
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $this->assertHasDefaultLanguageTranslation($translations);
        $this->assertSlugsAreUniquePerLanguage($translations);

        return DB::transaction(function () use ($data, $translations): Category {
            $category = Category::query()->create([
                'parent_id' => $data['parent_id'] ?? null,
                'image_id' => $data['image_id'] ?? null,
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? null,
                'show_in_menu' => (bool) ($data['show_in_menu'] ?? true),
                'show_on_homepage' => (bool) ($data['show_on_homepage'] ?? false),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'layout' => (string) ($data['layout'] ?? Category::LAYOUT_GRID),
            ]);

            foreach ($translations as $translation) {
                $category->translations()->create($translation);
            }

            return $category->fresh(['translations']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     * @return list<array<string, mixed>>
     */
    private function normalizeTranslations(array $translations): array
    {
        return array_values(array_map(function (array $row): array {
            $name = trim((string) ($row['name'] ?? ''));
            $slug = trim((string) ($row['slug'] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    'translations.name' => 'Each translation must include a name.',
                ]);
            }

            return [
                'language_id' => (int) ($row['language_id'] ?? 0),
                'name' => $name,
                'slug' => $slug !== '' ? Str::slug($slug) : Str::slug($name),
                'description' => $row['description'] ?? null,
                'meta_title' => $row['meta_title'] ?? null,
                'meta_description' => $row['meta_description'] ?? null,
            ];
        }, $translations));
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     */
    private function assertHasDefaultLanguageTranslation(array $translations): void
    {
        if ($translations === []) {
            throw ValidationException::withMessages([
                'translations' => 'At least one translation is required.',
            ]);
        }

        $defaultLanguage = Language::query()->default()->first();

        if ($defaultLanguage === null) {
            // Fresh install / test boot before LanguageSeeder ran — accept
            // whatever translations were supplied.
            return;
        }

        $hasDefault = collect($translations)
            ->contains(fn (array $t): bool => (int) ($t['language_id'] ?? 0) === (int) $defaultLanguage->id);

        if (! $hasDefault) {
            throw ValidationException::withMessages([
                'translations' => "A translation in the default language ({$defaultLanguage->code}) is required.",
            ]);
        }
    }

    /**
     * Within the same request, prevent two translations from claiming the
     * same (language_id, slug) pair — DB constraint would catch this but
     * we want a friendlier error.
     *
     * @param  list<array<string, mixed>>  $translations
     */
    private function assertSlugsAreUniquePerLanguage(array $translations): void
    {
        $seen = [];

        foreach ($translations as $translation) {
            $key = ((string) $translation['language_id']).':'.((string) $translation['slug']);

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'translations.slug' => "Duplicate slug [{$translation['slug']}] for language [{$translation['language_id']}].",
                ]);
            }

            $seen[$key] = true;
        }
    }
}
