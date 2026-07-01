<?php

declare(strict_types=1);

use App\Livewire\Visitor\Settings\Appearance;
use App\Livewire\Visitor\Settings\Privacy;
use App\Livewire\Visitor\Settings\Profile;
use App\Livewire\Visitor\Settings\Security;
use App\Livewire\Visitor\Settings\Sessions;
use App\Models\Language;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->visitor = User::factory()->visitor()->create([
        'password' => Hash::make('CurrentPass!1'),
    ]);
});

// ── Profile ────────────────────────────────────────────────────────────

test('profile save persists basic fields', function () {
    Livewire::actingAs($this->visitor)
        ->test(Profile::class)
        ->set('name', 'Aisha Rahman')
        ->set('bio', 'Loves climate journalism.')
        ->set('phone', '+880-555-0123')
        ->set('social.twitter', '@aisha')
        ->call('save');

    $fresh = $this->visitor->fresh();
    expect($fresh->name)->toBe('Aisha Rahman')
        ->and($fresh->bio)->toBe('Loves climate journalism.')
        ->and($fresh->phone)->toBe('+880-555-0123')
        ->and($fresh->social_links['twitter'])->toBe('@aisha');
});

test('profile email change requires current password', function () {
    Livewire::actingAs($this->visitor)
        ->test(Profile::class)
        ->set('email', 'new@example.com')
        ->set('currentPassword', 'wrong')
        ->call('save')
        ->assertHasErrors('currentPassword');

    expect($this->visitor->fresh()->email)->toBe($this->visitor->email);
});

test('profile email change with correct password updates and unverifies', function () {
    Livewire::actingAs($this->visitor)
        ->test(Profile::class)
        ->set('email', 'new@example.com')
        ->set('currentPassword', 'CurrentPass!1')
        ->call('save');

    $fresh = $this->visitor->fresh();
    expect($fresh->email)->toBe('new@example.com')
        ->and($fresh->email_verified_at)->toBeNull();
});

// ── Security ───────────────────────────────────────────────────────────

test('change password validates old password', function () {
    Livewire::actingAs($this->visitor)
        ->test(Security::class)
        ->set('currentPassword', 'wrong')
        ->set('newPassword', 'NewPass!12345')
        ->set('newPasswordConfirmation', 'NewPass!12345')
        ->call('changePassword')
        ->assertHasErrors('currentPassword');
});

test('change password updates the hash on success', function () {
    Livewire::actingAs($this->visitor)
        ->test(Security::class)
        ->set('currentPassword', 'CurrentPass!1')
        ->set('newPassword', 'NewPass!12345')
        ->set('newPasswordConfirmation', 'NewPass!12345')
        ->call('changePassword');

    expect(Hash::check('NewPass!12345', $this->visitor->fresh()->password))->toBeTrue();
});

// ── Sessions ───────────────────────────────────────────────────────────

test('sessions page handles file driver gracefully', function () {
    config(['session.driver' => 'file']);

    Livewire::actingAs($this->visitor)
        ->test(Sessions::class)
        ->assertOk();
});

test('sessions revoke deletes the row but not current', function () {
    config(['session.driver' => 'database']);

    if (! DB::getSchemaBuilder()->hasTable('sessions')) {
        DB::statement('CREATE TABLE sessions (id varchar(255) primary key, user_id integer, ip_address varchar(45), user_agent text, payload text, last_activity integer)');
    }

    DB::table('sessions')->insert([
        'id' => 'other-session-id',
        'user_id' => $this->visitor->id,
        'ip_address' => '1.2.3.4',
        'user_agent' => 'Mozilla/5.0',
        'payload' => '',
        'last_activity' => time() - 600,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(Sessions::class)
        ->call('revoke', 'other-session-id');

    expect(DB::table('sessions')->where('id', 'other-session-id')->exists())->toBeFalse();
});

// ── Privacy ────────────────────────────────────────────────────────────

test('privacy page persists user_settings rows', function () {
    Livewire::actingAs($this->visitor)
        ->test(Privacy::class)
        ->set('profileVisibility', 'followers')
        ->set('showReadingHistory', true)
        ->set('allowDms', false)
        ->call('save');

    expect(UserSetting::getValue($this->visitor->id, 'profile_visibility'))->toBe('followers')
        ->and(UserSetting::getValue($this->visitor->id, 'show_reading_history'))->toBeTrue()
        ->and(UserSetting::getValue($this->visitor->id, 'allow_dms'))->toBeFalse();
});

test('privacy page rejects invalid visibility values', function () {
    Livewire::actingAs($this->visitor)
        ->test(Privacy::class)
        ->set('profileVisibility', 'invalid_value')
        ->call('save')
        ->assertHasErrors('profileVisibility');
});

// ── Appearance ─────────────────────────────────────────────────────────

test('appearance save persists theme + font size + reading width', function () {
    Livewire::actingAs($this->visitor)
        ->test(Appearance::class)
        ->set('theme', 'dark')
        ->set('fontSize', 'large')
        ->set('readingWidth', 'wide')
        ->call('save');

    expect(UserSetting::getValue($this->visitor->id, 'theme'))->toBe('dark')
        ->and(UserSetting::getValue($this->visitor->id, 'font_size'))->toBe('large')
        ->and(UserSetting::getValue($this->visitor->id, 'reading_width'))->toBe('wide');
});

test('appearance language change updates user locale column', function () {
    $lang = Language::factory()->create(['code' => 'bn', 'is_active' => true]);

    Livewire::actingAs($this->visitor)
        ->test(Appearance::class)
        ->set('languageId', $lang->id)
        ->call('save');

    expect($this->visitor->fresh()->locale)->toBe('bn');
});

test('appearance rejects invalid theme', function () {
    Livewire::actingAs($this->visitor)
        ->test(Appearance::class)
        ->set('theme', 'neon')
        ->call('save')
        ->assertHasErrors('theme');
});
