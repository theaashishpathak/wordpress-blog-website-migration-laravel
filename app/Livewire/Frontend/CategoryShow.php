<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('frontend.layouts.app')]
class CategoryShow extends Component
{
    public Category $category;

    /**
     * Resolve the category from the URL slug + current locale. Translation
     * tables store slugs per language so we look up via category_translations
     * first, then fall back to a model lookup by ID. 404 if neither matches.
     *
     * Both params are optional so unit tests can construct the component
     * with $category pre-populated (Livewire::test passes it directly into
     * public properties); in that case we skip the slug lookup.
     */
    public function mount(?string $locale = null, ?string $slug = null): void
    {
        if (isset($this->category)) {
            return;
        }

        abort_if($slug === null, 404);

        $languageId = app(\App\Support\LocaleResolver::class)->current()?->id;

        $categoryId = \DB::table('category_translations')
            ->where('slug', $slug)
            ->when($languageId !== null, fn ($q) => $q->where('language_id', $languageId))
            ->value('category_id');

        abort_if($categoryId === null, 404);

        $this->category = Category::query()->findOrFail($categoryId);
    }

    /**
     * @return LengthAwarePaginator<Post>
     */
    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return Post::query()
            ->with(['translations', 'author:id,name', 'featuredImage:id,disk,path,mime_type,alt_text', 'category:id,icon', 'category.translations'])
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->where('category_id', $this->category->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate(perPage: 12);
    }

    public function render(): View
    {
        $name = $this->category->translate('name') ?? '#'.$this->category->id;

        return view('livewire.frontend.category-show', [
            'metaTitle' => $name,
            'metaDescription' => $this->category->translate('description')
                ?: 'Latest articles in '.$name,
        ]);
    }
}
