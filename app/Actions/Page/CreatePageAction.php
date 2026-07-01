<?php

declare(strict_types=1);

namespace App\Actions\Page;

use App\Enums\PageStatus;
use App\Models\Language;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Create a Page with one or more translation rows.
 *
 * Input shape:
 *
 *   [
 *       'status'       => ?string|PageStatus,
 *       'template'     => ?string,
 *       'show_in_menu' => ?bool,
 *       'sort_order'   => ?int,
 *       'created_by'   => ?int,
 *       'translations' => [
 *           [
 *               'language_id'      => int,
 *               'title'            => string,
 *               'slug'             => ?string,
 *               'content'          => ?string,
 *               'is_published'     => ?bool,         // per-locale gate
 *               'meta_title'       => ?string,
 *               'meta_description' => ?string,
 *               'og_image'         => ?string,
 *           ],
 *           ...
 *       ],
 *   ]
 */
class CreatePageAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Page
    {
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $this->assertHasDefaultLanguageTranslation($translations);
        $this->assertSlugsAreUniquePerLanguage($translations);

        return DB::transaction(function () use ($data, $translations): Page {
            $status = $data['status'] ?? PageStatus::Draft;

            $page = Page::query()->create([
                'status' => $status instanceof PageStatus ? $status->value : (string) $status,
                'template' => (string) ($data['template'] ?? Page::TEMPLATE_DEFAULT),
                'show_in_menu' => (bool) ($data['show_in_menu'] ?? false),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'created_by' => $data['created_by'] ?? auth()->id(),
                'updated_by' => $data['updated_by'] ?? $data['created_by'] ?? auth()->id(),
            ]);

            foreach ($translations as $row) {
                $page->translations()->create($row);
            }

            return $page->fresh(['translations']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     * @return list<array<string, mixed>>
     */
    private function normalizeTranslations(array $translations): array
    {
        return array_values(array_map(function (array $row): array {
            $title = trim((string) ($row['title'] ?? ''));
            $slug = trim((string) ($row['slug'] ?? ''));

            if ($title === '') {
                throw ValidationException::withMessages([
                    'translations.title' => 'Each translation must include a title.',
                ]);
            }

            return [
                'language_id' => (int) ($row['language_id'] ?? 0),
                'title' => $title,
                'slug' => $slug !== '' ? Str::slug($slug) : Str::slug($title),
                'content' => $row['content'] ?? null,
                'meta_title' => $row['meta_title'] ?? null,
                'meta_description' => $row['meta_description'] ?? null,
                'og_image' => $row['og_image'] ?? null,
                'is_published' => (bool) ($row['is_published'] ?? false),
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
