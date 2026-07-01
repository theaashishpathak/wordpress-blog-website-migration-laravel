<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('authenticated users can upload an avatar and see it in the shell', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertRedirect(route('profile'))
        ->assertSessionHas('status', 'Avatar updated successfully.');

    $updatedUser = $user->fresh();

    expect($updatedUser->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($updatedUser->avatar);

    $shellResponse = $this->actingAs($updatedUser)
        ->get(route('profile'));

    $avatarUrl = Storage::disk('public')->url($updatedUser->avatar);

    $shellResponse->assertOk();
    expect(substr_count($shellResponse->getContent(), $avatarUrl))->toBeGreaterThanOrEqual(2);
});
