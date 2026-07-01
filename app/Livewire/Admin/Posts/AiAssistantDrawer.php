<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Posts;

use App\Actions\AI\GenerateArticleAction;
use App\Actions\AI\GenerateSEOMetaAction;
use App\Actions\AI\RewriteParagraphAction;
use App\Services\AI\DataTransferObjects\SEOMetaResult;
use App\Services\AI\Exceptions\AIProviderException;
use App\Services\AI\Exceptions\QuotaExceededException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

/**
 * Slide-over drawer that wires Phase 3 AI Actions into the Posts admin UI.
 *
 * Workflow:
 *   1. Parent component (Create/Edit) dispatches 'ai-assistant.open' with
 *      a mode ('article' | 'seo' | 'rewrite').
 *   2. This drawer opens, user fills inputs, clicks Generate.
 *   3. Action runs (with current authenticated user attribution).
 *   4. Drawer dispatches a payload event back up:
 *        'ai.article-generated'  → Create/Edit appends/inserts into $content
 *        'ai.seo-generated'      → Create/Edit fills meta fields
 *        'ai.rewrite-completed'  → Create/Edit replaces / appends $content
 *   5. Drawer closes (parent can re-open with the result preview if desired).
 *
 * The drawer NEVER instantiates a provider directly — every call goes
 * through the Action layer which in turn goes through AIManager. The arch
 * test enforces this boundary.
 */
class AiAssistantDrawer extends Component
{
    public bool $open = false;

    /**
     * Active mode: 'article' | 'seo' | 'rewrite'.
     */
    public string $mode = 'article';

    // --- Article inputs ---
    public string $topic = '';

    public string $tone = 'professional';

    public int $wordCount = 800;

    public string $audience = 'general readers';

    public string $focusKeyword = '';

    // --- SEO inputs ---
    public string $seoTitle = '';

    public string $seoExcerpt = '';

    public string $seoFocusKeyword = '';

    // --- Rewrite inputs ---
    public string $paragraph = '';

    public string $rewriteTone = 'professional';

    // --- Output state ---
    public string $output = '';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $seoOutput = null;

    public bool $isGenerating = false;

    public string $locale = 'en';

    /**
     * Parent (Create.php or Edit.php) emits this event to open the drawer
     * in a particular mode and optionally seed inputs.
     *
     * @param  array<string, mixed>  $payload
     */
    #[On('ai-assistant.open')]
    public function openFor(array $payload = []): void
    {
        $this->reset(['output', 'seoOutput', 'isGenerating']);

        $this->mode = (string) ($payload['mode'] ?? 'article');
        $this->locale = (string) ($payload['locale'] ?? 'en');

        // Seed inputs from the host post when supplied.
        if (isset($payload['topic'])) {
            $this->topic = (string) $payload['topic'];
        }
        if (isset($payload['title'])) {
            $this->seoTitle = (string) $payload['title'];
        }
        if (isset($payload['excerpt'])) {
            $this->seoExcerpt = (string) $payload['excerpt'];
        }
        if (isset($payload['focus_keyword'])) {
            $this->focusKeyword = (string) $payload['focus_keyword'];
            $this->seoFocusKeyword = (string) $payload['focus_keyword'];
        }
        if (isset($payload['paragraph'])) {
            $this->paragraph = (string) $payload['paragraph'];
        }

        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['output', 'seoOutput', 'isGenerating']);
    }

    public function generateArticle(GenerateArticleAction $action): void
    {
        abort_unless(auth()->user()?->can('ai.use_writer') ?? false, 403);

        $topic = trim($this->topic);

        if ($topic === '') {
            $this->dispatchDangerToast('Topic is required to generate an article.');

            return;
        }

        $this->isGenerating = true;
        $this->output = '';

        try {
            $this->output = $action->handle(
                topic: $topic,
                locale: $this->locale,
                tone: $this->tone,
                wordCount: max(100, min(3000, $this->wordCount)),
                audience: $this->audience !== '' ? $this->audience : 'general readers',
                focusKeyword: $this->focusKeyword,
                userId: auth()->id(),
            );

            $this->dispatchSuccessToast('Article generated.');
        } catch (QuotaExceededException $e) {
            $this->dispatchDangerToast('Monthly AI quota exceeded. Contact admin to raise the cap.');
        } catch (AIProviderException $e) {
            $this->dispatchDangerToast('AI generation failed: '.$e->getMessage());
        } catch (Throwable $e) {
            report($e);
            $this->dispatchDangerToast('Unexpected error during generation.');
        } finally {
            $this->isGenerating = false;
        }
    }

    public function generateSeoMeta(GenerateSEOMetaAction $action): void
    {
        abort_unless(auth()->user()?->can('ai.use_seo') ?? false, 403);

        $title = trim($this->seoTitle);

        if ($title === '') {
            $this->dispatchDangerToast('Title is required to generate SEO meta.');

            return;
        }

        $this->isGenerating = true;
        $this->seoOutput = null;

        try {
            /** @var SEOMetaResult $result */
            $result = $action->handle(
                title: $title,
                excerpt: $this->seoExcerpt,
                focusKeyword: $this->seoFocusKeyword,
                locale: $this->locale,
                userId: auth()->id(),
            );

            $this->seoOutput = $result->toArray();
            $this->dispatchSuccessToast('SEO metadata generated.');
        } catch (QuotaExceededException $e) {
            $this->dispatchDangerToast('Monthly AI quota exceeded. Contact admin to raise the cap.');
        } catch (AIProviderException $e) {
            $this->dispatchDangerToast('AI generation failed: '.$e->getMessage());
        } catch (Throwable $e) {
            report($e);
            $this->dispatchDangerToast('Unexpected error during generation.');
        } finally {
            $this->isGenerating = false;
        }
    }

    public function rewriteParagraph(RewriteParagraphAction $action): void
    {
        abort_unless(auth()->user()?->can('ai.use_rewrite') ?? false, 403);

        $paragraph = trim($this->paragraph);

        if ($paragraph === '') {
            $this->dispatchDangerToast('Paste a paragraph to rewrite.');

            return;
        }

        $this->isGenerating = true;
        $this->output = '';

        try {
            $this->output = $action->handle(
                paragraph: $paragraph,
                tone: $this->rewriteTone,
                locale: $this->locale,
                userId: auth()->id(),
            );

            $this->dispatchSuccessToast('Paragraph rewritten.');
        } catch (QuotaExceededException $e) {
            $this->dispatchDangerToast('Monthly AI quota exceeded. Contact admin to raise the cap.');
        } catch (AIProviderException $e) {
            $this->dispatchDangerToast('AI generation failed: '.$e->getMessage());
        } catch (Throwable $e) {
            report($e);
            $this->dispatchDangerToast('Unexpected error during generation.');
        } finally {
            $this->isGenerating = false;
        }
    }

    /**
     * User clicked "Insert into Editor" — push the generated content into
     * the parent component and close the drawer.
     */
    public function applyToEditor(string $strategy = 'replace'): void
    {
        if ($this->mode === 'seo') {
            if ($this->seoOutput === null) {
                return;
            }

            $this->dispatch('ai.seo-generated', payload: $this->seoOutput);
        } else {
            if ($this->output === '') {
                return;
            }

            if ($this->mode === 'rewrite') {
                $this->dispatch('ai.rewrite-completed', content: $this->output, strategy: $strategy);
            } else {
                $this->dispatch('ai.article-generated', content: $this->output, strategy: $strategy);
            }
        }

        $this->close();
    }

    public function regenerate(): void
    {
        $this->output = '';
        $this->seoOutput = null;
    }

    public function render(): View
    {
        return view('livewire.admin.posts.ai-assistant-drawer');
    }

    protected function dispatchSuccessToast(string $message): void
    {
        $this->dispatch('toast.success', message: $message);
    }

    protected function dispatchDangerToast(string $message): void
    {
        $this->dispatch('toast.danger', message: $message);
    }
}
