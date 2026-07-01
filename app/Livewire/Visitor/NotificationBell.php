<?php

declare(strict_types=1);

namespace App\Livewire\Visitor;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Compact bell in the visitor navbar. Surfaces the unread count + a
 * dropdown preview of the 5 most recent notifications. Full management
 * lives at /visitor/notifications.
 */
class NotificationBell extends Component
{
    public bool $open = false;

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed(persist: false)]
    public function recent(): Collection
    {
        return auth()->user()
            ->notifications()
            ->latest()
            ->limit(6)
            ->get();
    }

    #[Computed(persist: false)]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markRead(string $id): void
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        $notification?->markAsRead();

        unset($this->recent, $this->unreadCount);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();

        unset($this->recent, $this->unreadCount);
        $this->dispatch('toast', message: 'All caught up.');
    }

    public function render(): View
    {
        return view('livewire.visitor.notification-bell');
    }
}
