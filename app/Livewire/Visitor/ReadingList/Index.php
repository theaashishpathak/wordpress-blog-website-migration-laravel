<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\ReadingList;

use App\Actions\Visitor\ReadingList\ToggleReadingListAction;
use App\Models\Post;
use App\Models\ReadingListItem;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.visitor')]
#[Title('Reading List')]
class Index extends Component
{
    use WithPagination;

    /** Filter: 'active' (queue) | 'dismissed' (archive). */
    #[Url(as: 'view')]
    public string $filter = 'active';

    /**
     * @return LengthAwarePaginator<ReadingListItem>
     */
    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $query = ReadingListItem::query()
            ->where('user_id', auth()->id())
            ->with([
                'post.translations',
                'post.author:id,name',
                'post.featuredImage:id,disk,path,mime_type,alt_text',
                'post.category.translations',
            ]);

        if ($this->filter === 'dismissed') {
            $query->dismissed()->latest('dismissed_at');
        } else {
            $query->active()->latest('added_at');
        }

        return $query->paginate(12);
    }

    public function dismiss(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);
        app(ToggleReadingListAction::class)->handle(auth()->user(), $post);

        unset($this->items);
        $this->dispatch('toast', message: 'Dismissed from Reading List');
    }

    public function restore(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);
        app(ToggleReadingListAction::class)->handle(auth()->user(), $post);

        unset($this->items);
        $this->dispatch('toast', message: 'Back on your Reading List');
    }

    public function switchFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['active', 'dismissed'], true) ? $filter : 'active';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.visitor.reading-list.index');
    }
}
