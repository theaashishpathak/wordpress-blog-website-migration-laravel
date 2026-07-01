<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Livewire\Admin\Editorial\Kanban;
use App\Models\EditorialNote;
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

function kanbanUser(string $roleName = 'Editor'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('mount denies users without editorial.review_queue permission', function (): void {
    $author = kanbanUser('Author');

    Livewire::actingAs($author)->test(Kanban::class)->assertForbidden();
});

test('editor can render the kanban', function (): void {
    $editor = kanbanUser('Editor');
    Post::factory()->pendingReview()->create();

    Livewire::actingAs($editor)
        ->test(Kanban::class)
        ->assertOk();
});

test('posts land in the right columns based on status', function (): void {
    $editor = kanbanUser('Editor');

    $pending = Post::factory()->pendingReview()->create();
    $approved = Post::factory()->state(['status' => PostStatus::Approved])->create();

    $component = Livewire::actingAs($editor)->test(Kanban::class);
    $columns = $component->instance()->columns;

    expect($columns[PostStatus::PendingReview->value]['posts']->pluck('id'))->toContain($pending->id);
    expect($columns[PostStatus::Approved->value]['posts']->pluck('id'))->toContain($approved->id);

    expect($columns[PostStatus::Approved->value]['posts']->pluck('id'))->not->toContain($pending->id);
});

test('move() approves a pending post via ApprovePostAction and writes an editorial note', function (): void {
    $editor = kanbanUser('Editor');
    $post = Post::factory()->pendingReview()->create();

    Livewire::actingAs($editor)
        ->test(Kanban::class)
        ->call('move', PostStatus::Approved->value, $post->id)
        ->assertDispatched('toast.success');

    expect($post->fresh()->status)->toBe(PostStatus::Approved);
    expect(EditorialNote::query()->where('post_id', $post->id)->ofType(EditorialNote::TYPE_APPROVE)->exists())->toBeTrue();
});

test('move() rejects an illegal transition with a danger toast and leaves status alone', function (): void {
    $editor = kanbanUser('Editor');
    // Draft → Scheduled is not allowed by the state machine.
    $post = Post::factory()->draft()->create();

    Livewire::actingAs($editor)
        ->test(Kanban::class)
        ->call('move', PostStatus::Scheduled->value, $post->id)
        ->assertDispatched('toast.danger');

    expect($post->fresh()->status)->toBe(PostStatus::Draft);
});

test('move() flips InReview without going through an Action', function (): void {
    $editor = kanbanUser('Editor');
    $post = Post::factory()->pendingReview()->create();

    Livewire::actingAs($editor)
        ->test(Kanban::class)
        ->call('move', PostStatus::InReview->value, $post->id);

    expect($post->fresh()->status)->toBe(PostStatus::InReview);
});

test('move() to Scheduled requires a future scheduled_at and otherwise fails gracefully', function (): void {
    $editor = kanbanUser('Editor');
    $post = Post::factory()->state(['status' => PostStatus::Approved, 'scheduled_at' => null])->create();

    Livewire::actingAs($editor)
        ->test(Kanban::class)
        ->call('move', PostStatus::Scheduled->value, $post->id)
        ->assertDispatched('toast.danger');

    expect($post->fresh()->status)->toBe(PostStatus::Approved);
});

test('move() to Scheduled succeeds when scheduled_at is in the future', function (): void {
    $admin = kanbanUser('Admin');     // Admin has publish permission
    $post = Post::factory()->state([
        'status' => PostStatus::Approved,
        'scheduled_at' => now()->addDays(2),
    ])->create();

    Livewire::actingAs($admin)
        ->test(Kanban::class)
        ->call('move', PostStatus::Scheduled->value, $post->id);

    expect($post->fresh()->status)->toBe(PostStatus::Scheduled);
});

test('search filter narrows posts by title', function (): void {
    $editor = kanbanUser('Editor');

    $a = Post::factory()->pendingReview()->create();
    $b = Post::factory()->pendingReview()->create();
    $a->translations()->first()->update(['title' => 'AI Marketing Trends']);
    $b->translations()->first()->update(['title' => 'Quantum Computing']);

    $component = Livewire::actingAs($editor)->test(Kanban::class)
        ->set('search', 'Quantum');

    $pendingPosts = $component->instance()->columns[PostStatus::PendingReview->value]['posts'];

    expect($pendingPosts->pluck('id'))->toContain($b->id);
    expect($pendingPosts->pluck('id'))->not->toContain($a->id);
});

test('My queue filter restricts to posts authored by — or noted on by — the current user', function (): void {
    $editor = kanbanUser('Editor');
    $other = User::factory()->create();

    $mine = Post::factory()->pendingReview()->withAuthor($editor->id)->create();
    $theirs = Post::factory()->pendingReview()->withAuthor($other->id)->create();

    $component = Livewire::actingAs($editor)->test(Kanban::class)
        ->set('onlyMine', true);

    $pending = $component->instance()->columns[PostStatus::PendingReview->value]['posts'];

    expect($pending->pluck('id'))->toContain($mine->id);
    expect($pending->pluck('id'))->not->toContain($theirs->id);
});

test('clearFilters resets all filter state', function (): void {
    $editor = kanbanUser('Editor');

    Livewire::actingAs($editor)
        ->test(Kanban::class)
        ->set('search', 'something')
        ->set('onlyMine', true)
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('onlyMine', false)
        ->assertSet('categoryFilter', '')
        ->assertSet('authorFilter', '');
});
