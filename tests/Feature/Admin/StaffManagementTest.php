<?php

use App\Livewire\Admin\Staff\Create as StaffCreate;
use App\Livewire\Admin\Staff\Edit as StaffEdit;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('staff index page renders for super admin', function () {
    $admin = staffUser();

    $this->actingAs($admin)
        ->get(route('admin.staff.index'))
        ->assertOk()
        ->assertSee('Staff Directory');
});

test('staff create page renders and creates a new staff member', function () {
    $admin = staffUser();
    $department = Department::query()->create([
        'name' => 'Engineering',
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    Role::query()->firstOrCreate(['name' => 'Sales', 'guard_name' => 'web']);

    Livewire::actingAs($admin)
        ->test(StaffCreate::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('employeeId', 'EMP-100')
        ->set('jobTitle', 'Sales Lead')
        ->set('departmentId', (string) $department->id)
        ->set('hireDate', now()->toDateString())
        ->set('employmentType', User::EMPLOYMENT_FULL_TIME)
        ->set('selectedRoles', ['Sales'])
        ->set('sendInvite', false)
        ->set('temporaryPassword', 'StrongPass123')
        ->call('save');

    $created = User::query()->where('email', 'jane@example.com')->first();

    expect($created)->not->toBeNull();
    expect($created->employee_id)->toBe('EMP-100');
    expect($created->department_id)->toBe($department->id);
    expect($created->job_title)->toBe('Sales Lead');
    expect($created->roles->pluck('name')->all())->toContain('Sales');
});

test('staff edit updates fields and roles', function () {
    $admin = staffUser();
    $department = Department::query()->create([
        'name' => 'Operations',
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $staff = User::factory()->create([
        'name' => 'Old Name',
        'portal_type' => 'author',
        'status' => User::STATUS_ACTIVE,
    ]);

    Role::query()->firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);

    Livewire::actingAs($admin)
        ->test(StaffEdit::class, ['user' => $staff])
        ->set('name', 'Updated Name')
        ->set('jobTitle', 'Operations Manager')
        ->set('departmentId', (string) $department->id)
        ->set('selectedRoles', ['Manager'])
        ->call('save');

    $staff->refresh();

    expect($staff->name)->toBe('Updated Name');
    expect($staff->job_title)->toBe('Operations Manager');
    expect($staff->department_id)->toBe($department->id);
    expect($staff->roles->pluck('name')->all())->toContain('Manager');
});
