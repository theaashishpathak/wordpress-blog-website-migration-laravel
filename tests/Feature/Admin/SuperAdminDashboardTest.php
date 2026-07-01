<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('super admin dashboard renders for a super admin user', function (): void {
    $user = staffUser();

    $this->actingAs($user)
        ->get(route('admin.dashboard.super'))
        ->assertOk();
});
