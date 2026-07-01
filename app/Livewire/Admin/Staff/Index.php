<?php

namespace App\Livewire\Admin\Staff;

use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Throwable;

#[Layout('layouts.app')]
#[Title('Staff')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $departmentFilter = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public string $employmentTypeFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingEmploymentTypeFilter(): void
    {
        $this->resetPage();
    }

    public function deactivate(int $userId): void
    {
        try {
            $user = User::query()->findOrFail($userId);

            if ($user->id === auth()->id()) {
                $this->dispatchDangerToast('You cannot deactivate your own account.');

                return;
            }

            $user->update(['status' => User::STATUS_INACTIVE]);
            $this->dispatchSuccessToast('Staff member deactivated.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Failed to deactivate staff member.');
        }
    }

    public function activate(int $userId): void
    {
        try {
            User::query()->findOrFail($userId)->update(['status' => User::STATUS_ACTIVE]);
            $this->dispatchSuccessToast('Staff member activated.');
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Failed to activate staff member.');
        }
    }

    public function requestDelete(int $id): void
    {
        $this->dispatch('confirm-staff-delete-alert', id: $id);
    }

    #[On('confirm-staff-delete')]
    public function delete(int $id): void
    {
        try {
            if ($id === auth()->id()) {
                $this->dispatchDangerToast('You cannot delete your own account.');

                return;
            }

            User::query()->whereKey($id)->delete();
            $this->dispatchSuccessToast('Staff member deleted.');
            $this->resetPage();
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Failed to delete staff member.');
        }
    }

    public function render(): View
    {
        $staff = User::query()
            ->where('portal_type', '!=', 'visitor')
            ->with(['department:id,name', 'manager:id,name', 'roles:id,name'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($builder): void {
                    $builder->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('employee_id', 'like', '%'.$this->search.'%')
                        ->orWhere('job_title', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->departmentFilter !== '', function ($query): void {
                $query->where('department_id', (int) $this->departmentFilter);
            })
            ->when($this->roleFilter !== '', function ($query): void {
                $query->whereHas('roles', function ($builder): void {
                    $builder->where('name', $this->roleFilter);
                });
            })
            ->when($this->statusFilter !== '', function ($query): void {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->employmentTypeFilter !== '', function ($query): void {
                $query->where('employment_type', $this->employmentTypeFilter);
            })
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.staff.index', [
            'staff' => $staff,
            'departments' => Department::query()->active()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => User::STATUSES,
            'employmentTypes' => User::EMPLOYMENT_TYPES,
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
