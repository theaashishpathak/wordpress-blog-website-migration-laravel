<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Role;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Edit Role')]
class Edit extends Component
{
    public int $roleId;

    public string $name = '';

    /**
     * @var array<int, int>
     */
    public array $selectedPermissions = [];

    public function mount(int $id): void
    {
        $role = Role::query()->with('permissions')->where('guard_name', 'web')->findOrFail($id);

        $this->roleId = $id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('id')->map(fn ($pid) => (int) $pid)->all();
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
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('guard_name', 'web'))
                    ->ignore($this->roleId),
            ],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where(fn ($query) => $query->where('guard_name', 'web')),
            ],
        ];
    }

    public function toggleGroup(int $groupId): void
    {
        $groupPermissionIds = Permission::query()
            ->where('permission_group_id', $groupId)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $allSelected = collect($groupPermissionIds)->every(fn (int $id) => in_array($id, $this->selectedPermissions, true));

        if ($allSelected) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $groupPermissionIds));
        } else {
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $groupPermissionIds)));
        }
    }

    public function selectAll(): void
    {
        $this->selectedPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function clearAll(): void
    {
        $this->selectedPermissions = [];
    }

    public function save(): void
    {
        $validated = $this->validate();

        Role::query()->whereKey($this->roleId)->update([
            'name' => $validated['name'],
        ]);

        $role = Role::query()->findOrFail($this->roleId);

        $permissionIds = collect($validated['selectedPermissions'] ?? [])
            ->map(fn ($permissionId) => (int) $permissionId)
            ->filter(fn (int $permissionId) => $permissionId > 0)
            ->unique()
            ->values();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('id', $permissionIds)
            ->get();

        $role->syncPermissions($permissions);

        session()->flash('success', 'Role updated successfully.');

        $this->redirectRoute('admin.roles.index', navigate: true);
    }

    public function render(): View
    {
        $permissionGroups = PermissionGroup::query()
            ->with(['permissions' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('livewire.admin.role.edit', [
            'permissionGroups' => $permissionGroups,
            'totalPermissions' => $permissionGroups->sum(fn ($g) => $g->permissions->count()),
        ]);
    }
}
