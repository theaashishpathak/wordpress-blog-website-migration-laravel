<?php

namespace App\Livewire\Admin\Logs;

use App\Models\LoginLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Login Logs')]
class LoginLogIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    /** @return array<int, string> */
    public function statusOptions(): array
    {
        return LoginLog::STATUSES;
    }

    public function render(): View
    {
        $logs = LoginLog::query()
            ->with('user:id,name,email')
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($qq): void {
                    $qq->where('ip_address', 'like', '%'.$this->search.'%')
                        ->orWhere('browser', 'like', '%'.$this->search.'%')
                        ->orWhere('platform', 'like', '%'.$this->search.'%')
                        ->orWhere('attempted_email', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%'));
                });
            })
            ->latest('login_at')
            ->paginate(15);

        return view('admin.logs.login-index', [
            'logs' => $logs,
            'statuses' => $this->statusOptions(),
        ]);
    }
}
