<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\ReadingHistory;

use App\Models\ReadingHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.visitor')]
#[Title('Reading History')]
class Index extends Component
{
    use WithPagination;

    public function clear(int $postId): void
    {
        ReadingHistory::query()
            ->where('user_id', auth()->id())
            ->where('post_id', $postId)
            ->delete();

        unset($this->history);
        $this->dispatch('toast', message: 'Removed from history');
    }

    /**
     * @return LengthAwarePaginator<ReadingHistory>
     */
    #[Computed]
    public function history(): LengthAwarePaginator
    {
        return ReadingHistory::query()
            ->where('user_id', auth()->id())
            ->with([
                'post.translations',
                'post.author:id,name',
                'post.featuredImage:id,disk,path,mime_type,alt_text',
                'post.category.translations',
            ])
            ->orderByDesc('last_read_at')
            ->paginate(20);
    }

    public function render(): View
    {
        // Pre-compute date-grouped list for the view (so the template stays simple)
        $grouped = $this->history->getCollection()->groupBy(
            fn (ReadingHistory $h) => $h->last_read_at?->format('Y-m-d') ?? 'unknown'
        );

        return view('livewire.visitor.reading-history.index', [
            'grouped' => $grouped,
        ]);
    }
}
