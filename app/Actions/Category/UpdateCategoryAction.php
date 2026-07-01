<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Update structural fields on a Category and upsert each supplied
 * translation row.
 *
 * - Structural keys (parent_id, icon, color, show_in_menu, ...) updated
 *   in place; only supplied keys are written, others left alone.
 * - For each translation entry: row exists for (category_id, language_id)?
 *     yes → update its fields
 *     no  → create it
 * - Set 'translations.{i}.delete' = true to drop that translation row
 *   (only allowed if at least one translation remains).
 */
class UpdateCategoryAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data): Category {
            $this->updateStructuralFields($category, $data);
            $this->upsertTranslations($category, $data['translations'] ?? []);

            return $category->fresh(['translations']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateStructuralFields(Category $category, array $data): void
    {
        $structural = Arr::only($data, [
            'parent_id', 'image_id', 'icon', 'color',
            'show_in_menu', 'show_on_homepage', 'is_featured',
            'sort_order', 'layout',
        ]);

        // Defensive: a category cannot be its own parent.
        if (isset($structural['parent_id']) && (int) $structural['parent_id'] === (int) $category->id) {
            unset($structural['parent_id']);
        }

        if ($structural !== []) {
            $category->fill($structural)->save();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     */
    private function upsertTranslations(Category $category, array $translations): void
    {
        foreach ($translations as $row) {
            $languageId = (int) ($row['language_id'] ?? 0);

            if ($languageId === 0) {
                continue;
            }

            $existing = $category->translations()
                ->where('language_id', $languageId)
                ->first();

            if (! empty($row['delete']) && $existing !== null) {
                // Refuse to delete the last translation — leaves the
                // category with no name to show.
                if ($category->translations()->count() > 1) {
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
                $category->translations()->create($payload);
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
}
