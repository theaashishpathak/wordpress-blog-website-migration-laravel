<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Language;
use App\Models\Post;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Create a Post with translations and tags in a single transaction.
 *
 * Input shape:
 *
 *   [
 *       'type'              => PostType|string,
 *       'category_id'       => ?int,
 *       'subcategory_id'    => ?int,
 *       'author_id'         => int (required),
 *       'default_language_id' => ?int  // defaults to global default language
 *       'status'            => PostStatus|string,           // defaults to Draft
 *       'visibility'        => ?string,
 *       'is_featured'       => ?bool,
 *       'is_breaking'       => ?bool,
 *       'is_trending'       => ?bool,
 *       'is_editors_pick'   => ?bool,
 *       'is_sponsored'      => ?bool,
 *       'is_premium'        => ?bool,
 *       'allow_comments'    => ?bool,
 *       'scheduled_at'      => ?\DateTimeInterface,
 *       'breaking_expires_at' => ?\DateTimeInterface,
 *       'featured_image_id' => ?int,
 *       'source_name'       => ?string,
 *       'source_url'        => ?string,
 *       'translations'      => list<array>,                  // required
 *       'tag_ids'           => ?list<int>,                   // optional
 *   ]
 */
class CreatePostAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Post
    {
        $authorId = (int) ($data['author_id'] ?? 0);

        if ($authorId === 0) {
            throw ValidationException::withMessages([
                'author_id' => 'author_id is required.',
            ]);
        }

        $defaultLanguage = $this->resolveDefaultLanguage((int) ($data['default_language_id'] ?? 0));
        $translations = $this->normalizeTranslations($data['translations'] ?? []);
        $this->assertHasDefaultLanguageTranslation($translations, $defaultLanguage);
        $this->assertSlugsAreUniqueWithinRequest($translations);

        return DB::transaction(function () use ($data, $defaultLanguage, $translations, $authorId): Post {
            $type = $data['type'] ?? PostType::Post;
            $status = $data['status'] ?? PostStatus::Draft;

            $post = Post::query()->create([
                'type' => $type instanceof PostType ? $type->value : (string) $type,
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'author_id' => $authorId,
                'default_language_id' => $defaultLanguage->id,
                'status' => $status instanceof PostStatus ? $status->value : (string) $status,
                'visibility' => (string) ($data['visibility'] ?? Post::VISIBILITY_PUBLIC),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'is_breaking' => (bool) ($data['is_breaking'] ?? false),
                'is_trending' => (bool) ($data['is_trending'] ?? false),
                'is_editors_pick' => (bool) ($data['is_editors_pick'] ?? false),
                'is_sponsored' => (bool) ($data['is_sponsored'] ?? false),
                'is_premium' => (bool) ($data['is_premium'] ?? false),
                'allow_comments' => (bool) ($data['allow_comments'] ?? true),
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'breaking_expires_at' => $data['breaking_expires_at'] ?? null,
                'featured_image_id' => $data['featured_image_id'] ?? null,
                'source_name' => $data['source_name'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'created_by' => $data['created_by'] ?? $authorId,
                'updated_by' => $data['updated_by'] ?? $authorId,
            ]);

            foreach ($translations as $row) {
                $post->translations()->create($row);
            }

            $tagIds = $data['tag_ids'] ?? [];

            if (is_array($tagIds) && $tagIds !== []) {
                $payload = [];
                foreach ($tagIds as $tagId) {
                    $payload[(int) $tagId] = ['created_at' => now()];
                }
                $post->tags()->sync($payload);
            }

            return $post->fresh(['translations', 'tags']);
        });
    }

    private function resolveDefaultLanguage(int $providedId): Language
    {
        if ($providedId > 0) {
            $language = Language::query()->find($providedId);
            if ($language !== null) {
                return $language;
            }
        }

        $default = Language::query()->default()->first();

        if ($default !== null) {
            return $default;
        }

        // Fresh install: any active language.
        $any = Language::query()->active()->ordered()->first();

        if ($any !== null) {
            return $any;
        }

        throw ValidationException::withMessages([
            'default_language_id' => 'No active language is configured; seed languages before creating posts.',
        ]);
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
                'slug' => $slug !== '' ? Str::slug($slug) : Str::slug($title).'-'.Str::lower(Str::random(4)),
                'excerpt' => $row['excerpt'] ?? null,
                'content' => app(HtmlSanitizer::class)->clean($row['content'] ?? null),
                'reading_time' => $row['reading_time'] ?? null,
                'meta_title' => $row['meta_title'] ?? null,
                'meta_description' => $row['meta_description'] ?? null,
                'focus_keyword' => $row['focus_keyword'] ?? null,
                'canonical_url' => $row['canonical_url'] ?? null,
                'og_image' => $row['og_image'] ?? null,
                'seo_score' => $row['seo_score'] ?? null,
                'translation_status' => $row['translation_status']
                    ?? \App\Models\PostTranslation::TRANSLATION_STATUS_MANUAL,
                'is_published' => (bool) ($row['is_published'] ?? false),
            ];
        }, $translations));
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     */
    private function assertHasDefaultLanguageTranslation(array $translations, Language $defaultLanguage): void
    {
        $hasDefault = collect($translations)
            ->contains(fn (array $t): bool => (int) ($t['language_id'] ?? 0) === (int) $defaultLanguage->id);

        if (! $hasDefault) {
            throw ValidationException::withMessages([
                'translations' => "A translation in the post's default language ({$defaultLanguage->code}) is required.",
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     */
    private function assertSlugsAreUniqueWithinRequest(array $translations): void
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
