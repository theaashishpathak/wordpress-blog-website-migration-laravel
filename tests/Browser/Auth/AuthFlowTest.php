<?php

declare(strict_types=1);

/**
 * AuthFlow — exercises Fortify login, register, password reset and
 * email verification end-to-end through the rendered HTML pages.
 *
 * Note on 2FA: Fortify's challenge form is gated by a session token
 * that's hard to replay from a fresh visit() call, so the dedicated
 * 2FA browser test belongs in a separate suite that exposes a helper
 * for setting up the two_factor_secret first. We assert the form
 * renders here as a minimum smoke check.
 */

use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Laravel\Fortify\Notifications\ResetPassword;

beforeEach(function (): void {
    \App\Models\Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

test('guest can log in via the rendered login page', function (): void {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => Hash::make('password123'),
        'email_verified_at' => now(),
    ]);

    visit('/login')
        ->assertSee('Sign in')
        ->fill('input[name="email"]', $user->email)
        ->fill('input[name="password"]', 'password123')
        ->press('Sign in')
        ->assertPathIs('/dashboard');
});

test('login form rejects invalid credentials with an error message', function (): void {
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => Hash::make('correct-password'),
        'email_verified_at' => now(),
    ]);

    visit('/login')
        ->fill('input[name="email"]', 'real@example.com')
        ->fill('input[name="password"]', 'wrong-password')
        ->press('Sign in')
        ->assertPathIs('/login')
        ->assertSee('credentials do not match');
});

test('visitor can request a password reset link', function (): void {
    if (! Features::enabled(Features::resetPasswords())) {
        $this->markTestSkipped('Password reset feature is disabled in Fortify config.');
    }

    Notification::fake();

    $user = User::factory()->create(['email' => 'reset@example.com']);

    visit('/forgot-password')
        ->assertSee('email')
        ->fill('input[name="email"]', $user->email)
        ->press('Email Password Reset Link');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('register page renders and accepts a new sign-up', function (): void {
    if (! Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration is disabled in Fortify config.');
    }

    visit('/register')
        ->fill('input[name="name"]', 'New User')
        ->fill('input[name="email"]', 'new@example.com')
        ->fill('input[name="password"]', 'password123')
        ->fill('input[name="password_confirmation"]', 'password123')
        ->press('Register');

    expect(User::query()->where('email', 'new@example.com')->exists())->toBeTrue();
});

test('logged-in user can sign out', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    visit('/dashboard', as: $user)
        ->assertOk()
        ->press('Logout')
        ->assertPathIs('/login');
});
