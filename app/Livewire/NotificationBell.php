<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    #[Computed]
    public function unreadCount(): int
    {
        return (int) (auth()->user()?->unreadNotifications()->count() ?? 0);
    }

    /**
     * @return Collection<int, \Illuminate\Notifications\DatabaseNotification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        return auth()->user()?->notifications()->latest()->limit(5)->get() ?? collect();
    }

    public function markRead(string $id): void
    {
        $notification = auth()->user()?->notifications()->where('id', $id)->first();
        $notification?->markAsRead();

        unset($this->unreadCount, $this->notifications);
    }

    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);

        unset($this->unreadCount, $this->notifications);
    }

    public function viewAllUrl(): string
    {
        return route('notifications.index');
    }

    public function render(): View
    {
        return view('livewire.notification-bell');
    }
}
