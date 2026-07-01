<?php

namespace App\Livewire\Admin\Staff;

use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Throwable;

#[Layout('layouts.app')]
#[Title('Add Staff')]
class Create extends Component
{
    public string $name = '';

    public string $email = '';

    public string $employeeId = '';

    public string $jobTitle = '';

    public string $departmentId = '';

    public string $managerId = '';

    public string $hireDate = '';

    public string $employmentType = User::EMPLOYMENT_FULL_TIME;

    public string $phone = '';

    public string $mobile = '';

    public string $status = User::STATUS_ACTIVE;

    public string $portalType = 'author';

    /**
     * @var list<string>
     */
    public array $selectedRoles = [];

    public bool $sendInvite = true;

    public string $temporaryPassword = '';

    public function mount(): void
    {
        $this->hireDate = now()->toDateString();
        $this->temporaryPassword = Str::random(12);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'employeeId' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_id')],
            'jobTitle' => ['nullable', 'string', 'max:255'],
            'departmentId' => ['nullable', Rule::exists('departments', 'id')],
            'managerId' => ['nullable', Rule::exists('users', 'id')],
            'hireDate' => ['nullable', 'date'],
            'employmentType' => ['nullable', Rule::in(User::EMPLOYMENT_TYPES)],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(User::STATUSES)],
            'portalType' => ['required', Rule::in(['author', 'admin'])],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => [Rule::exists('roles', 'name')],
            'sendInvite' => ['boolean'],
            'temporaryPassword' => ['required_if:sendInvite,false', 'nullable', 'string', 'min:8'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        try {
            $user = User::query()->create([
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
                'password' => Hash::make($validated['temporaryPassword'] ?? Str::random(16)),
                'email_verified_at' => $this->sendInvite ? null : now(),
            ]);

            if ($this->selectedRoles !== []) {
                $user->syncRoles($this->selectedRoles);
            }

            // TODO: implement actual invite email when SMTP is configured.
            $message = $this->sendInvite
                ? 'Staff member created. Send the invite email manually with their temporary password until SMTP is wired up.'
                : 'Staff member created successfully.';

            session()->flash('success', $message);
            $this->dispatch('toast.success', message: $message);
            $this->redirectRoute('admin.staff.index', navigate: true);
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('danger', 'Failed to create staff member.');
            $this->dispatch('toast.danger', message: 'Failed to create staff member.');
        }
    }

    public function regeneratePassword(): void
    {
        $this->temporaryPassword = Str::random(12);
    }

    public function render(): View
    {
        return view('livewire.admin.staff.create', [
            'departments' => Department::query()->active()->orderBy('name')->get(['id', 'name']),
            'managers' => User::query()
                ->where('portal_type', '!=', 'visitor')
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
