<?php

declare(strict_types=1);

namespace App\Livewire\Admin\AI;

use App\Actions\AI\GenerateSEOMetaAction;
use App\Models\Language;
use App\Services\AI\DataTransferObjects\SEOMetaResult;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * SEO Generator Studio — standalone page that turns a title + excerpt
 * (and optional focus keyword) into a full SEO meta bundle (meta title,
 * meta description, keywords, slug, JSON-LD type hint).
 */
#[Layout('layouts.app')]
#[Title('SEO Generator')]
class SeoGeneratorStudio extends Component
{
    public string $title = '';

    public string $excerpt = '';

    public string $focusKeyword = '';

    public ?int $languageId = null;

    public bool $isGenerating = false;

    public ?SEOMetaResult $result = null;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('ai.use_seo') ?? false,
            403,
            'You do not have access to the SEO Generator.',
        );

        $this->languageId = Language::query()->default()->value('id');
    }

    /**
     * @return Collection<int, Language>
     */
    #[Computed]
    public function languages(): Collection
    {
        return Language::query()->active()->ordered()->get();
    }

    public function generate(GenerateSEOMetaAction $action): void
    {
        $this->authorize('ai.use_seo');

        $this->validate([
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'focusKeyword' => ['nullable', 'string', 'max:120'],
            'languageId' => ['required', 'integer', 'exists:languages,id'],
        ]);

        $language = Language::query()->find($this->languageId);
        $this->isGenerating = true;
        $this->result = null;

        try {
            $this->result = $action->handle(
                title: $this->title,
                excerpt: $this->excerpt,
                focusKeyword: $this->focusKeyword,
                locale: (string) ($language?->code ?? 'en'),
                userId: (int) (auth()->id() ?? 0),
            );

            $this->dispatch('toast.success', message: 'SEO meta generated.');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast.danger', message: 'Generation failed: '.$e->getMessage());
        } finally {
            $this->isGenerating = false;
        }
    }

    public function render(): View
    {
        return view('livewire.admin.ai.seo-generator-studio');
    }
}
