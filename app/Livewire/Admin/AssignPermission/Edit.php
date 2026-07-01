<?php

declare(strict_types=1);

namespace App\Livewire\Admin\AssignPermission;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Edit User Permissions')]
class Edit extends Component
{
    public int $userId;

    public string $userName = '';

    public string $userEmail = '';

    /**
     * @var array<int, int>
     */
    public array $selectedPermissions = [];

    /**
     * @var array<int, string>
     */
    public array $userRoles = [];

    public function mount(int $id): void
    {
        $this->userId = $id;

        $user = User::query()->with(['permissions', 'roles'])->findOrFail($id);

        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->selectedPermissions = $user->permissions->pluck('id')->map(fn ($pid) => (int) $pid)->all();
        $this->userRoles = $user->roles->pluck('name')->all();
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

    public function clearAll(): void
    {
        $this->selectedPermissions = [];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where(fn ($query) => $query->where('guard_name', 'web')),
            ],
        ]);

        $user = User::query()->findOrFail($this->userId);

        $permissionIds = collect($validated['selectedPermissions'] ?? [])
            ->map(fn ($permissionId) => (int) $permissionId)
            ->filter(fn (int $permissionId) => $permissionId > 0)
            ->unique()
            ->values();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('id', $permissionIds)
            ->get();

        $user->syncPermissions($permissions);

        session()->flash('success', 'User direct permissions updated successfully.');

        $this->redirectRoute('admin.assign-user-permissions.index', navigate: true);
    }

    public function render(): View
    {
        $permissionGroups = PermissionGroup::query()
            ->with(['permissions' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('livewire.admin.assign-permission.edit', [
            'permissionGroups' => $permissionGroups,
            'totalPermissions' => $permissionGroups->sum(fn ($g) => $g->permissions->count()),
        ]);
    }
}
