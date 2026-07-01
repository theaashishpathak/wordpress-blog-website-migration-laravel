<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

// Browser smoke + journey suite. Pest 4's visit() boots a real
// Chromium and shares the test database via RefreshDatabase. We use
// the lazy variant so tests that don't touch the DB don't pay the
// migration cost on every spin-up.
//
// The Pest Browser plugin is opt-in (`composer require pestphp/pest-plugin-browser`
// + `npx playwright install`). When the plugin isn't installed the
// `visit()` helper isn't defined; we skip the whole Browser suite so
// the rest of the test run isn't blocked.
pest()->extend(TestCase::class)
    ->use(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class)
    ->beforeEach(function (): void {
        if (! function_exists('Pest\Browser\visit') && ! function_exists('visit')) {
            test()->markTestSkipped(
                'Pest Browser plugin not installed. Run: '
                .'composer require pestphp/pest-plugin-browser:^4.0 --dev && npx playwright install',
            );
        }
    })
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a staff user with Super Admin role assigned.
 *
 * Super Admin is granted unconditional access via Gate::before in
 * AuthServiceProvider, so this helper works for any permission/role check
 * without having to seed individual permissions.
 *
 * Use this in feature tests that hit permission-protected admin routes.
 *
 * @param  array<string, mixed>  $attributes
 */
function staffUser(array $attributes = []): \App\Models\User
{
    $user = \App\Models\User::factory()->create(array_merge([
        'email_verified_at' => now(),
    ], $attributes));

    $role = \Spatie\Permission\Models\Role::query()->firstOrCreate([
        'name' => 'Super Admin',
        'guard_name' => 'web',
    ]);

    $user->assignRole($role);

    return $user;
}

/**
 * Create a staff user with a specific list of permission names.
 *
 * Permissions are auto-created if missing. Use when you need to test
 * the granular permission middleware (e.g., a user that ONLY has
 * `invoices.view` should NOT be able to access `invoices.create`).
 *
 * @param  list<string>  $permissions
 * @param  array<string, mixed>  $attributes
 */
function staffUserWith(array $permissions, array $attributes = []): \App\Models\User
{
    $user = \App\Models\User::factory()->create(array_merge([
        'email_verified_at' => now(),
    ], $attributes));

    foreach ($permissions as $name) {
        $permission = \Spatie\Permission\Models\Permission::query()->firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo($permission);
    }

    return $user;
}
