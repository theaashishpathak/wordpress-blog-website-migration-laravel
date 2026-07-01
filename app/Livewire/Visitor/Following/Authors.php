<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Following;

use App\Actions\Visitor\Follow\ToggleAuthorFollowAction;
use App\Models\AuthorFollow;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.visitor')]
#[Title('Followed Authors')]
class Authors extends Component
{
    use WithPagination;

    /**
     * @return LengthAwarePaginator<AuthorFollow>
     */
    #[Computed]
    public function follows(): LengthAwarePaginator
    {
        return AuthorFollow::query()
            ->where('follower_id', auth()->id())
            ->with('author')
            ->latest()
            ->paginate(15);
    }

    public function unfollow(int $authorId): void
    {
        $author = User::query()->findOrFail($authorId);
        app(ToggleAuthorFollowAction::class)->handle(auth()->user(), $author);

        unset($this->follows);
        $this->dispatch('toast', message: 'Unfollowed.');
    }

    public function toggleNotify(int $id): void
    {
        $follow = AuthorFollow::query()
            ->where('follower_id', auth()->id())
            ->findOrFail($id);

        $follow->update(['notify_on_publish' => ! $follow->notify_on_publish]);

        unset($this->follows);
        $this->dispatch('toast', message: $follow->notify_on_publish ? 'Notifications on.' : 'Muted notifications.');
    }

    public function render(): View
    {
        return view('livewire.visitor.following.authors');
    }
}
