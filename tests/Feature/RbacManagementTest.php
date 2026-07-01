<?php

use App\Livewire\Admin\AssignPermission\Edit as AssignPermissionEdit;
use App\Livewire\Admin\AssignRole\Index as AssignRoleIndex;
use App\Livewire\Admin\Permission\Form as PermissionForm;
use App\Livewire\Admin\PermissionGroup\Form as PermissionGroupForm;
use App\Livewire\Admin\Role\Create as RoleCreate;
use App\Livewire\Admin\Role\Edit as RoleEdit;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('permission group form can create a permission group and auto-generate slug', function () {
    Livewire::test(PermissionGroupForm::class)
        ->set('name', 'Sales Permissions')
        ->call('save')
        ->assertDispatched('permission-group-saved');

    $this->assertDatabaseHas('permission_groups', [
        'name' => 'Sales Permissions',
        'slug' => 'sales-permissions',
    ]);
});

test('permission form can create a permission with a group', function () {
    $permissionGroup = PermissionGroup::query()->create([
        'name' => 'Contacts',
        'slug' => 'contacts',
    ]);

    Livewire::test(PermissionForm::class)
        ->set('name', 'contacts.view')
        ->set('permission_group_id', $permissionGroup->id)
        ->call('save')
        ->assertDispatched('permission-saved');

    $this->assertDatabaseHas('permissions', [
        'name' => 'contacts.view',
        'guard_name' => 'web',
        'permission_group_id' => $permissionGroup->id,
    ]);
});

test('role create page syncs selected permissions to a new role', function () {
    $permissionGroup = PermissionGroup::query()->create([
        'name' => 'Deals',
        'slug' => 'deals',
    ]);

    $permission = Permission::query()->create([
        'name' => 'deals.manage',
        'guard_name' => 'web',
        'permission_group_id' => $permissionGroup->id,
    ]);

    Livewire::test(RoleCreate::class)
        ->set('name', 'Manager')
        ->set('selectedPermissions', [$permission->id])
        ->call('save')
        ->assertRedirect(route('admin.roles.index'));

    $role = Role::query()->where('name', 'Manager')->where('guard_name', 'web')->firstOrFail();

    expect($role->permissions->pluck('name')->all())->toContain('deals.manage');
});

test('role edit page resyncs selected permissions on an existing role', function () {
    $permissionGroup = PermissionGroup::query()->create([
        'name' => 'Reports',
        'slug' => 'reports',
    ]);

    $oldPermission = Permission::query()->create([
        'name' => 'reports.view',
        'guard_name' => 'web',
        'permission_group_id' => $permissionGroup->id,
    ]);

    $newPermission = Permission::query()->create([
        'name' => 'reports.export',
        'guard_name' => 'web',
        'permission_group_id' => $permissionGroup->id,
    ]);

    $role = Role::query()->create(['name' => 'Analyst', 'guard_name' => 'web']);
    $role->syncPermissions([$oldPermission]);

    Livewire::test(RoleEdit::class, ['id' => $role->id])
        ->set('selectedPermissions', [$newPermission->id])
        ->call('save')
        ->assertRedirect(route('admin.roles.index'));

    expect($role->fresh()->permissions->pluck('name')->all())
        ->toContain('reports.export')
        ->not->toContain('reports.view');
});

test('assign role component syncs roles to users', function () {
    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => 'Staff',
        'guard_name' => 'web',
    ]);

    Livewire::test(AssignRoleIndex::class)
        ->call('editUser', $user->id)
        ->set('selectedRoles', [(string) $role->id])
        ->call('saveRoles');

    expect($user->fresh()->roles->pluck('name')->all())->toContain('Staff');
});

test('assign permission edit component syncs direct permissions to a user', function () {
    $user = User::factory()->create([
        'portal_type' => 'admin',
    ]);

    $permissionGroup = PermissionGroup::query()->create([
        'name' => 'Reports',
        'slug' => 'reports',
    ]);

    $permission = Permission::query()->create([
        'name' => 'reports.view',
        'guard_name' => 'web',
        'permission_group_id' => $permissionGroup->id,
    ]);

    Livewire::test(AssignPermissionEdit::class, ['id' => $user->id])
        ->set('selectedPermissions', [(string) $permission->id])
        ->call('save');

    expect($user->fresh()->getDirectPermissions()->pluck('name')->all())->toContain('reports.view');
});

test('admin rbac routes are available to authenticated verified users', function () {
    $user = staffUser();

    $permissionGroup = PermissionGroup::query()->create([
        'name' => 'General',
        'slug' => 'general',
    ]);

    $role = Role::query()->create(['name' => 'Reviewer', 'guard_name' => 'web']);

    $this->actingAs($user)->get('/admin/permission-groups')->assertOk();
    $this->actingAs($user)->get('/admin/permissions')->assertOk();
    $this->actingAs($user)->get('/admin/roles')->assertOk();
    $this->actingAs($user)->get('/admin/roles/create')->assertOk();
    $this->actingAs($user)->get('/admin/roles/'.$role->id.'/edit')->assertOk();
    $this->actingAs($user)->get('/admin/assign-role')->assertOk();
    $this->actingAs($user)->get('/admin/assign-user-permissions')->assertOk();
    $this->actingAs($user)->get('/admin/assign-user-permissions/'.$user->id.'/edit')->assertOk();

    expect($permissionGroup->id)->toBeGreaterThan(0);
});
