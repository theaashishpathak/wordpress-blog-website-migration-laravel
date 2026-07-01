<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Notifications;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.visitor')]
#[Title('Notifications')]
class Index extends Component
{
    use WithPagination;

    /** 'all' | 'unread' | 'read' */
    #[Url(as: 'view')]
    public string $filter = 'all';

    /**
     * @return LengthAwarePaginator<DatabaseNotification>
     */
    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        $query = auth()->user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->latest()->paginate(20);
    }

    public function switchFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'unread', 'read'], true) ? $filter : 'all';
        $this->resetPage();
    }

    public function markRead(string $id): void
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        $notification?->markAsRead();

        unset($this->notifications);
    }

    public function markUnread(string $id): void
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        $notification?->update(['read_at' => null]);

        unset($this->notifications);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();

        unset($this->notifications);
        $this->dispatch('toast', message: 'All notifications marked as read.');
    }

    public function delete(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->delete();

        unset($this->notifications);
        $this->dispatch('toast', message: 'Notification removed.');
    }

    public function clearAll(): void
    {
        auth()->user()->notifications()->delete();

        unset($this->notifications);
        $this->dispatch('toast', message: 'Cleared all notifications.');
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.visitor.notifications.index', [
            'counts' => [
                'all' => $user->notifications()->count(),
                'unread' => $user->unreadNotifications()->count(),
                'read' => $user->notifications()->whereNotNull('read_at')->count(),
            ],
        ]);
    }
}
