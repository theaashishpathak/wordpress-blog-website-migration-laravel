<?php

use App\Livewire\NotificationsIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function notificationsUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::findOrCreate('Admin', 'web');
    Permission::findOrCreate('notifications.view', 'web');
    $role->givePermissionTo(['notifications.view']);
    $user->assignRole($role);

    return $user;
}

function insertNotification(User $user, ?string $title = null, bool $read = false): string
{
    $id = (string) Str::uuid();

    DB::table('notifications')->insert([
        'id' => $id,
        'type' => DatabaseNotification::class,
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode(['title' => $title ?? 'Test notification', 'message' => 'Body text']),
        'read_at' => $read ? now() : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

test('notifications index requires permission', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertForbidden();
});

test('notifications index renders for user with permission', function () {
    $user = notificationsUser();
    insertNotification($user, 'Welcome aboard');

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Welcome aboard');
});

test('notifications can be filtered by unread and read', function () {
    $user = notificationsUser();
    insertNotification($user, 'Unread one', read: false);
    insertNotification($user, 'Read one', read: true);

    Livewire::actingAs($user)
        ->test(NotificationsIndex::class)
        ->set('filter', 'unread')
        ->assertSee('Unread one')
        ->assertDontSee('Read one')
        ->set('filter', 'read')
        ->assertSee('Read one')
        ->assertDontSee('Unread one');
});

test('mark all read clears unread notifications', function () {
    $user = notificationsUser();
    insertNotification($user, 'Item A', read: false);
    insertNotification($user, 'Item B', read: false);

    Livewire::actingAs($user)
        ->test(NotificationsIndex::class)
        ->call('markAllRead');

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('delete removes the notification from user inbox', function () {
    $user = notificationsUser();
    $id = insertNotification($user, 'Goes away');

    Livewire::actingAs($user)
        ->test(NotificationsIndex::class)
        ->call('delete', $id);

    expect(DB::table('notifications')->where('id', $id)->count())->toBe(0);
});
