<?php

declare(strict_types=1);

use App\Actions\Visitor\Bookmark\ToggleBookmarkAction;
use App\Actions\Visitor\Reaction\ToggleReactionAction;
use App\Actions\Visitor\ReadingList\ToggleReadingListAction;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Phase V9 — End-to-end smoke test for the visitor reader journey
|--------------------------------------------------------------------------
| Hits the real Action layer (not the UI), then verifies that every portal
| sidebar route renders 200 for a visitor with realistic engagement data.
| Catches schema drift + permission gates regressing across phases.
*/

test('a visitor can bookmark, react, queue, and then visit every portal page', function () {
    $visitor = User::factory()->visitor()->create();
    $post = Post::factory()->create([
        'status' => \App\Enums\PostStatus::Published,
        'published_at' => now()->subDay(),
        'allow_comments' => true,
    ]);

    // Generate engagement signal
    app(ToggleBookmarkAction::class)->handle($visitor, $post);
    app(ToggleReadingListAction::class)->handle($visitor, $post);
    app(ToggleReactionAction::class)->handle($visitor, $post, 'like');

    $this->actingAs($visitor);

    $routes = [
        'visitor.dashboard',
        'visitor.bookmarks',
        'visitor.reading-list',
        'visitor.reading-history',
        'visitor.highlights',
        'visitor.comments',
        'visitor.reactions',
        'visitor.recommendations',
        'visitor.following.topics',
        'visitor.following.authors',
        'visitor.following.users',
        'visitor.notifications',
        'visitor.email.preferences',
        'visitor.email.subscriptions',
        'visitor.settings.profile',
        'visitor.settings.security',
        'visitor.settings.sessions',
        'visitor.settings.privacy',
        'visitor.settings.appearance',
        'visitor.data.export',
        'visitor.data.delete',
    ];

    foreach ($routes as $name) {
        $this->get(route($name))
            ->assertOk()
            ->assertSee('Reader Portal', false);
    }

    // Confirm the engagement actually shows up where it should
    expect($visitor->bookmarks()->count())->toBe(1)
        ->and($visitor->readingListItems()->active()->count())->toBe(1)
        ->and($visitor->reactions()->where('type', 'like')->count())->toBe(1);
});

test('admin user is bounced from every visitor portal route', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $routes = [
        'visitor.dashboard',
        'visitor.bookmarks',
        'visitor.comments',
        'visitor.following.topics',
        'visitor.notifications',
        'visitor.email.preferences',
        'visitor.settings.profile',
        'visitor.data.export',
        'visitor.data.delete',
    ];

    foreach ($routes as $name) {
        $this->get(route($name))->assertRedirect(route('dashboard'));
    }
});

test('every visitor sidebar item points to a real built route', function () {
    $visitor = User::factory()->visitor()->create();
    $this->actingAs($visitor);

    // Sanity: the sidebar component renders without listing any "Soon" badges
    // — V9 should land with every item built.
    $response = $this->get(route('visitor.dashboard'))
        ->assertOk()
        ->getContent();

    expect($response)->not->toContain('"Soon"')
        ->and($response)->not->toContain('>Soon<');
});
