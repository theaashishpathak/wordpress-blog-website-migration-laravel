<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Phase V1 — Visitor portal access & sidebar route smoke tests
|--------------------------------------------------------------------------
| The full menu structure is rendered via placeholder ComingSoon component.
| Every sidebar route must return 200 for a logged-in visitor and 302
| (redirect to admin /dashboard) for staff users — that's what the
| EnsureVisitorPortal middleware enforces.
*/

test('guest is redirected to login from any visitor route', function () {
    $this->get(route('visitor.dashboard'))->assertRedirect(route('login'));
    $this->get(route('visitor.bookmarks'))->assertRedirect(route('login'));
});

test('admin/author user is redirected away from visitor routes', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('visitor.dashboard'))
        ->assertRedirect(route('dashboard'));
});

test('visitor user can access every visitor portal route', function () {
    $visitor = User::factory()->visitor()->create();

    $this->actingAs($visitor);

    $routes = [
        'visitor.dashboard',
        // Library
        'visitor.bookmarks',
        'visitor.reading-list',
        'visitor.reading-history',
        'visitor.highlights',
        // Engagement
        'visitor.comments',
        'visitor.reactions',
        'visitor.recommendations',
        // Following
        'visitor.following.topics',
        'visitor.following.authors',
        'visitor.following.users',
        // Notifications
        'visitor.notifications',
        // Email & Newsletter
        'visitor.email.preferences',
        'visitor.email.subscriptions',
        // Settings
        'visitor.settings.profile',
        'visitor.settings.security',
        'visitor.settings.sessions',
        'visitor.settings.privacy',
        'visitor.settings.appearance',
        // Data & Privacy
        'visitor.data.export',
        'visitor.data.delete',
    ];

    foreach ($routes as $name) {
        $this->get(route($name))
            ->assertOk()
            ->assertSee('Reader Portal', false); // sidebar branding line
    }
});

test('login dispatches a visitor to the visitor dashboard', function () {
    $visitor = User::factory()->visitor()->create();

    $this->actingAs($visitor)
        ->get(route('dashboard'))
        ->assertRedirect(route('visitor.dashboard'));
});

test('comments page renders for a visitor with no comments yet', function () {
    // Was a ComingSoon assertion in V1; the route now resolves to the real
    // My Comments Livewire page (built in V3), so the test verifies the
    // empty-state copy instead.
    $visitor = User::factory()->visitor()->create();

    $this->actingAs($visitor)
        ->get(route('visitor.comments'))
        ->assertOk()
        ->assertSee('My Comments')
        ->assertSee('No comments yet');
});
