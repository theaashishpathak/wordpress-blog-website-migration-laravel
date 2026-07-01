<?php

use App\Livewire\Admin\Departments\Index as DepartmentIndex;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('department index renders for super admin', function () {
    $admin = staffUser();

    $this->actingAs($admin)
        ->get(route('admin.departments.index'))
        ->assertOk()
        ->assertSee('Departments');
});

test('department can be created via livewire form', function () {
    $admin = staffUser();

    Livewire::actingAs($admin)
        ->test(DepartmentIndex::class)
        ->call('create')
        ->set('name', 'Customer Success')
        ->set('description', 'Owns post-sale relationships')
        ->set('status', Department::STATUS_ACTIVE)
        ->set('sortOrder', '1')
        ->call('save');

    $dept = Department::query()->where('name', 'Customer Success')->first();

    expect($dept)->not->toBeNull();
    expect($dept->slug)->toBe('customer-success');
    expect($dept->code)->toMatch('/^DEPT-\d{4}$/');
});

test('deleting department detaches its members', function () {
    $admin = staffUser();
    $dept = Department::query()->create([
        'name' => 'To Be Deleted',
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $member = User::factory()->create([
        'department_id' => $dept->id,
        'portal_type' => 'author',
    ]);

    Livewire::actingAs($admin)
        ->test(DepartmentIndex::class)
        ->call('delete', $dept->id);

    $member->refresh();

    expect(Department::query()->whereKey($dept->id)->exists())->toBeFalse();
    expect($member->department_id)->toBeNull();
});
