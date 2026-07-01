<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Livewire\Admin\Posts\Index;
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

function postsIndexUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('admin sees every post in the index', function (): void {
    $admin = postsIndexUser('Admin');
    Post::factory()->count(3)->published()->create();
    Post::factory()->count(2)->draft()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertOk()
        ->assertViewHas('posts', fn ($posts): bool => $posts->total() === 5);
});

test('author only sees their own posts', function (): void {
    $author = postsIndexUser('Author');
    $other = postsIndexUser('Author');

    Post::factory()->count(2)->withAuthor($author->id)->create();
    Post::factory()->count(3)->withAuthor($other->id)->create();

    Livewire::actingAs($author)
        ->test(Index::class)
        ->assertOk()
        ->assertViewHas('posts', fn ($posts): bool => $posts->total() === 2);
});

test('type filter narrows the list', function (): void {
    $admin = postsIndexUser('Admin');
    Post::factory()->ofType(PostType::News)->create();
    Post::factory()->ofType(PostType::Post)->create();
    Post::factory()->ofType(PostType::Video)->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('typeFilter', PostType::News->value)
        ->assertViewHas('posts', fn ($posts): bool => $posts->total() === 1);
});

test('status filter narrows the list', function (): void {
    $admin = postsIndexUser('Admin');
    Post::factory()->published()->create();
    Post::factory()->draft()->create();
    Post::factory()->pendingReview()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('statusFilter', PostStatus::PendingReview->value)
        ->assertViewHas('posts', fn ($posts): bool => $posts->total() === 1);
});

test('search filters by translation title', function (): void {
    $admin = postsIndexUser('Admin');

    $target = Post::factory()->create();
    $target->translations()->first()->update(['title' => 'Searchable Beacon Title']);
    Post::factory()->count(2)->create();   // other posts

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('search', 'Beacon')
        ->assertViewHas('posts', fn ($posts): bool => $posts->total() === 1);
});

test('clearFilters resets every filter property', function (): void {
    $admin = postsIndexUser('Admin');
    Post::factory()->count(3)->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('search', 'foo')
        ->set('typeFilter', 'news')
        ->set('statusFilter', 'draft')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('typeFilter', '')
        ->assertSet('statusFilter', '');
});

test('toggleSort flips direction when same field clicked twice', function (): void {
    $admin = postsIndexUser('Admin');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertSet('sortField', 'created_at')
        ->assertSet('sortDirection', 'desc')
        ->call('toggleSort', 'published_at')
        ->assertSet('sortField', 'published_at')
        ->assertSet('sortDirection', 'desc')
        ->call('toggleSort', 'published_at')
        ->assertSet('sortDirection', 'asc');
});

test('toggleSort rejects unknown fields', function (): void {
    $admin = postsIndexUser('Admin');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('toggleSort', 'sql_injection_field')
        ->assertSet('sortField', 'created_at');
});

test('bulkPublish moves selected posts to Published', function (): void {
    $admin = postsIndexUser('Admin');
    // Both posts must be in a state that legally transitions to Published.
    // PendingReview → Published is forbidden by the state machine; editor
    // must Approve first. Approved → Published is the canonical path.
    $a = Post::factory()->state(['status' => PostStatus::Approved])->create();
    $b = Post::factory()->state(['status' => PostStatus::Approved])->create();
    $c = Post::factory()->draft()->create();   // not selected — should stay Draft

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('selectedIds', [$a->id, $b->id])
        ->call('bulkPublish');

    expect($a->fresh()->status)->toBe(PostStatus::Published);
    expect($b->fresh()->status)->toBe(PostStatus::Published);
    expect($c->fresh()->status)->toBe(PostStatus::Draft);
});

test('bulkArchive moves selected posts to Archived', function (): void {
    $admin = postsIndexUser('Admin');
    $a = Post::factory()->published()->create();
    $b = Post::factory()->published()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('selectedIds', [$a->id, $b->id])
        ->call('bulkArchive');

    expect($a->fresh()->status)->toBe(PostStatus::Archived);
    expect($b->fresh()->status)->toBe(PostStatus::Archived);
});

test('bulk actions silently skip selections forbidden by the policy', function (): void {
    $author = postsIndexUser('Author');
    $other = postsIndexUser('Author');

    $ownDraft = Post::factory()->draft()->withAuthor($author->id)->create();
    $othersDraft = Post::factory()->draft()->withAuthor($other->id)->create();

    Livewire::actingAs($author)
        ->test(Index::class)
        ->set('selectedIds', [$ownDraft->id, $othersDraft->id])
        ->call('bulkArchive');

    // Author lacks posts.archive permission entirely, so neither moves.
    expect($ownDraft->fresh()->status)->toBe(PostStatus::Draft);
    expect($othersDraft->fresh()->status)->toBe(PostStatus::Draft);
});

test('posts index route renders for authorised user', function (): void {
    $admin = postsIndexUser('Admin');

    $this->actingAs($admin)
        ->get(route('admin.posts.index'))
        ->assertOk()
        ->assertSee('Posts');
});

test('posts create page renders for authors', function (): void {
    $author = postsIndexUser('Author');

    $this->actingAs($author)
        ->get(route('admin.posts.create'))
        ->assertOk()
        ->assertSee('Create');   // "Create" appears in breadcrumb + page heading
});
