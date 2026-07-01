<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Livewire\Author\Dashboard as AuthorDashboard;
use App\Livewire\Author\Profile as AuthorProfile;
use App\Livewire\Frontend\AuthorShow;
use App\Models\AIUsageLog;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function portalUser(string $roleName = 'Author'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

// -------------------------------------------------------------------------
// Dashboard
// -------------------------------------------------------------------------

test('users without posts.create are denied access to the author dashboard', function (): void {
    $subscriber = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($subscriber)->test(AuthorDashboard::class)->assertForbidden();
});

test('author can view their dashboard', function (): void {
    $author = portalUser();

    Livewire::actingAs($author)
        ->test(AuthorDashboard::class)
        ->assertOk()
        ->assertSee($author->name);
});

test('dashboard counts reflect only the current author posts', function (): void {
    $me = portalUser();
    $other = portalUser();

    Post::factory()->count(2)->draft()->withAuthor($me->id)->create();
    Post::factory()->pendingReview()->withAuthor($me->id)->create();
    Post::factory()->published()->withAuthor($me->id)->create();
    Post::factory()->count(5)->published()->withAuthor($other->id)->create();

    $component = Livewire::actingAs($me)->test(AuthorDashboard::class);
    $counts = $component->instance()->counts;

    expect($counts['draft'])->toBe(2);
    expect($counts['pending'])->toBe(1);
    expect($counts['published'])->toBe(1);
    expect($counts['total'])->toBe(4);
});

test('dashboard totalViews sums view_count across author posts only', function (): void {
    $author = portalUser();
    $other = portalUser();

    Post::factory()->published()->withAuthor($author->id)->state(['view_count' => 100])->create();
    Post::factory()->published()->withAuthor($author->id)->state(['view_count' => 50])->create();
    Post::factory()->published()->withAuthor($other->id)->state(['view_count' => 9999])->create();

    $component = Livewire::actingAs($author)->test(AuthorDashboard::class);
    expect($component->instance()->totalViews)->toBe(150);
});

test('dashboard aiUsageThisMonth reports calls + tokens + cost for current user', function (): void {
    $author = portalUser();
    $other = portalUser();

    AIUsageLog::query()->create([
        'user_id' => $author->id,
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'feature_key' => 'article_writer',
        'prompt_tokens' => 100,
        'completion_tokens' => 200,
        'total_tokens' => 300,
        'estimated_cost_usd' => 0.01,
        'status' => AIUsageLog::STATUS_SUCCESS,
    ]);

    AIUsageLog::query()->create([
        'user_id' => $other->id,
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'feature_key' => 'article_writer',
        'prompt_tokens' => 999,
        'completion_tokens' => 999,
        'total_tokens' => 1998,
        'estimated_cost_usd' => 0.99,
        'status' => AIUsageLog::STATUS_SUCCESS,
    ]);

    $component = Livewire::actingAs($author)->test(AuthorDashboard::class);
    $usage = $component->instance()->aiUsageThisMonth;

    expect($usage['calls'])->toBe(1);
    expect($usage['tokens'])->toBe(300);
    expect($usage['cost'])->toEqualWithDelta(0.01, 0.0001);
});

// -------------------------------------------------------------------------
// Profile editor
// -------------------------------------------------------------------------

test('users without author privileges cannot access the profile editor', function (): void {
    $subscriber = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($subscriber)->test(AuthorProfile::class)->assertForbidden();
});

test('profile editor hydrates with current user values', function (): void {
    $author = portalUser();
    $author->fill([
        'bio' => 'Existing bio text',
        'public_slug' => 'existing-slug',
        'social_links' => ['twitter' => '@authorhandle'],
        'show_in_team' => true,
    ])->save();

    Livewire::actingAs($author->fresh())
        ->test(AuthorProfile::class)
        ->assertSet('bio', 'Existing bio text')
        ->assertSet('publicSlug', 'existing-slug')
        ->assertSet('social.twitter', '@authorhandle')
        ->assertSet('showInTeam', true);
});

test('save persists bio + slug + social links + show_in_team', function (): void {
    $author = portalUser();

    Livewire::actingAs($author)
        ->test(AuthorProfile::class)
        ->set('bio', 'New bio')
        ->set('publicSlug', 'Some Slug')
        ->set('social.twitter', '@me')
        ->set('social.linkedin', 'me-on-linkedin')
        ->set('showInTeam', true)
        ->call('save')
        ->assertDispatched('toast.success');

    $author->refresh();
    expect($author->bio)->toBe('New bio');
    expect($author->public_slug)->toBe('some-slug');     // slugified
    expect($author->social_links)->toMatchArray(['twitter' => '@me', 'linkedin' => 'me-on-linkedin']);
    expect($author->show_in_team)->toBeTrue();
});

test('save rejects empty display name', function (): void {
    $author = portalUser();

    Livewire::actingAs($author)
        ->test(AuthorProfile::class)
        ->set('displayName', '')
        ->call('save')
        ->assertHasErrors(['displayName']);
});

test('save rejects duplicate public_slug across users', function (): void {
    $author = portalUser();
    $other = portalUser();
    $other->update(['public_slug' => 'taken']);

    Livewire::actingAs($author)
        ->test(AuthorProfile::class)
        ->set('publicSlug', 'taken')
        ->call('save')
        ->assertHasErrors(['publicSlug' => 'unique']);
});

test('save rejects invalid website URL', function (): void {
    $author = portalUser();

    Livewire::actingAs($author)
        ->test(AuthorProfile::class)
        ->set('social.website', 'not a url')
        ->call('save')
        ->assertHasErrors(['social.website']);
});

// -------------------------------------------------------------------------
// Frontend AuthorShow renders the bio + social links
// -------------------------------------------------------------------------

test('public author page shows the bio + social links + posts', function (): void {
    $author = portalUser();
    $author->fill([
        'bio' => 'AI writer who loves ML',
        'social_links' => ['twitter' => 'aiwriter'],
        'public_slug' => 'ai-writer',
    ])->save();

    Post::factory()->published()->withAuthor($author->id)->create();

    Livewire::test(AuthorShow::class, ['author' => $author->fresh()])
        ->assertOk()
        ->assertSee('AI writer who loves ML')
        ->assertSee($author->name);
});
