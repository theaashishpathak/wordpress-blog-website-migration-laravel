<?php

declare(strict_types=1);

namespace App\Livewire\Admin\AI;

use App\Actions\AI\GenerateArticleAction;
use App\Actions\Post\CreatePostAction;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Standalone AI Writer — full-page editor for spinning up new articles
 * without picking a specific post first. The output can be:
 *   - copied to clipboard (purely client-side)
 *   - saved as a fresh draft Post in any category
 */
#[Layout('layouts.app')]
#[Title('AI Writer')]
class WriterStudio extends Component
{
    public string $topic = '';

    public string $tone = 'professional';

    public int $wordCount = 800;

    public string $audience = 'general readers';

    public string $focusKeyword = '';

    public ?int $languageId = null;

    public ?int $categoryId = null;

    public string $output = '';

    public bool $isGenerating = false;

    public string $generatedTitle = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('ai.use_writer') ?? false,
            403,
            'You do not have access to the AI Writer.',
        );

        $defaultLang = Language::query()->default()->first();
        $this->languageId = $defaultLang?->id;
    }

    /**
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languages(): Collection
    {
        return Language::query()->active()->ordered()->get();
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->with('translations')->ordered()->limit(200)->get();
    }

    #[Computed]
    public function canSaveAsDraft(): bool
    {
        return Gate::allows('posts.create');
    }

    public function generate(GenerateArticleAction $action): void
    {
        $this->authorize('ai.use_writer');

        $this->validate([
            'topic' => ['required', 'string', 'min:5', 'max:200'],
            'wordCount' => ['integer', 'min:100', 'max:5000'],
            'languageId' => ['required', 'integer', 'exists:languages,id'],
        ]);

        $language = Language::query()->find($this->languageId);

        $this->isGenerating = true;

        try {
            $body = $action->handle(
                topic: $this->topic,
                locale: (string) ($language?->code ?? 'en'),
                tone: $this->tone,
                wordCount: $this->wordCount,
                audience: $this->audience,
                focusKeyword: $this->focusKeyword,
                userId: (int) (auth()->id() ?? 0),
            );

            $this->output = $body;
            $this->generatedTitle = $this->extractHeadline($body) ?: $this->topic;

            $this->dispatch('toast.success', message: 'Article generated. Review before saving.');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast.danger', message: 'Generation failed: '.$e->getMessage());
        } finally {
            $this->isGenerating = false;
        }
    }

    /**
     * Save the generated output as a fresh draft Post.
     */
    public function saveAsDraft(CreatePostAction $createPost): void
    {
        $this->authorize('posts.create');

        if (trim($this->output) === '') {
            $this->dispatch('toast.danger', message: 'Nothing to save yet — generate an article first.');

            return;
        }

        $title = trim($this->generatedTitle) !== '' ? $this->generatedTitle : Str::limit($this->topic, 80, '');

        try {
            $post = $createPost->handle([
                'type' => PostType::Post->value,
                'status' => PostStatus::Draft->value,
                'category_id' => $this->categoryId,
                'author_id' => (int) auth()->id(),
                'default_language_id' => $this->languageId,
                'visibility' => Post::VISIBILITY_PUBLIC,
                'translations' => [[
                    'language_id' => $this->languageId,
                    'title' => $title,
                    'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
                    'content' => $this->output,
                    'translation_status' => 'ai_generated',
                ]],
                'created_by' => (int) auth()->id(),
            ]);

            $this->dispatch('toast.success', message: 'Draft saved. Opening the editor…');
            $this->redirect(route('admin.posts.edit', $post), navigate: true);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast.danger', message: 'Save failed: '.$e->getMessage());
        }
    }

    /**
     * Pull the first H1 / H2 / first line as a best-effort headline.
     */
    private function extractHeadline(string $body): string
    {
        if (preg_match('/<h[12][^>]*>(.*?)<\/h[12]>/i', $body, $m)) {
            return trim(strip_tags($m[1]));
        }

        $firstLine = strtok(strip_tags($body), "\n");

        return trim((string) $firstLine);
    }

    public function render(): View
    {
        return view('livewire.admin.ai.writer-studio');
    }
}
