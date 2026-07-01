<?php

namespace App\Livewire\Admin\Departments;

use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Departments')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    // Form fields
    public string $name = '';

    public string $description = '';

    public string $headUserId = '';

    public string $status = Department::STATUS_ACTIVE;

    public string $sortOrder = '0';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $department = Department::query()->findOrFail($id);

        $this->editingId = $department->id;
        $this->name = $department->name;
        $this->description = $department->description ?? '';
        $this->headUserId = $department->head_user_id !== null ? (string) $department->head_user_id : '';
        $this->status = $department->status;
        $this->sortOrder = (string) $department->sort_order;

        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'headUserId' => ['nullable', Rule::exists('users', 'id')],
            'status' => ['required', Rule::in(Department::STATUSES)],
            'sortOrder' => ['required', 'integer', 'min:0'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        try {
            $payload = [
                'name' => $validated['name'],
                'description' => $this->nullify($validated['description'] ?? null),
                'head_user_id' => $validated['headUserId'] !== '' ? (int) $validated['headUserId'] : null,
                'status' => $validated['status'],
                'sort_order' => (int) $validated['sortOrder'],
                'updated_by' => auth()->id(),
            ];

            if ($this->editingId === null) {
                $payload['created_by'] = auth()->id();
                Department::query()->create($payload);
                $this->dispatchSuccessToast('Department created successfully.');
            } else {
                Department::query()->findOrFail($this->editingId)->update($payload);
                $this->dispatchSuccessToast('Department updated successfully.');
            }

            $this->closeFormModal();
            $this->resetPage();
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Failed to save department.');
        }
    }

    public function requestDelete(int $id): void
    {
        $this->dispatch('confirm-department-delete-alert', id: $id);
    }

    #[On('confirm-department-delete')]
    public function delete(int $id): void
    {
        try {
            $department = Department::query()->findOrFail($id);

            // Detach members before delete (department_id will become null on members).
            User::query()->where('department_id', $department->id)->update(['department_id' => null]);

            $department->delete();
            $this->dispatchSuccessToast('Department deleted successfully.');
            $this->resetPage();
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatchDangerToast('Failed to delete department.');
        }
    }

    public function render(): View
    {
        $departments = Department::query()
            ->with(['head:id,name', 'members'])
            ->withCount('members')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($builder): void {
                    $builder->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', function ($query): void {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.departments.index', [
            'departments' => $departments,
            'staffOptions' => User::query()
                ->where('portal_type', '!=', 'visitor')
                ->orderBy('name')
                ->limit(200)
                ->get(['id', 'name', 'email']),
            'statuses' => Department::STATUSES,
        ]);
    }

    protected function resetForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->headUserId = '';
        $this->status = Department::STATUS_ACTIVE;
        $this->sortOrder = '0';
        $this->resetErrorBag();
    }

    protected function nullify(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
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
