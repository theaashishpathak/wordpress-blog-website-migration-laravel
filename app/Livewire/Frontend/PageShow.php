<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('frontend.layouts.app')]
class PageShow extends Component
{
    public Page $page;

    public PageTranslation $translation;

    /**
     * Resolve the page from the URL slug + current locale. We look up the
     * translation row first (because slug is per-language) then load the
     * parent page. 404 if no published translation matches.
     *
     * Both params are optional so unit tests can construct the component
     * with $page / $translation already populated.
     */
    public function mount(?string $locale = null, ?string $slug = null): void
    {
        if (isset($this->page, $this->translation)) {
            return;
        }

        abort_if($slug === null, 404);

        $languageId = app(\App\Support\LocaleResolver::class)->current()?->id;

        $translation = PageTranslation::query()
            ->with('page')
            ->where('slug', $slug)
            ->when($languageId !== null, fn ($q) => $q->where('language_id', $languageId))
            ->where('is_published', true)
            ->first();

        abort_if($translation === null || ! $translation->page?->isPublished(), 404);

        $this->page = $translation->page;
        $this->translation = $translation;
    }

    public function render(): View
    {
        return view('livewire.frontend.page-show', [
            'metaTitle' => $this->translation->meta_title ?: $this->translation->title,
            'metaDescription' => $this->translation->meta_description
                ?: \Illuminate\Support\Str::limit(strip_tags((string) $this->translation->content), 160),
        ]);
    }
}
