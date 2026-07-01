<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Notifications')]
class NotificationsIndex extends Component
{
    use WithPagination;

    /** @var string all|unread|read */
    #[Url(as: 'filter', except: 'all')]
    public string $filter = 'all';

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'unread', 'read'], true) ? $filter : 'all';
        $this->resetPage();
    }

    public function markRead(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function delete(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->delete();
    }

    public function render(): View
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $query = $user->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(15);

        $layout = $user->portal_type === 'visitor' ? 'layouts.portal' : 'layouts.app';

        return view('livewire.notifications-index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ])->layout($layout);
    }
}
