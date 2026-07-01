<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('frontend.layouts.app')]
class Search extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    public function updatingQuery(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<Post>
     */
    #[Computed]
    public function results(): LengthAwarePaginator
    {
        if (trim($this->query) === '') {
            return Post::query()->whereRaw('1 = 0')->paginate(perPage: 12);
        }

        $term = '%'.trim($this->query).'%';

        return Post::query()
            ->with(['translations', 'author:id,name', 'featuredImage:id,disk,path,mime_type,alt_text', 'category:id,icon', 'category.translations'])
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('translations', function ($q) use ($term): void {
                $q->where('title', 'like', $term)
                    ->orWhere('excerpt', 'like', $term)
                    ->orWhere('content', 'like', $term);
            })
            ->orderByDesc('published_at')
            ->paginate(perPage: 12);
    }

    public function render(): View
    {
        return view('livewire.frontend.search', [
            'metaTitle' => trim($this->query) !== '' ? 'Search: '.$this->query : 'Search',
        ]);
    }
}
