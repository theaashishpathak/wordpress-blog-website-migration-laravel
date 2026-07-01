<?php

namespace App\Livewire\Admin\Staff;

use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Throwable;

#[Layout('layouts.app')]
#[Title('Edit Staff')]
class Edit extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $employeeId = '';

    public string $jobTitle = '';

    public string $departmentId = '';

    public string $managerId = '';

    public string $hireDate = '';

    public string $employmentType = '';

    public string $phone = '';

    public string $mobile = '';

    public string $status = User::STATUS_ACTIVE;

    public string $portalType = 'author';

    /**
     * @var list<string>
     */
    public array $selectedRoles = [];

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->employeeId = $user->employee_id ?? '';
        $this->jobTitle = $user->job_title ?? '';
        $this->departmentId = $user->department_id !== null ? (string) $user->department_id : '';
        $this->managerId = $user->manager_id !== null ? (string) $user->manager_id : '';
        $this->hireDate = $user->hire_date?->toDateString() ?? '';
        $this->employmentType = $user->employment_type ?? '';
        $this->phone = $user->phone ?? '';
        $this->mobile = $user->mobile ?? '';
        $this->status = $user->status ?? User::STATUS_ACTIVE;
        $this->portalType = $user->portal_type ?? 'author';
        $this->selectedRoles = $user->roles->pluck('name')->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'employeeId' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_id')->ignore($this->user->id)],
            'jobTitle' => ['nullable', 'string', 'max:255'],
            'departmentId' => ['nullable', Rule::exists('departments', 'id')],
            'managerId' => ['nullable', Rule::exists('users', 'id'), Rule::notIn([(string) $this->user->id])],
            'hireDate' => ['nullable', 'date'],
            'employmentType' => ['nullable', Rule::in(User::EMPLOYMENT_TYPES)],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(User::STATUSES)],
            'portalType' => ['required', Rule::in(['author', 'admin'])],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => [Rule::exists('roles', 'name')],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        try {
            $this->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'employee_id' => $this->nullify($validated['employeeId'] ?? null),
                'job_title' => $this->nullify($validated['jobTitle'] ?? null),
                'department_id' => $validated['departmentId'] !== '' ? (int) $validated['departmentId'] : null,
                'manager_id' => $validated['managerId'] !== '' ? (int) $validated['managerId'] : null,
                'hire_date' => $this->nullify($validated['hireDate'] ?? null),
                'employment_type' => $validated['employmentType'] ?: null,
                'phone' => $this->nullify($validated['phone'] ?? null),
                'mobile' => $this->nullify($validated['mobile'] ?? null),
                'status' => $validated['status'],
                'portal_type' => $validated['portalType'],
            ]);

            $this->user->syncRoles($this->selectedRoles);

            session()->flash('success', 'Staff member updated successfully.');
            $this->dispatch('toast.success', message: 'Staff member updated successfully.');
            $this->redirectRoute('admin.staff.index', navigate: true);
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('danger', 'Failed to update staff member.');
            $this->dispatch('toast.danger', message: 'Failed to update staff member.');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.staff.edit', [
            'departments' => Department::query()->active()->orderBy('name')->get(['id', 'name']),
            'managers' => User::query()
                ->where('portal_type', '!=', 'visitor')
                ->where('id', '!=', $this->user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => User::STATUSES,
            'employmentTypes' => User::EMPLOYMENT_TYPES,
        ]);
    }

    protected function nullify(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
