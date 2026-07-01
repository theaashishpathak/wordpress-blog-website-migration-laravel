<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Bookmarks;

use App\Actions\Visitor\Bookmark\ToggleBookmarkAction;
use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.visitor')]
#[Title('My Bookmarks')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    /**
     * @return LengthAwarePaginator<Bookmark>
     */
    #[Computed]
    public function bookmarks(): LengthAwarePaginator
    {
        return Bookmark::query()
            ->where('user_id', auth()->id())
            ->with([
                'post.translations',
                'post.author:id,name',
                'post.featuredImage:id,disk,path,mime_type,alt_text',
                'post.category.translations',
            ])
            ->when($this->search !== '', function ($query): void {
                $query->whereHas('post.translations', function ($q): void {
                    $q->where('title', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(12);
    }

    public function unbookmark(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);
        app(ToggleBookmarkAction::class)->handle(auth()->user(), $post);

        unset($this->bookmarks);
        $this->dispatch('toast', message: 'Removed from bookmarks');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.visitor.bookmarks.index');
    }
}
