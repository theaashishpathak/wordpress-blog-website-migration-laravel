<?php

namespace App\Livewire\Admin\Permission;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?int $permissionId = null;

    public string $name = '';

    public ?int $permission_group_id = null;

    public function mount(?int $permissionId = null): void
    {
        $this->permissionId = $permissionId;

        if ($permissionId === null) {
            return;
        }

        $permission = Permission::query()->findOrFail($permissionId);

        $this->name = $permission->name;
        $this->permission_group_id = $permission->permission_group_id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')
                    ->where(fn ($query) => $query->where('guard_name', 'web'))
                    ->ignore($this->permissionId),
            ],
            'permission_group_id' => ['required', 'integer', 'exists:permission_groups,id'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $payload = [
            'name' => $validated['name'],
            'permission_group_id' => $validated['permission_group_id'],
            'guard_name' => 'web',
        ];

        if ($this->permissionId === null) {
            Permission::query()->create($payload);
        } else {
            Permission::query()->whereKey($this->permissionId)->update($payload);
        }

        session()->flash('success', 'Permission saved successfully.');
        $this->dispatch('permission-saved');
    }

    public function cancel(): void
    {
        $this->dispatch('permission-cancelled');
    }

    public function render(): View
    {
        return view('livewire.admin.permissions.form', [
            'permissionGroups' => PermissionGroup::query()->orderBy('name')->get(),
        ]);
    }
}
