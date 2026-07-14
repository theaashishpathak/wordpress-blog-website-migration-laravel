<?php

namespace App\Livewire\Admin\Subscribers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Subscribers')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function promoteToAuthor(int $userId): void
    {
        try {
            DB::transaction(function () use ($userId): void {
                $user = User::query()->where('portal_type', 'visitor')->findOrFail($userId);

                $user->syncRoles(['Author']);
                $user->update(['portal_type' => 'author']);
            });

            $this->dispatchSuccessToast('Subscriber promoted to Author.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Failed to promote subscriber.');
        }
    }

    public function deactivate(int $userId): void
    {
        try {
            $user = User::query()->where('portal_type', 'visitor')->findOrFail($userId);

            if ($user->id === auth()->id()) {
                $this->dispatchDangerToast('You cannot deactivate your own account.');

                return;
            }

            $user->update(['status' => User::STATUS_INACTIVE]);
            $this->dispatchSuccessToast('Subscriber deactivated.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Failed to deactivate subscriber.');
        }
    }

    public function activate(int $userId): void
    {
        try {
            User::query()->where('portal_type', 'visitor')->findOrFail($userId)
                ->update(['status' => User::STATUS_ACTIVE]);
            $this->dispatchSuccessToast('Subscriber activated.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Failed to activate subscriber.');
        }
    }

    public function render(): View
    {
        $subscribers = User::query()
            ->where('portal_type', 'visitor')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($builder): void {
                    $builder->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== '', function ($query): void {
                $query->where('status', $this->statusFilter);
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.subscribers.index', [
            'subscribers' => $subscribers,
            'statuses' => User::STATUSES,
        ]);
    }

    protected function dispatchSuccessToast(string $message): void
    {
        session()->flash('success', $message);
        $this->dispatch('toast.success', message: $message);
    }

    protected function dispatchDangerToast(string $message): void
    {
        session()->flash('danger', $message);
        $this->dispatch('toast.danger', message: $message);
    }
}
