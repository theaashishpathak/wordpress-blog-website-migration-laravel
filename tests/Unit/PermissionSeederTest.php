<?php

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('permission seeder creates permissions and syncs them to super admin', function (): void {
    $permissions = collect(config('permissions', []))
        ->flatten(1)
        ->filter(fn (mixed $permission): bool => is_string($permission) && $permission !== '')
        ->unique()
        ->values();

    app(PermissionSeeder::class)->run();

    $seededPermissions = Permission::query()->get();

    expect($seededPermissions)->toHaveCount($permissions->count());
    expect($seededPermissions->pluck('guard_name')->unique()->all())->toEqual(['web']);
    expect($seededPermissions->pluck('name')->sort()->values()->all())->toBe($permissions->sort()->values()->all());

    $superAdminRole = Role::query()->get()->firstWhere('name', 'Super Admin');

    expect($superAdminRole)->not->toBeNull();
    expect($superAdminRole->guard_name)->toBe('web');
    expect($superAdminRole->permissions->pluck('name')->sort()->values()->all())->toBe($permissions->sort()->values()->all());
});
