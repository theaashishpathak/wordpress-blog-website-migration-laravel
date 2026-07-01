<?php

declare(strict_types=1);

namespace App\Actions\Tag;

use App\Models\Language;
use App\Models\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Update structural fields + upsert translation rows on a Tag.
 *
 * If the default-language translation is updated, the legacy
 * tags.name / tags.slug columns are mirrored to match so the existing
 * UI keeps showing the same values.
 */
class UpdateTagAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Tag $tag, array $data): Tag
    {
        return DB::transaction(function () use ($tag, $data): Tag {
            $this->updateStructuralFields($tag, $data);
            $this->upsertTranslations($tag, $data['translations'] ?? []);
            $this->syncLegacyColumns($tag);

            return $tag->fresh(['translations']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateStructuralFields(Tag $tag, array $data): void
    {
        $structural = Arr::only($data, [
            'code', 'color', 'type', 'status',
        ]);

        if (array_key_exists('updated_by', $data)) {
            $structural['updated_by'] = $data['updated_by'];
        }

        if ($structural !== []) {
            $tag->fill($structural)->save();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     */
    private function upsertTranslations(Tag $tag, array $translations): void
    {
        foreach ($translations as $row) {
            $languageId = (int) ($row['language_id'] ?? 0);

            if ($languageId === 0) {
                continue;
            }

            $existing = $tag->translations()
                ->where('language_id', $languageId)
                ->first();

            if (! empty($row['delete']) && $existing !== null) {
                if ($tag->translations()->count() > 1) {
                    $existing->delete();
                }

                continue;
            }

            $payload = [
                'language_id' => $languageId,
                'name' => trim((string) ($row['name'] ?? ($existing->name ?? ''))),
                'slug' => $this->resolveSlug($row, $existing),
                'description' => array_key_exists('description', $row)
                    ? $row['description']
                    : ($existing->description ?? null),
                'meta_title' => array_key_exists('meta_title', $row)
                    ? $row['meta_title']
                    : ($existing->meta_title ?? null),
                'meta_description' => array_key_exists('meta_description', $row)
                    ? $row['meta_description']
                    : ($existing->meta_description ?? null),
            ];

            if ($existing === null) {
                $tag->translations()->create($payload);
            } else {
                $existing->update($payload);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveSlug(array $row, ?\Illuminate\Database\Eloquent\Model $existing): string
    {
        $slug = trim((string) ($row['slug'] ?? ''));

        if ($slug !== '') {
            return Str::slug($slug);
        }

        if ($existing !== null && $existing->getAttribute('slug') !== null) {
            return (string) $existing->getAttribute('slug');
        }

        $name = trim((string) ($row['name'] ?? ''));

        return Str::slug($name);
    }

    /**
     * Mirror default-language translation name + slug into the legacy
     * tags table columns so the existing single-language UI continues
     * to render correctly.
     */
    private function syncLegacyColumns(Tag $tag): void
    {
        $defaultLanguage = Language::query()->default()->first();

        if ($defaultLanguage === null) {
            return;
        }

        $translation = $tag->translations()
            ->where('language_id', $defaultLanguage->id)
            ->first();

        if ($translation === null) {
            return;
        }

        // saveQuietly() to avoid the observer running and overwriting
        // the translation we just wrote.
        $tag->forceFill([
            'name' => (string) $translation->name,
            'slug' => (string) $translation->slug,
        ])->saveQuietly();
    }
}
