<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Posts;

use App\Actions\Editorial\SubmitForReviewAction;
use App\Actions\Post\CreatePostAction;
use App\Actions\Post\PublishPostAction;
use App\Actions\Seo\UpdateSeoMetaAction;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Category;
use App\Models\Language;
use App\Models\Media;
use App\Models\Post;
use App\Models\SeoMeta;
use App\Models\Tag;
use App\Services\Seo\DataTransferObjects\SeoScoreInput;
use App\Services\Seo\DataTransferObjects\SeoScoreResult;
use App\Services\Seo\SeoScoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Create Post')]
class Create extends Component
{
    public string $title = '';

    public string $slug = '';

    public bool $slugManuallyEdited = false;

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
     * Featured image — set by MediaPickerModal.
     */
    public ?int $featuredImageId = null;

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

    public function mount(): void
    {
        $this->authorize('create', Post::class);

        $this->defaultLanguageId = Language::query()->default()->first()?->id;
        $this->type = PostType::Post->value;
    }

    public function updatedTitle(string $value): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug(string $value): void
    {
        $this->slugManuallyEdited = trim($value) !== '';
    }

    public function saveDraft(CreatePostAction $createPost, UpdateSeoMetaAction $updateSeo): void
    {
        $this->persistAndRedirect($createPost, $updateSeo, status: PostStatus::Draft);
    }

    public function saveAndSubmit(
        CreatePostAction $createPost,
        UpdateSeoMetaAction $updateSeo,
        SubmitForReviewAction $submitForReview,
    ): void {
        $post = $this->persistOnly($createPost, $updateSeo, status: PostStatus::Draft);

        if ($post === null) {
            return;
        }

        try {
            $submitForReview->handle($post, auth()->user(), note: null);
            $this->dispatchSuccessToast('Post submitted for editorial review.');
            $this->redirectToEdit($post);
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Saved draft, but submitting for review failed: '.$exception->getMessage());
            $this->redirectToEdit($post);
        }
    }

    public function savePublish(
        CreatePostAction $createPost,
        UpdateSeoMetaAction $updateSeo,
        PublishPostAction $publishPost,
    ): void {
        // PostPolicy::publish requires a Post instance — we don't have one
        // yet at create time, so check the raw permission directly. Once
        // the post is created we delegate publishing to PublishPostAction
        // which has its own state-machine guards.
        abort_unless(
            auth()->user()?->can('posts.publish') ?? false,
            403,
            'You do not have permission to publish posts.',
        );

        $post = $this->persistOnly($createPost, $updateSeo, status: PostStatus::Draft);

        if ($post === null) {
            return;
        }

        try {
            $publishPost->handle($post, cascadeTranslations: true, allowDirectPublish: true);
            $this->dispatchSuccessToast('Post created and published.');
            $this->redirectToEdit($post->fresh());
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Saved draft, but publishing failed: '.$exception->getMessage());
            $this->redirectToEdit($post);
        }
    }

    /**
     * @return Post|null  null when validation aborts
     */
    private function persistOnly(
        CreatePostAction $createPost,
        UpdateSeoMetaAction $updateSeo,
        PostStatus $status,
    ): ?Post {
        $this->validate($this->rules());

        try {
            $post = $createPost->handle($this->buildPayload($status));

            $updateSeo->handle(
                seoable: $post,
                languageId: $this->defaultLanguageId,
                data: $this->buildAdvancedSeoPayload(),
            );

            return $post;
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Failed to create post: '.$exception->getMessage());

            return null;
        }
    }

    private function persistAndRedirect(
        CreatePostAction $createPost,
        UpdateSeoMetaAction $updateSeo,
        PostStatus $status,
    ): void {
        $post = $this->persistOnly($createPost, $updateSeo, $status);

        if ($post === null) {
            return;
        }

        $this->dispatchSuccessToast('Draft saved.');
        $this->redirectToEdit($post);
    }

    /**
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

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(PostStatus $status): array
    {
        $userId = (int) auth()->id();

        return [
            'type' => $this->type,
            'author_id' => $userId,
            'category_id' => $this->categoryId,
            'default_language_id' => $this->defaultLanguageId,
            'status' => $status,
            'visibility' => $this->visibility,
            'featured_image_id' => $this->featuredImageId,
            'is_featured' => $this->isFeatured,
            'is_breaking' => $this->isBreaking,
            'is_trending' => $this->isTrending,
            'is_editors_pick' => $this->isEditorsPick,
            'allow_comments' => $this->allowComments,
            'created_by' => $userId,
            'updated_by' => $userId,
            'translations' => [
                [
                    'language_id' => $this->defaultLanguageId,
                    'title' => trim($this->title),
                    'slug' => trim($this->slug) !== '' ? $this->slug : Str::slug($this->title),
                    'excerpt' => $this->excerpt !== '' ? $this->excerpt : null,
                    'content' => $this->content !== '' ? $this->content : null,
                    'meta_title' => $this->seoMetaTitle !== '' ? $this->seoMetaTitle : null,
                    'meta_description' => $this->seoMetaDescription !== '' ? $this->seoMetaDescription : null,
                    'focus_keyword' => $this->seoFocusKeyword !== '' ? $this->seoFocusKeyword : null,
                    'canonical_url' => $this->seoCanonicalUrl !== '' ? $this->seoCanonicalUrl : null,
                    'seo_score' => $this->seoScore->overall,
                ],
            ],
            'tag_ids' => $this->tagIds,
        ];
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
            'defaultLanguageId' => ['required', 'integer', 'exists:languages,id'],
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

    private function redirectToEdit(Post $post): void
    {
        $this->redirect(route('admin.posts.edit', $post), navigate: true);
    }

    #[Computed]
    public function categories(): \Illuminate\Support\Collection
    {
        return Category::query()->orderBy('id')->limit(200)->get();
    }

    #[Computed]
    public function tags(): \Illuminate\Support\Collection
    {
        return Tag::query()->published()->orderBy('id')->limit(500)->get();
    }

    #[Computed]
    public function languages(): \Illuminate\Support\Collection
    {
        return Language::query()->active()->ordered()->get();
    }

    #[Computed]
    public function canPublishDirectly(): bool
    {
        // Raw permission check — no Post instance exists at create time.
        return auth()->user()?->can('posts.publish') ?? false;
    }

    /**
     * Live SEO score — recomputed on every Livewire render against the
     * current (unsaved) form state.
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
     * @return list<string>
     */
    #[Computed]
    public function schemaTypeOptions(): array
    {
        return SeoMeta::SCHEMA_TYPES;
    }

    // -------------------------------------------------------------------------
    // AI Assistant integration
    // -------------------------------------------------------------------------

    /**
     * Open the shared MediaPickerModal scoped to this post's featured
     * image slot.
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

    /**
     * Open the AI Assistant drawer in the requested mode, seeded with
     * the current form values so the user doesn't have to retype them.
     */
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

        $this->js("window.dispatchEvent(new CustomEvent('editor:set-content', { detail: { content: ".json_encode($this->content)." } }))");
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

        $this->js("window.dispatchEvent(new CustomEvent('editor:set-content', { detail: { content: ".json_encode($this->content)." } }))");
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
            $this->slugManuallyEdited = true;
        }

        if (! empty($payload['meta_title'])) {
            $this->seoMetaTitle = (string) $payload['meta_title'];
        }

        if (! empty($payload['meta_description'])) {
            $this->seoMetaDescription = (string) $payload['meta_description'];
        }

        // Accept both new ('tags') and legacy ('meta_keywords') payload keys.
        $keywords = $payload['tags'] ?? $payload['meta_keywords'] ?? null;
        if (! empty($keywords)) {
            $this->seoMetaKeywords = is_array($keywords)
                ? implode(', ', array_map('strval', $keywords))
                : (string) $keywords;
        }

        // Accept both new ('focus_keyphrase') and legacy ('focus_keyword').
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
        return view('livewire.admin.posts.create');
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
