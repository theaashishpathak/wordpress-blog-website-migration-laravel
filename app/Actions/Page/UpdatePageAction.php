<?php

declare(strict_types=1);

namespace App\Actions\Page;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Update structural fields and upsert per-locale translation rows.
 *
 * `translations.*.is_published` toggles per-locale visibility independently
 * of the parent page's overall `status`. Both gates must be open for the
 * frontend to serve a localized page (see Page::scopeVisibleIn).
 */
class UpdatePageAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Page $page, array $data): Page
    {
        return DB::transaction(function () use ($page, $data): Page {
            $this->updateStructuralFields($page, $data);
            $this->upsertTranslations($page, $data['translations'] ?? []);

            return $page->fresh(['translations']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateStructuralFields(Page $page, array $data): void
    {
        $structural = Arr::only($data, [
            'template', 'show_in_menu', 'sort_order',
        ]);

        if (array_key_exists('status', $data)) {
            $status = $data['status'];
            $structural['status'] = $status instanceof PageStatus ? $status->value : (string) $status;
        }

        if (array_key_exists('updated_by', $data)) {
            $structural['updated_by'] = $data['updated_by'];
        }

        if ($structural !== []) {
            $page->fill($structural)->save();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     */
    private function upsertTranslations(Page $page, array $translations): void
    {
        foreach ($translations as $row) {
            $languageId = (int) ($row['language_id'] ?? 0);

            if ($languageId === 0) {
                continue;
            }

            $existing = $page->translations()
                ->where('language_id', $languageId)
                ->first();

            if (! empty($row['delete']) && $existing !== null) {
                // Refuse to delete the last translation.
                if ($page->translations()->count() > 1) {
                    $existing->delete();
                }

                continue;
            }

            $payload = [
                'language_id' => $languageId,
                'title' => trim((string) ($row['title'] ?? ($existing->title ?? ''))),
                'slug' => $this->resolveSlug($row, $existing),
                'content' => array_key_exists('content', $row)
                    ? $row['content']
                    : ($existing->content ?? null),
                'meta_title' => array_key_exists('meta_title', $row)
                    ? $row['meta_title']
                    : ($existing->meta_title ?? null),
                'meta_description' => array_key_exists('meta_description', $row)
                    ? $row['meta_description']
                    : ($existing->meta_description ?? null),
                'og_image' => array_key_exists('og_image', $row)
                    ? $row['og_image']
                    : ($existing->og_image ?? null),
                'is_published' => array_key_exists('is_published', $row)
                    ? (bool) $row['is_published']
                    : (bool) ($existing->is_published ?? false),
            ];

            if ($existing === null) {
                $page->translations()->create($payload);
            } else {
                $existing->update($payload);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveSlug(array $row, ?Model $existing): string
    {
        $slug = trim((string) ($row['slug'] ?? ''));

        if ($slug !== '') {
            return Str::slug($slug);
        }

        if ($existing !== null && $existing->getAttribute('slug') !== null) {
            return (string) $existing->getAttribute('slug');
        }

        $title = trim((string) ($row['title'] ?? ''));

        return Str::slug($title);
    }
}
