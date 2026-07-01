<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Posts;

use App\Actions\AI\TranslateContentAction;
use App\Actions\Editorial\ApprovePostAction;
use App\Actions\Editorial\RejectPostAction;
use App\Actions\Editorial\RequestChangesAction;
use App\Actions\Editorial\SubmitForReviewAction;
use App\Actions\Post\ArchivePostAction;
use App\Actions\Post\PublishPostAction;
use App\Actions\Post\UnpublishPostAction;
use App\Actions\Post\UpdatePostAction;
use App\Actions\Seo\UpdateSeoMetaAction;
use App\Enums\PostType;
use App\Models\Category;
use App\Models\Language;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\SeoMeta;
use App\Models\Tag;
use App\Services\Seo\DataTransferObjects\SeoScoreInput;
use App\Services\Seo\DataTransferObjects\SeoScoreResult;
use App\Services\Seo\SeoScoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Edit Post')]
class Edit extends Component
{
    public Post $post;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $content = '';

    public string $type = 'post';

    public ?int $categoryId = null;

    public ?int $defaultLanguageId = null;

    /**
     * @var list<int>
     */
    public array $tagIds = [];

    public bool $isFeatured = false;

    public bool $isBreaking = false;

    public bool $isTrending = false;

    public bool $isEditorsPick = false;

    public bool $allowComments = true;

    public string $visibility = Post::VISIBILITY_PUBLIC;

    /**
     * Featured image — set by MediaPickerModal via the
     * `media.selected` event. Null means "no featured image".
     */
    public ?int $featuredImageId = null;

    /**
     * Which language tab is currently being edited. The scalar fields
     * (title, slug, excerpt, content, seoMeta*) always reflect this
     * tab. Switching tabs flushes the scalars into $translations and
     * loads the chosen language's values.
     */
    public ?int $activeLanguageId = null;

    /**
     * Per-language translation state. Keyed by language_id. Each entry:
     *   - title, slug, excerpt, content
     *   - meta_title, meta_description, focus_keyword, canonical_url
     *   - translation_status — one of PostTranslation::TRANSLATION_STATUSES
     *
     * @var array<int, array<string, mixed>>
     */
    public array $translations = [];

    /**
     * SEO Panel — basic fields (persisted to post_translations).
     */
    public string $seoMetaTitle = '';

    public string $seoMetaDescription = '';

    public string $seoFocusKeyword = '';

    public string $seoCanonicalUrl = '';

    /**
     * SEO Panel — advanced fields (persisted to seo_metas polymorphic table).
     */
    public string $seoRobots = '';

    public string $seoSchemaType = '';

    public string $seoMetaKeywords = '';

    public string $seoOgTitle = '';

    public string $seoOgDescription = '';

    public string $seoTwitterTitle = '';

    public string $seoTwitterDescription = '';

    /**
     * Editorial action feedback fields — collected via modal inputs.
     */
    public string $editorialNote = '';

    public function mount(Post $post): void
    {
        $this->authorize('update', $post);

        $this->post = $post->load(['translations', 'tags']);

        $this->type = $post->type->value;
        $this->categoryId = $post->category_id;
        $this->defaultLanguageId = $post->default_language_id;
        $this->tagIds = $post->tags->pluck('id')->all();
        $this->isFeatured = (bool) $post->is_featured;
        $this->isBreaking = (bool) $post->is_breaking;
        $this->isTrending = (bool) $post->is_trending;
        $this->isEditorsPick = (bool) $post->is_editors_pick;
        $this->allowComments = (bool) $post->allow_comments;
        $this->visibility = (string) $post->visibility;
        $this->featuredImageId = $post->featured_image_id;

        $this->loadTranslationsFromModel();
        $this->activeLanguageId = $this->defaultLanguageId ?? array_key_first($this->translations);
        $this->loadActiveTranslationIntoScalars();
        $this->hydrateAdvancedSeoFromModel();
    }

    /**
     * Build the $translations dictionary from every PostTranslation row
     * the post currently has.
     */
    private function loadTranslationsFromModel(): void
    {
        $this->translations = [];

        foreach ($this->post->translations as $translation) {
            $this->translations[(int) $translation->language_id] = $this->shapeTranslation($translation);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function shapeTranslation(PostTranslation $translation): array
    {
        return [
            'title' => (string) ($translation->title ?? ''),
            'slug' => (string) ($translation->slug ?? ''),
            'excerpt' => (string) ($translation->excerpt ?? ''),
            'content' => (string) ($translation->content ?? ''),
            'meta_title' => (string) ($translation->meta_title ?? ''),
            'meta_description' => (string) ($translation->meta_description ?? ''),
            'focus_keyword' => (string) ($translation->focus_keyword ?? ''),
            'canonical_url' => (string) ($translation->canonical_url ?? ''),
            'translation_status' => (string) ($translation->translation_status ?? PostTranslation::TRANSLATION_STATUS_MANUAL),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankTranslation(): array
    {
        return [
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'content' => '',
            'meta_title' => '',
            'meta_description' => '',
            'focus_keyword' => '',
            'canonical_url' => '',
            'translation_status' => PostTranslation::TRANSLATION_STATUS_MANUAL,
        ];
    }

    /**
     * Mirror the active tab's row from $translations into the scalar
     * properties bound to the form fields.
     */
    private function loadActiveTranslationIntoScalars(): void
    {
        $row = $this->translations[$this->activeLanguageId] ?? $this->blankTranslation();

        $this->title = (string) $row['title'];
        $this->slug = (string) $row['slug'];
        $this->excerpt = (string) $row['excerpt'];
        $this->content = (string) $row['content'];
        $this->seoMetaTitle = (string) $row['meta_title'];
        $this->seoMetaDescription = (string) $row['meta_description'];
        $this->seoFocusKeyword = (string) $row['focus_keyword'];
        $this->seoCanonicalUrl = (string) $row['canonical_url'];
    }

    /**
     * Push the current scalar values back into $translations under the
     * active language key. Call before switching tabs or saving.
     */
    private function flushScalarsIntoActiveTranslation(): void
    {
        if ($this->activeLanguageId === null) {
            return;
        }

        $existing = $this->translations[$this->activeLanguageId] ?? $this->blankTranslation();

        $this->translations[$this->activeLanguageId] = [
            ...$existing,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'meta_title' => $this->seoMetaTitle,
            'meta_description' => $this->seoMetaDescription,
            'focus_keyword' => $this->seoFocusKeyword,
            'canonical_url' => $this->seoCanonicalUrl,
        ];
    }

    /**
     * Pull advanced SEO overrides (robots, schema, social cards) from
     * the polymorphic seo_metas row for the active language. Basic SEO
     * fields (meta_title, meta_description, etc.) flow through the
     * per-language translation dictionary instead.
     */
    private function hydrateAdvancedSeoFromModel(): void
    {
        $advanced = SeoMeta::query()
            ->where('seoable_type', $this->post->getMorphClass())
            ->where('seoable_id', $this->post->getKey())
            ->forLocale($this->activeLanguageId)
            ->first();

        $this->seoRobots = (string) ($advanced->robots ?? '');
        $this->seoSchemaType = (string) ($advanced->schema_type ?? '');
        $this->seoMetaKeywords = (string) ($advanced->meta_keywords ?? '');
        $this->seoOgTitle = (string) ($advanced->og_title ?? '');
        $this->seoOgDescription = (string) ($advanced->og_description ?? '');
        $this->seoTwitterTitle = (string) ($advanced->twitter_title ?? '');
        $this->seoTwitterDescription = (string) ($advanced->twitter_description ?? '');
    }

    public function save(UpdatePostAction $updatePost, UpdateSeoMetaAction $updateSeo): void
    {
        $this->authorize('update', $this->post);
        $this->flushScalarsIntoActiveTranslation();
        $this->validate($this->rules());

        try {
            $updatePost->handle($this->post, $this->buildUpdatePayload());

            // Advanced SEO overrides are scoped to the active language —
            // they were just edited in the SEO panel for that tab.
            $updateSeo->handle(
                seoable: $this->post,
                languageId: $this->activeLanguageId,
                data: $this->buildAdvancedSeoPayload(),
            );

            $this->post = $this->post->fresh(['translations', 'tags']);
            $this->loadTranslationsFromModel();
            $this->loadActiveTranslationIntoScalars();
            $this->dispatchSuccessToast('Post updated.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Failed to update post: '.$exception->getMessage());
        }
    }

    /**
     * Switch the visible language tab. Flushes current edits into the
     * outgoing tab's row, then loads the incoming row into the scalar
     * editors. Advanced SEO overrides are re-read from the polymorphic
     * row since they're per-locale and not part of the dictionary.
     */
    public function switchLanguage(int $languageId): void
    {
        if ($languageId === $this->activeLanguageId) {
            return;
        }

        if (! isset($this->translations[$languageId])) {
            $this->translations[$languageId] = $this->blankTranslation();
        }

        $this->flushScalarsIntoActiveTranslation();
        $this->activeLanguageId = $languageId;
        $this->loadActiveTranslationIntoScalars();
        $this->hydrateAdvancedSeoFromModel();
        $this->broadcastContentRefresh();
    }

    /**
     * Start a new translation row for a language the post does not yet
     * cover. The new tab is initialised blank and made active.
     */
    public function addTranslation(int $languageId): void
    {
        if (isset($this->translations[$languageId])) {
            $this->switchLanguage($languageId);

            return;
        }

        $this->flushScalarsIntoActiveTranslation();
        $this->translations[$languageId] = $this->blankTranslation();
        $this->activeLanguageId = $languageId;
        $this->loadActiveTranslationIntoScalars();
        $this->hydrateAdvancedSeoFromModel();
        $this->broadcastContentRefresh();
    }

    /**
     * Drop a translation tab (in-memory only — persisted on Save).
     * The default language cannot be removed.
     */
    public function removeTranslation(int $languageId): void
    {
        if ($languageId === $this->defaultLanguageId) {
            $this->dispatchDangerToast('Cannot remove the default language translation.');

            return;
        }

        if (! isset($this->translations[$languageId])) {
            return;
        }

        // Mark for deletion via the UpdatePostAction translations API.
        $this->translations[$languageId]['delete'] = true;

        if ($this->activeLanguageId === $languageId) {
            $this->activeLanguageId = $this->defaultLanguageId;
            $this->loadActiveTranslationIntoScalars();
            $this->hydrateAdvancedSeoFromModel();
            $this->broadcastContentRefresh();
        }

        $this->dispatchSuccessToast('Translation marked for removal — save to apply.');
    }

    /**
     * Tell the TipTap wrapper (inside wire:ignore) to swap its displayed
     * content for $this->content. wire:ignore blocks Livewire's normal
     * DOM diff so the editor needs an explicit nudge. We dispatch both
     * the new (editor:set-content) and legacy (post-content-refreshed)
     * events so any third-party extension still listening keeps working.
     */
    private function broadcastContentRefresh(): void
    {
        $this->dispatch('post-content-refreshed', content: $this->content);
        $this->js("window.dispatchEvent(new CustomEvent('editor:set-content', { detail: { content: ".json_encode($this->content)." } }))");
    }

    /**
     * Translate the title + content from the default language into the
     * currently active tab using TranslateContentAction. The result is
     * inserted directly into the scalar editors; the user is expected
     * to review before saving.
     */
    public function translateActiveFromDefault(TranslateContentAction $translate): void
    {
        if ($this->activeLanguageId === null || $this->activeLanguageId === $this->defaultLanguageId) {
            $this->dispatchDangerToast('Switch to a non-default language tab before translating.');

            return;
        }

        $source = $this->translations[$this->defaultLanguageId] ?? null;

        if ($source === null || trim((string) $source['content']) === '') {
            $this->dispatchDangerToast('The default language has no content to translate yet.');

            return;
        }

        $targetLanguage = Language::query()->find($this->activeLanguageId);
        $sourceLanguage = Language::query()->find($this->defaultLanguageId);

        if ($targetLanguage === null || $sourceLanguage === null) {
            $this->dispatchDangerToast('Language metadata is missing.');

            return;
        }

        try {
            $translatedContent = $translate->handle(
                article: (string) $source['content'],
                targetLanguage: (string) $targetLanguage->name,
                sourceLocale: (string) $sourceLanguage->code,
                userId: (int) auth()->id(),
            );

            $translatedTitle = trim((string) $source['title']) !== ''
                ? $translate->handle(
                    article: (string) $source['title'],
                    targetLanguage: (string) $targetLanguage->name,
                    sourceLocale: (string) $sourceLanguage->code,
                    userId: (int) auth()->id(),
                )
                : '';

            $this->title = $translatedTitle !== '' ? $translatedTitle : $this->title;
            $this->content = $translatedContent;
            $this->translations[$this->activeLanguageId]['translation_status'] = PostTranslation::TRANSLATION_STATUS_AI_GENERATED;
            $this->flushScalarsIntoActiveTranslation();
            $this->broadcastContentRefresh();

            $this->dispatchSuccessToast('Translation drafted — review before saving.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('AI translation failed: '.$exception->getMessage());
        }
    }

    /**
     * Payload for the polymorphic seo_metas row. Basic SEO fields are
     * intentionally NOT included here — those go into post_translations
     * via the main UpdatePostAction translations array.
     *
     * @return array<string, mixed>
     */
    private function buildAdvancedSeoPayload(): array
    {
        return [
            'robots' => $this->seoRobots,
            'schema_type' => $this->seoSchemaType,
            'meta_keywords' => $this->seoMetaKeywords,
            'og_title' => $this->seoOgTitle,
            'og_description' => $this->seoOgDescription,
            'twitter_title' => $this->seoTwitterTitle,
            'twitter_description' => $this->seoTwitterDescription,
            'seo_score' => $this->seoScore->overall,
        ];
    }

    public function submitForReview(SubmitForReviewAction $submit): void
    {
        $this->authorize('submitForReview', $this->post);

        try {
            $submit->handle($this->post->fresh(), auth()->user(), note: $this->editorialNote ?: null);
            $this->reloadPost();
            $this->editorialNote = '';
            $this->dispatchSuccessToast('Submitted for editorial review.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Submit failed: '.$exception->getMessage());
        }
    }

    public function approve(ApprovePostAction $approve): void
    {
        $this->authorize('approve', $this->post);

        try {
            $approve->handle($this->post->fresh(), auth()->user(), note: $this->editorialNote ?: null);
            $this->reloadPost();
            $this->editorialNote = '';
            $this->dispatchSuccessToast('Post approved.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Approve failed: '.$exception->getMessage());
        }
    }

    public function reject(RejectPostAction $reject): void
    {
        $this->authorize('reject', $this->post);

        try {
            $reject->handle($this->post->fresh(), auth()->user(), reason: $this->editorialNote);
            $this->reloadPost();
            $this->editorialNote = '';
            $this->dispatchSuccessToast('Post rejected.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatchDangerToast('Rejection reason is required.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Reject failed: '.$exception->getMessage());
        }
    }

    public function requestChanges(RequestChangesAction $requestChanges): void
    {
        $this->authorize('requestChanges', $this->post);

        try {
            $requestChanges->handle($this->post->fresh(), auth()->user(), feedback: $this->editorialNote);
            $this->reloadPost();
            $this->editorialNote = '';
            $this->dispatchSuccessToast('Changes requested.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatchDangerToast('Change-request feedback is required.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Request changes failed: '.$exception->getMessage());
        }
    }

    public function publish(PublishPostAction $publish): void
    {
        $this->authorize('publish', $this->post);

        try {
            $publish->handle(
                $this->post->fresh(),
                cascadeTranslations: true,
                allowDirectPublish: true,
                publisher: auth()->user(),
            );
            $this->reloadPost();
            $this->dispatchSuccessToast('Post published.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Publish failed: '.$exception->getMessage());
        }
    }

    public function unpublish(UnpublishPostAction $unpublish): void
    {
        $this->authorize('publish', $this->post);

        try {
            $unpublish->handle($this->post->fresh());
            $this->reloadPost();
            $this->dispatchSuccessToast('Post unpublished.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Unpublish failed: '.$exception->getMessage());
        }
    }

    public function archive(ArchivePostAction $archive): void
    {
        $this->authorize('archive', $this->post);

        try {
            $archive->handle($this->post->fresh());
            $this->reloadPost();
            $this->dispatchSuccessToast('Post archived.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Archive failed: '.$exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUpdatePayload(): array
    {
        $userId = (int) auth()->id();

        return [
            'type' => $this->type,
            'category_id' => $this->categoryId,
            'visibility' => $this->visibility,
            'featured_image_id' => $this->featuredImageId,
            'is_featured' => $this->isFeatured,
            'is_breaking' => $this->isBreaking,
            'is_trending' => $this->isTrending,
            'is_editors_pick' => $this->isEditorsPick,
            'allow_comments' => $this->allowComments,
            'updated_by' => $userId,
            'translations' => $this->buildTranslationRows(),
            'tag_ids' => $this->tagIds,
        ];
    }

    /**
     * Convert the $translations dictionary into the row list shape
     * UpdatePostAction expects. Each row carries the language_id +
     * its editable fields. Rows flagged with delete=true are passed
     * through so the Action can drop them.
     *
     * The active tab's score is attached to its row only — the other
     * tabs preserve whatever score they had previously (we don't
     * rescore inactive tabs here since the form isn't bound to them).
     *
     * @return list<array<string, mixed>>
     */
    private function buildTranslationRows(): array
    {
        $rows = [];

        foreach ($this->translations as $languageId => $data) {
            $row = [
                'language_id' => $languageId,
                'title' => trim((string) ($data['title'] ?? '')),
                'slug' => trim((string) ($data['slug'] ?? '')) !== ''
                    ? (string) $data['slug']
                    : Str::slug((string) ($data['title'] ?? '')),
                'excerpt' => ((string) ($data['excerpt'] ?? '')) !== '' ? $data['excerpt'] : null,
                'content' => ((string) ($data['content'] ?? '')) !== '' ? $data['content'] : null,
                'meta_title' => ((string) ($data['meta_title'] ?? '')) !== '' ? $data['meta_title'] : null,
                'meta_description' => ((string) ($data['meta_description'] ?? '')) !== '' ? $data['meta_description'] : null,
                'focus_keyword' => ((string) ($data['focus_keyword'] ?? '')) !== '' ? $data['focus_keyword'] : null,
                'canonical_url' => ((string) ($data['canonical_url'] ?? '')) !== '' ? $data['canonical_url'] : null,
                'translation_status' => (string) ($data['translation_status'] ?? PostTranslation::TRANSLATION_STATUS_MANUAL),
            ];

            if ($languageId === $this->activeLanguageId) {
                $row['seo_score'] = $this->seoScore->overall;
            }

            if (! empty($data['delete'])) {
                $row['delete'] = true;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['required', \Illuminate\Validation\Rule::in(PostType::values())],
            'categoryId' => ['nullable', 'integer', 'exists:categories,id'],
            'tagIds' => ['array'],
            'tagIds.*' => ['integer', 'exists:tags,id'],
            'visibility' => ['required', \Illuminate\Validation\Rule::in(Post::VISIBILITIES)],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string'],
            'seoMetaTitle' => ['nullable', 'string', 'max:200'],
            'seoMetaDescription' => ['nullable', 'string', 'max:300'],
            'seoFocusKeyword' => ['nullable', 'string', 'max:120'],
            'seoCanonicalUrl' => ['nullable', 'url', 'max:500'],
            'seoRobots' => ['nullable', 'string', 'max:120'],
            'seoSchemaType' => ['nullable', \Illuminate\Validation\Rule::in(SeoMeta::SCHEMA_TYPES)],
        ];
    }

    private function reloadPost(): void
    {
        $this->post = $this->post->fresh([
            'translations',
            'tags',
            'editorialNotes.author',
            'revisions',
        ]);
    }

    // -------------------------------------------------------------------------
    // Computed properties for the view
    // -------------------------------------------------------------------------

    #[Computed]
    public function categories(): \Illuminate\Support\Collection
    {
        return Category::query()->orderBy('id')->limit(200)->get();
    }

    #[Computed]
    public function tags(): \Illuminate\Support\Collection
    {
        return Tag::query()->orderBy('id')->limit(500)->get();
    }

    #[Computed]
    public function languages(): \Illuminate\Support\Collection
    {
        return Language::query()->active()->ordered()->get();
    }

    #[Computed]
    public function editorialNotes(): \Illuminate\Support\Collection
    {
        return $this->post->editorialNotes()->with('author:id,name')->limit(20)->get();
    }

    #[Computed]
    public function revisionCount(): int
    {
        return $this->post->revisions()->count();
    }

    #[Computed]
    public function canPublish(): bool
    {
        return Gate::allows('publish', $this->post);
    }

    #[Computed]
    public function canApprove(): bool
    {
        return Gate::allows('approve', $this->post);
    }

    #[Computed]
    public function canReject(): bool
    {
        return Gate::allows('reject', $this->post);
    }

    #[Computed]
    public function canRequestChanges(): bool
    {
        return Gate::allows('requestChanges', $this->post);
    }

    #[Computed]
    public function canSubmitForReview(): bool
    {
        return Gate::allows('submitForReview', $this->post);
    }

    #[Computed]
    public function canArchive(): bool
    {
        return Gate::allows('archive', $this->post);
    }

    /**
     * Live SEO score — recomputed on every Livewire render against the
     * current (unsaved) form state so the gauge reflects what the user
     * has typed, not what's in the database.
     */
    #[Computed]
    public function seoScore(): SeoScoreResult
    {
        return app(SeoScoreService::class)->score(new SeoScoreInput(
            title: $this->title,
            slug: $this->slug,
            excerpt: $this->excerpt,
            content: $this->content,
            metaTitle: $this->seoMetaTitle,
            metaDescription: $this->seoMetaDescription,
            focusKeyword: $this->seoFocusKeyword,
        ));
    }

    /**
     * Schema.org type options for the SEO panel dropdown.
     *
     * @return list<string>
     */
    #[Computed]
    public function schemaTypeOptions(): array
    {
        return SeoMeta::SCHEMA_TYPES;
    }

    /**
     * Build the tab list for the translation tabs UI. Each entry:
     *   id      — language id
     *   code    — 'en', 'bn', etc.
     *   name    — display name
     *   flag    — emoji
     *   active  — bool
     *   percent — 0-100 completeness (title + content presence)
     *   status  — translation_status code
     *   deleted — true when the user marked it for removal
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function translationTabs(): array
    {
        $languages = $this->languages->keyBy('id');
        $tabs = [];

        foreach ($this->translations as $languageId => $row) {
            $language = $languages->get($languageId);

            if ($language === null) {
                continue;
            }

            $isActive = $languageId === $this->activeLanguageId;
            $data = $isActive
                ? array_merge($row, [
                    'title' => $this->title,
                    'content' => $this->content,
                    'excerpt' => $this->excerpt,
                    'meta_title' => $this->seoMetaTitle,
                ])
                : $row;

            $tabs[] = [
                'id' => (int) $languageId,
                'code' => $language->code,
                'name' => $language->name,
                'flag' => $language->flag_emoji ?? null,
                'active' => $isActive,
                'percent' => $this->computeTranslationPercent($data),
                'status' => (string) ($row['translation_status'] ?? PostTranslation::TRANSLATION_STATUS_MANUAL),
                'deleted' => ! empty($row['delete']),
                'is_default' => $languageId === $this->defaultLanguageId,
            ];
        }

        return $tabs;
    }

    /**
     * Languages that don't yet have a translation on this post.
     *
     * @return \Illuminate\Support\Collection<int, Language>
     */
    #[Computed]
    public function languagesAvailableToAdd(): \Illuminate\Support\Collection
    {
        $existingIds = array_keys($this->translations);

        return $this->languages->reject(fn (Language $lang): bool => in_array($lang->id, $existingIds, true))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function computeTranslationPercent(array $row): int
    {
        $weights = ['title' => 30, 'content' => 50, 'excerpt' => 10, 'meta_title' => 10];
        $total = 0;

        foreach ($weights as $key => $weight) {
            if (trim((string) ($row[$key] ?? '')) !== '') {
                $total += $weight;
            }
        }

        return $total;
    }

    // -------------------------------------------------------------------------
    // AI Assistant integration
    // -------------------------------------------------------------------------

    /**
     * Open the shared MediaPickerModal scoped to this post's featured
     * image slot. The picker dispatches `media.selected` back with the
     * same `target` so other pickers on the page don't react.
     */
    public function openFeaturedImagePicker(): void
    {
        $this->dispatch('media-picker.open', payload: [
            'target' => 'featured_image',
            'mime' => 'image/',
        ]);
    }

    public function clearFeaturedImage(): void
    {
        $this->featuredImageId = null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[On('media.selected')]
    public function onMediaSelected(array $payload): void
    {
        if (($payload['target'] ?? null) !== 'featured_image') {
            return;
        }

        $this->featuredImageId = isset($payload['mediaId']) ? (int) $payload['mediaId'] : null;
    }

    #[Computed]
    public function featuredImage(): ?Media
    {
        return $this->featuredImageId !== null
            ? Media::query()->find($this->featuredImageId)
            : null;
    }

    public function openAIAssistant(string $mode = 'article'): void
    {
        $this->dispatch('ai-assistant.open', payload: [
            'mode' => $mode,
            'locale' => $this->resolveCurrentLocaleCode(),
            'topic' => $this->title,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'focus_keyword' => '',
            'paragraph' => '',
        ]);
    }

    #[On('ai.article-generated')]
    public function applyGeneratedArticle(string $content, string $strategy = 'replace'): void
    {
        if ($content === '') {
            return;
        }

        $this->content = $strategy === 'append'
            ? trim((string) ($this->content."\n\n".$content))
            : $content;

        $this->broadcastContentRefresh();
        $this->dispatch('toast.success', message: 'AI article inserted.');
    }

    #[On('ai.rewrite-completed')]
    public function applyRewrite(string $content, string $strategy = 'replace'): void
    {
        if ($content === '') {
            return;
        }

        $this->content = $strategy === 'append'
            ? trim((string) ($this->content."\n\n".$content))
            : $content;

        $this->broadcastContentRefresh();
        $this->dispatch('toast.success', message: 'Rewrite applied.');
    }

    /**
     * Apply AI-generated SEO metadata into the SEO panel state. Empty
     * fields in the payload are skipped so re-running the AI never
     * blanks out manually-curated entries.
     *
     * @param  array<string, mixed>  $payload  meta_title, meta_description, meta_keywords, focus_keyword, slug
     */
    #[On('ai.seo-generated')]
    public function applyGeneratedSeoMeta(array $payload): void
    {
        if (! empty($payload['slug'])) {
            $this->slug = (string) $payload['slug'];
        }

        if (! empty($payload['meta_title'])) {
            $this->seoMetaTitle = (string) $payload['meta_title'];
        }

        if (! empty($payload['meta_description'])) {
            $this->seoMetaDescription = (string) $payload['meta_description'];
        }

        // The new SEOMetaResult exposes `tags` (replacing the old
        // `meta_keywords`). Either key name is tolerated so older
        // integrations keep working — we just join into a comma list
        // for the legacy `seoMetaKeywords` field.
        $keywords = $payload['tags'] ?? $payload['meta_keywords'] ?? null;
        if (! empty($keywords)) {
            $this->seoMetaKeywords = is_array($keywords)
                ? implode(', ', array_map('strval', $keywords))
                : (string) $keywords;
        }

        // New: focus_keyphrase (preferred) supersedes legacy focus_keyword.
        $kp = $payload['focus_keyphrase'] ?? $payload['focus_keyword'] ?? null;
        if (! empty($kp)) {
            $this->seoFocusKeyword = (string) $kp;
        }

        $this->dispatch('toast.success', message: 'SEO metadata applied.');
    }

    private function resolveCurrentLocaleCode(): string
    {
        $language = $this->defaultLanguageId !== null
            ? Language::query()->find($this->defaultLanguageId)
            : Language::query()->default()->first();

        return (string) ($language?->code ?? 'en');
    }

    public function render(): View
    {
        return view('livewire.admin.posts.edit');
    }

    protected function dispatchSuccessToast(string $message): void
    {
        session()->flash('success', $message);
        $this->dispatch('toast.success', message: $message);
    }

    protected function dispatchDangerToast(string $message): void
    {
        session()->flash('danger', $message);
        $this->dispatch('toast.danger', message: $message);
    }
}
