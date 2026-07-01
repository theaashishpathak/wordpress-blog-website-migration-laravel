<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Enums\PostType;
use App\Models\Post;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Update a Post: structural fields + translation upsert + tag sync.
 *
 * Status transitions are intentionally NOT handled here — they go through
 * dedicated workflow Actions (SubmitForReviewAction, ApprovePostAction,
 * PublishPostAction, etc.) so the state machine and side effects
 * (editorial notes, observer publication backfill) stay in one place.
 *
 * If 'status' is supplied here it is ignored.
 */
class UpdatePostAction
{
    public function __construct(
        private CreatePostRevisionAction $createRevision,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data): Post {
            // Snapshot the pre-edit state FIRST so the revision history
            // always shows where the post came from. The handle() call
            // returns immediately if the caller asked to skip this (rare
            // — e.g., system jobs that fix data inconsistencies).
            if (! ($data['skip_revision'] ?? false)) {
                $this->createRevision->handle(
                    $post,
                    authorId: $data['updated_by'] ?? null,
                    summary: $data['revision_summary'] ?? null,
                );
            }

            $this->updateStructuralFields($post, $data);
            $this->upsertTranslations($post, $data['translations'] ?? []);
            $this->syncTagsIfProvided($post, $data);

            return $post->fresh(['translations', 'tags']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateStructuralFields(Post $post, array $data): void
    {
        // Status changes go through the workflow Actions — drop any caller attempt.
        unset($data['status']);

        $structural = Arr::only($data, [
            'category_id', 'subcategory_id', 'default_language_id',
            'visibility', 'is_featured', 'is_breaking', 'is_trending',
            'is_editors_pick', 'is_sponsored', 'is_premium', 'allow_comments',
            'scheduled_at', 'breaking_expires_at',
            'featured_image_id', 'source_name', 'source_url',
            'updated_by',
        ]);

        if (array_key_exists('type', $data)) {
            $type = $data['type'];
            $structural['type'] = $type instanceof PostType ? $type->value : (string) $type;
        }

        // Defensive: a post's subcategory cannot equal its primary category.
        if (isset($structural['subcategory_id'], $structural['category_id'])
            && (int) $structural['subcategory_id'] === (int) $structural['category_id']) {
            unset($structural['subcategory_id']);
        }

        if ($structural !== []) {
            $post->fill($structural)->save();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     */
    private function upsertTranslations(Post $post, array $translations): void
    {
        foreach ($translations as $row) {
            $languageId = (int) ($row['language_id'] ?? 0);

            if ($languageId === 0) {
                continue;
            }

            $existing = $post->translations()
                ->where('language_id', $languageId)
                ->first();

            if (! empty($row['delete']) && $existing !== null) {
                if ($post->translations()->count() > 1) {
                    $existing->delete();
                }

                continue;
            }

            $payload = $this->buildTranslationPayload($row, $existing, $languageId);

            if ($existing === null) {
                $post->translations()->create($payload);
            } else {
                $existing->update($payload);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function buildTranslationPayload(array $row, ?Model $existing, int $languageId): array
    {
        return [
            'language_id' => $languageId,
            'title' => trim((string) ($row['title'] ?? ($existing->title ?? ''))),
            'slug' => $this->resolveSlug($row, $existing),
            'excerpt' => array_key_exists('excerpt', $row) ? $row['excerpt'] : ($existing->excerpt ?? null),
            'content' => array_key_exists('content', $row)
                ? app(HtmlSanitizer::class)->clean($row['content'])
                : ($existing->content ?? null),
            'reading_time' => array_key_exists('reading_time', $row)
                ? $row['reading_time']
                : ($existing->reading_time ?? null),
            'meta_title' => array_key_exists('meta_title', $row)
                ? $row['meta_title']
                : ($existing->meta_title ?? null),
            'meta_description' => array_key_exists('meta_description', $row)
                ? $row['meta_description']
                : ($existing->meta_description ?? null),
            'focus_keyword' => array_key_exists('focus_keyword', $row)
                ? $row['focus_keyword']
                : ($existing->focus_keyword ?? null),
            'canonical_url' => array_key_exists('canonical_url', $row)
                ? $row['canonical_url']
                : ($existing->canonical_url ?? null),
            'og_image' => array_key_exists('og_image', $row)
                ? $row['og_image']
                : ($existing->og_image ?? null),
            'seo_score' => array_key_exists('seo_score', $row)
                ? $row['seo_score']
                : ($existing->seo_score ?? null),
            'translation_status' => array_key_exists('translation_status', $row)
                ? (string) $row['translation_status']
                : ($existing->translation_status ?? \App\Models\PostTranslation::TRANSLATION_STATUS_MANUAL),
            'is_published' => array_key_exists('is_published', $row)
                ? (bool) $row['is_published']
                : (bool) ($existing->is_published ?? false),
        ];
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncTagsIfProvided(Post $post, array $data): void
    {
        if (! array_key_exists('tag_ids', $data)) {
            return;
        }

        $tagIds = (array) $data['tag_ids'];
        $payload = [];

        foreach ($tagIds as $tagId) {
            $payload[(int) $tagId] = ['created_at' => now()];
        }

        $post->tags()->sync($payload);
    }
}
