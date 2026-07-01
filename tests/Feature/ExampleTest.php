<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard route redirects guests to login', function () {
    $this->get('/dashboard')
        ->assertRedirect(route('login'));
});
