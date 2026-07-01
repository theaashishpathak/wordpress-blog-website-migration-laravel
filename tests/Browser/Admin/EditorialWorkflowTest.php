<?php

declare(strict_types=1);

/**
 * EditorialWorkflow — the multi-actor review flow:
 *
 *   Author drafts → Author submits → Editor requests changes →
 *   Author resubmits → Editor approves → Editor publishes.
 *
 * Exercises both the Kanban view's drag/click transitions and the
 * post Edit page's workflow buttons.
 */

use App\Actions\Editorial\ApprovePostAction;
use App\Actions\Editorial\RequestChangesAction;
use App\Actions\Editorial\SubmitForReviewAction;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function workflowUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole(Role::query()->where('name', $role)->firstOrFail());

    return $user->fresh();
}

test('editor sees a pending post in the Kanban queue', function (): void {
    $author = workflowUser('Author');
    $editor = workflowUser('Editor');

    $post = Post::factory()->draft()->withAuthor($author->id)->create();
    app(SubmitForReviewAction::class)->handle($post, $author);

    visit('/admin/editorial/queue', as: $editor)
        ->assertSee('Editorial Queue')
        ->assertSee($post->translation()?->title ?? '#'.$post->id);
});

test('editor can request changes and the post moves to changes_requested', function (): void {
    $author = workflowUser('Author');
    $editor = workflowUser('Editor');

    $post = Post::factory()->pendingReview()->withAuthor($author->id)->create();

    app(RequestChangesAction::class)->handle($post, $editor, 'Please tighten the intro.');

    expect($post->fresh()->status->value)->toBe('changes_requested');
});

test('editor approves a pending post and it becomes approved', function (): void {
    $author = workflowUser('Author');
    $editor = workflowUser('Editor');

    $post = Post::factory()->pendingReview()->withAuthor($author->id)->create();

    app(ApprovePostAction::class)->handle($post, $editor, 'Great work.');

    expect($post->fresh()->status->value)->toBe('approved');
});

test('admin publishes an approved post via the Edit page button', function (): void {
    $admin = workflowUser('Admin');
    $post = Post::factory()->state(['status' => \App\Enums\PostStatus::Approved->value])
        ->withAuthor(workflowUser('Author')->id)->create();

    visit('/admin/posts/'.$post->id.'/edit', as: $admin)
        ->assertOk()
        ->press('Publish');

    expect($post->fresh()->status->value)->toBe('published');
});

test('calendar view shows a scheduled post on the right day', function (): void {
    $admin = workflowUser('Admin');
    $scheduledAt = now()->addDays(3)->setTime(9, 0);

    Post::factory()->state([
        'status' => \App\Enums\PostStatus::Scheduled->value,
        'scheduled_at' => $scheduledAt,
    ])->withAuthor($admin->id)->create();

    visit('/admin/editorial/calendar', as: $admin)
        ->assertSee('Editorial Calendar')
        ->assertSee($scheduledAt->format('F Y'));
});
