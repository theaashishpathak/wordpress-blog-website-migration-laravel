<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Following;

use App\Actions\Visitor\Follow\ToggleUserFollowAction;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.visitor')]
#[Title('Followed Readers')]
class Users extends Component
{
    use WithPagination;

    /** 'following' = visitors I follow | 'followers' = visitors who follow me */
    #[Url(as: 'tab')]
    public string $direction = 'following';

    /**
     * @return LengthAwarePaginator<UserFollow>
     */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = UserFollow::query();

        if ($this->direction === 'followers') {
            $query->where('followed_id', auth()->id())->with('follower');
        } else {
            $query->where('follower_id', auth()->id())->with('followed');
        }

        return $query->latest()->paginate(15);
    }

    public function unfollow(int $userId): void
    {
        $target = User::query()->findOrFail($userId);
        app(ToggleUserFollowAction::class)->handle(auth()->user(), $target);

        unset($this->rows);
        $this->dispatch('toast', message: 'Unfollowed.');
    }

    public function switchDirection(string $direction): void
    {
        $this->direction = in_array($direction, ['following', 'followers'], true) ? $direction : 'following';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.visitor.following.users', [
            'counts' => [
                'following' => auth()->user()->following()->count(),
                'followers' => auth()->user()->followers()->count(),
            ],
        ]);
    }
}
