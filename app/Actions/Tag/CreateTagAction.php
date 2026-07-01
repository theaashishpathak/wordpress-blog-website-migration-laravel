<?php

declare(strict_types=1);

namespace App\Actions\Tag;

use App\Models\Language;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Create a Tag with one or more translation rows.
 *
 * Input shape:
 *
 *   [
 *       'code'          => ?string,    // auto-generated 4-digit when null
 *       'color'         => ?string,
 *       'type'          => ?string,    // default Tag::TYPE_GENERAL
 *       'status'        => ?string,    // default Tag::STATUS_PUBLISHED
 *       'created_by'    => ?int,
 *       'translations'  => [
 *           ['language_id' => int, 'name' => string, 'slug' => ?string, 'description' => ?string, ...],
 *           ...
 *       ],
 *   ]
 *
 * At least one default-language translation is required.
 * tags.name / tags.slug are populated from the default-language translation
 * (keeps the legacy single-language UI working alongside the new flow).
 */
class CreateTagAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Tag
    {
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $this->assertSlugsAreUniquePerLanguage($translations);

        $defaultTranslation = $this->resolveDefaultTranslation($translations);

        return DB::transaction(function () use ($data, $translations, $defaultTranslation): Tag {
            $tag = Tag::query()->create([
                'code' => (string) ($data['code'] ?? $this->generateCode()),
                // Mirror default-language fields into legacy columns for UI compat.
                'name' => (string) $defaultTranslation['name'],
                'slug' => (string) $defaultTranslation['slug'],
                'color' => $data['color'] ?? '#6366f1',
                'type' => (string) ($data['type'] ?? Tag::TYPE_GENERAL),
                'status' => (string) ($data['status'] ?? Tag::STATUS_PUBLISHED),
                'created_by' => $data['created_by'] ?? auth()->id() ?? 1,
                'updated_by' => $data['updated_by'] ?? $data['created_by'] ?? auth()->id() ?? 1,
            ]);

            // TagObserver may have already created the default-language
            // translation; reuse the row if present so we don't trip the
            // unique constraint on (tag_id, language_id).
            foreach ($translations as $row) {
                $existing = $tag->translations()
                    ->where('language_id', $row['language_id'])
                    ->first();

                if ($existing !== null) {
                    $existing->fill($row)->save();

                    continue;
                }

                $tag->translations()->create($row);
            }

            return $tag->fresh(['translations']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     * @return list<array<string, mixed>>
     */
    private function normalizeTranslations(array $translations): array
    {
        if ($translations === []) {
            throw ValidationException::withMessages([
                'translations' => 'At least one translation is required.',
            ]);
        }

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

    /**
     * @param  list<array<string, mixed>>  $translations
     * @return array<string, mixed>
     */
    private function resolveDefaultTranslation(array $translations): array
    {
        $defaultLanguage = Language::query()->default()->first();

        if ($defaultLanguage === null) {
            // No default language seeded yet — fall back to the first translation.
            return $translations[0];
        }

        foreach ($translations as $translation) {
            if ((int) $translation['language_id'] === (int) $defaultLanguage->id) {
                return $translation;
            }
        }

        throw ValidationException::withMessages([
            'translations' => "A translation in the default language ({$defaultLanguage->code}) is required.",
        ]);
    }

    private function generateCode(): string
    {
        $highest = Tag::query()
            ->pluck('code')
            ->map(static function (?string $code): int {
                return $code !== null && preg_match('/^\d+$/', $code) === 1 ? (int) $code : 0;
            })
            ->max() ?? 0;

        return str_pad((string) (((int) $highest) + 1), 4, '0', STR_PAD_LEFT);
    }
}
