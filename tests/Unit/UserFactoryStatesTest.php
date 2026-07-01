<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

test('admin state produces admin portal user', function () {
    $user = User::factory()->admin()->create();

    expect($user->portal_type)->toBe('admin');
});

test('author state produces author portal user', function () {
    $user = User::factory()->author()->create();

    expect($user->portal_type)->toBe('author');
});

test('staff alias still resolves to author', function () {
    $user = User::factory()->staff()->create();

    expect($user->portal_type)->toBe('author');
});

test('visitor state produces visitor portal user', function () {
    $user = User::factory()->visitor()->create();

    expect($user->portal_type)->toBe('visitor');
});

test('inactive state sets status to inactive', function () {
    $user = User::factory()->inactive()->create();

    expect($user->status)->toBe(User::STATUS_INACTIVE)
        ->and($user->isActive())->toBeFalse();
});

test('suspended state sets status to suspended', function () {
    $user = User::factory()->suspended()->create();

    expect($user->status)->toBe(User::STATUS_SUSPENDED);
});

test('unverified state leaves email_verified_at null', function () {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();
});
