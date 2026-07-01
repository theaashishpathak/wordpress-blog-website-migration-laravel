<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Email;

use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.visitor')]
#[Title('Subscribed Lists')]
class Subscriptions extends Component
{
    /**
     * Newsletter subscriptions linked to the user's email. Today we only
     * have a single "main" newsletter list, but the table is designed to
     * accept multiple lists later — this page already iterates over rows
     * so adding lists doesn't need UI changes.
     *
     * @return Collection<int, NewsletterSubscriber>
     */
    #[Computed]
    public function subscriptions(): Collection
    {
        return NewsletterSubscriber::query()
            ->where('email', auth()->user()->email)
            ->latest()
            ->get();
    }

    public function unsubscribe(int $id): void
    {
        $subscription = NewsletterSubscriber::query()
            ->where('email', auth()->user()->email)
            ->findOrFail($id);

        $subscription->update([
            'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ]);

        unset($this->subscriptions);
        $this->dispatch('toast', message: 'Unsubscribed.');
    }

    public function resubscribe(int $id): void
    {
        $subscription = NewsletterSubscriber::query()
            ->where('email', auth()->user()->email)
            ->findOrFail($id);

        $subscription->update([
            'status' => NewsletterSubscriber::STATUS_CONFIRMED,
            'unsubscribed_at' => null,
            'confirmed_at' => $subscription->confirmed_at ?? now(),
        ]);

        unset($this->subscriptions);
        $this->dispatch('toast', message: 'Resubscribed.');
    }

    public function unsubscribeAll(): void
    {
        NewsletterSubscriber::query()
            ->where('email', auth()->user()->email)
            ->where('status', NewsletterSubscriber::STATUS_CONFIRMED)
            ->update([
                'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => now(),
            ]);

        unset($this->subscriptions);
        $this->dispatch('toast', message: 'Unsubscribed from every list.');
    }

    public function render(): View
    {
        return view('livewire.visitor.email.subscriptions');
    }
}
