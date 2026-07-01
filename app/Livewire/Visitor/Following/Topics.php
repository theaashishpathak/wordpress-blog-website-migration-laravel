<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Following;

use App\Actions\Visitor\Follow\ToggleTopicFollowAction;
use App\Models\Category;
use App\Models\Tag;
use App\Models\TopicFollow;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.visitor')]
#[Title('Followed Topics')]
class Topics extends Component
{
    use WithPagination;

    /** 'all' | 'tag' | 'category' */
    #[Url(as: 'kind')]
    public string $filter = 'all';

    /**
     * @return LengthAwarePaginator<TopicFollow>
     */
    #[Computed]
    public function follows(): LengthAwarePaginator
    {
        $query = TopicFollow::query()
            ->where('user_id', auth()->id())
            ->with('followable')
            ->latest();

        if ($this->filter === 'tag') {
            $query->where('followable_type', Tag::class);
        } elseif ($this->filter === 'category') {
            $query->where('followable_type', Category::class);
        }

        return $query->paginate(15);
    }

    public function switchFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'tag', 'category'], true) ? $filter : 'all';
        $this->resetPage();
    }

    public function unfollow(int $id): void
    {
        $follow = TopicFollow::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $followable = $follow->followable;
        if ($followable) {
            app(ToggleTopicFollowAction::class)->handle(auth()->user(), $followable);
        } else {
            $follow->delete();
        }

        unset($this->follows);
        $this->dispatch('toast', message: 'Unfollowed.');
    }

    public function toggleNotify(int $id): void
    {
        $follow = TopicFollow::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $follow->update(['notify_on_post' => ! $follow->notify_on_post]);

        unset($this->follows);
        $this->dispatch('toast', message: $follow->notify_on_post ? 'Notifications on.' : 'Muted notifications.');
    }

    public function render(): View
    {
        return view('livewire.visitor.following.topics', [
            'counts' => [
                'all' => TopicFollow::query()->where('user_id', auth()->id())->count(),
                'tag' => TopicFollow::query()->where('user_id', auth()->id())->where('followable_type', Tag::class)->count(),
                'category' => TopicFollow::query()->where('user_id', auth()->id())->where('followable_type', Category::class)->count(),
            ],
        ]);
    }
}
