<?php

use App\Models\ProfileActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('profile information updates are logged and displayed on profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-profile-information.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '+1 (555) 010-1111',
            'mobile' => $user->mobile,
            'gender' => $user->gender,
            'date_of_birth' => optional($user->date_of_birth)->format('Y-m-d'),
            'timezone' => 'UTC',
            'locale' => 'en',
        ])
        ->assertStatus(302);

    $this->assertDatabaseHas('profile_activity_logs', [
        'user_id' => $user->id,
        'event' => 'profile_information_updated',
        'description' => 'Updated profile information.',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Activity Log')
        ->assertSee('Updated profile information.');
});

test('avatar updates are logged', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('profile.jpg'),
        ])
        ->assertRedirect(route('profile'));

    $this->assertDatabaseHas('profile_activity_logs', [
        'user_id' => $user->id,
        'event' => 'avatar_updated',
        'description' => 'Updated profile avatar.',
    ]);
});

test('password updates are logged', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertStatus(302);

    expect(ProfileActivityLog::query()->where('user_id', $user->id)->where('event', 'password_updated')->exists())->toBeTrue();
});

test('profile activity logs are paginated at ten rows per page', function () {
    $user = User::factory()->create();

    foreach (range(1, 11) as $index) {
        DB::table('profile_activity_logs')->insert([
            'user_id' => $user->id,
            'event' => 'profile_information_updated',
            'description' => sprintf('[log-%02d]', $index),
            'meta' => null,
            'created_at' => now()->addSeconds($index),
            'updated_at' => now()->addSeconds($index),
        ]);
    }

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('[log-11]')
        ->assertDontSee('[log-01]');

    $this->actingAs($user)
        ->get(route('profile', ['page' => 2]))
        ->assertOk()
        ->assertSee('[log-01]')
        ->assertDontSee('[log-11]');
});
