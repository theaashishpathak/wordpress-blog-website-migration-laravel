<?php

use App\Models\LoginLog;
use App\Models\User;
use App\Support\UserAgentParser;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function logsAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::findOrCreate('Admin', 'web');

    foreach (['logs.login.view', 'logs.activity.view'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->givePermissionTo(['logs.login.view', 'logs.activity.view']);
    $user->assignRole($role);

    return $user;
}

test('login logs page renders for admin with permission', function () {
    $user = logsAdmin();
    LoginLog::create([
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 Chrome/124',
        'device' => 'WebKit',
        'platform' => 'Windows',
        'browser' => 'Chrome',
        'device_type' => 'Desktop',
        'login_at' => now(),
    ]);

    actingAs($user)
        ->get(route('admin.logs.login.index'))
        ->assertOk()
        ->assertSee('Login Logs')
        ->assertSee('User Login History')
        ->assertSee('127.0.0.1');
});

test('activity logs page renders spatie activity entries for admin with permission', function () {
    $user = logsAdmin();

    Activity::create([
        'log_name' => 'business',
        'description' => 'Listing created',
        'subject_type' => 'App\\Models\\User',
        'subject_id' => 99,
        'causer_type' => User::class,
        'causer_id' => $user->id,
        'event' => 'created',
        'properties' => [
            'attributes' => ['title' => 'New Listing'],
            'context' => [
                'ip_address' => '10.0.0.1',
                'browser' => 'Chrome',
                'country' => 'Bangladesh',
                'country_code' => 'BD',
                'city' => 'Dhaka',
            ],
        ],
    ]);

    actingAs($user)
        ->get(route('admin.logs.activity.index'))
        ->assertOk()
        ->assertSee('Activity Logs')
        ->assertSee('User #99')
        ->assertSee('Created')
        ->assertSee('10.0.0.1')
        ->assertSee('Dhaka');
});

test('user agent parser identifies common browsers and platforms', function () {
    $chrome = UserAgentParser::parse('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36');
    expect($chrome['browser'])->toBe('Chrome')
        ->and($chrome['platform'])->toBe('Windows')
        ->and($chrome['device_type'])->toBe('Desktop');

    $iphone = UserAgentParser::parse('Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) Safari/604.1');
    expect($iphone['platform'])->toBe('iOS')
        ->and($iphone['device_type'])->toBe('Mobile')
        ->and($iphone['device'])->toBe('iPhone');

    $empty = UserAgentParser::parse(null);
    expect($empty['browser'])->toBe('Unknown');
});

test('login event creates a login log row', function () {
    $user = User::factory()->create();

    Event::dispatch(new Login('web', $user, false));

    expect(LoginLog::where('user_id', $user->id)->count())->toBe(1);
});
