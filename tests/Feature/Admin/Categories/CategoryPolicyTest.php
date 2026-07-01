<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Language;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function policyUserWithRole(string $roleName): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('admins are allowed to view categories', function (): void {
    $admin = policyUserWithRole('Admin');
    $category = Category::factory()->create();

    expect(Gate::forUser($admin)->allows('viewAny', Category::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('view', $category))->toBeTrue();
});

test('admins are allowed to create, edit, delete, reorder', function (): void {
    $admin = policyUserWithRole('Admin');
    $category = Category::factory()->create();

    expect(Gate::forUser($admin)->allows('create', Category::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('update', $category))->toBeTrue();
    expect(Gate::forUser($admin)->allows('delete', $category))->toBeTrue();
    expect(Gate::forUser($admin)->allows('reorder', Category::class))->toBeTrue();
});

test('authors cannot create or edit categories', function (): void {
    $author = policyUserWithRole('Author');
    $category = Category::factory()->create();

    expect(Gate::forUser($author)->allows('create', Category::class))->toBeFalse();
    expect(Gate::forUser($author)->allows('update', $category))->toBeFalse();
    expect(Gate::forUser($author)->allows('delete', $category))->toBeFalse();
    expect(Gate::forUser($author)->allows('reorder', Category::class))->toBeFalse();
});

test('super admin bypasses every check via Gate::before', function (): void {
    $superAdmin = policyUserWithRole('Super Admin');
    $category = Category::factory()->create();

    expect(Gate::forUser($superAdmin)->allows('viewAny', Category::class))->toBeTrue();
    expect(Gate::forUser($superAdmin)->allows('delete', $category))->toBeTrue();
    expect(Gate::forUser($superAdmin)->allows('reorder', Category::class))->toBeTrue();
});
