<?php

declare(strict_types=1);

use App\Actions\Comment\ApproveCommentAction;
use App\Actions\Comment\CreateCommentAction;
use App\Actions\Comment\DeleteCommentAction;
use App\Actions\Comment\MarkSpamAction;
use App\Livewire\Admin\Comments\Index as AdminCommentsIndex;
use App\Livewire\Frontend\PostComments;
use App\Models\Comment;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function moderatorUser(string $role = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $r = Role::query()->where('name', $role)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($r);

    return $user->fresh();
}

// -------------------------------------------------------------------------
// CreateCommentAction
// -------------------------------------------------------------------------

test('authenticated user comment is auto-approved', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();
    $user = User::factory()->create();

    $comment = app(CreateCommentAction::class)->handle($post, $user, ['body' => 'Great article!']);

    expect($comment->status)->toBe(Comment::STATUS_APPROVED);
    expect($comment->approved_at)->not->toBeNull();
    expect($comment->user_id)->toBe($user->id);
});

test('first-time guest comment is held pending', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();

    $comment = app(CreateCommentAction::class)->handle($post, null, [
        'body' => 'Hello from a first-time guest',
        'guest_name' => 'Alice',
        'guest_email' => 'alice@example.com',
    ]);

    expect($comment->status)->toBe(Comment::STATUS_PENDING);
    expect($comment->guest_email)->toBe('alice@example.com');
});

test('returning guest with a prior approved comment skips moderation', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();

    // Seed an approved historical comment.
    Comment::factory()->approved()->fromGuest('returning@example.com')->create();

    $comment = app(CreateCommentAction::class)->handle($post, null, [
        'body' => 'Returning with another thought',
        'guest_name' => 'Bob',
        'guest_email' => 'returning@example.com',
    ]);

    expect($comment->status)->toBe(Comment::STATUS_APPROVED);
});

test('comment refuses when allow_comments is false on the post', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => false])->create();

    expect(fn () => app(CreateCommentAction::class)->handle($post, null, [
        'body' => 'will not be saved',
        'guest_name' => 'X',
        'guest_email' => 'x@example.com',
    ]))->toThrow(ValidationException::class);
});

test('comment refuses on a draft post', function (): void {
    $post = Post::factory()->draft()->state(['allow_comments' => true])->create();

    expect(fn () => app(CreateCommentAction::class)->handle($post, null, [
        'body' => 'on a draft',
        'guest_name' => 'X',
        'guest_email' => 'x@example.com',
    ]))->toThrow(ValidationException::class);
});

test('comment refuses an empty body', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();

    expect(fn () => app(CreateCommentAction::class)->handle($post, User::factory()->create(), [
        'body' => '',
    ]))->toThrow(ValidationException::class);
});

test('guest comment refuses missing email', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();

    expect(fn () => app(CreateCommentAction::class)->handle($post, null, [
        'body' => 'something',
        'guest_name' => 'Anon',
    ]))->toThrow(ValidationException::class);
});

test('reply attaches to a parent on the same post', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();
    $parent = Comment::factory()->approved()->state(['post_id' => $post->id])->create();
    $user = User::factory()->create();

    $reply = app(CreateCommentAction::class)->handle($post, $user, [
        'body' => 'Reply body',
        'parent_id' => $parent->id,
    ]);

    expect($reply->parent_id)->toBe($parent->id);
});

test('reply to a reply is flattened to the top-level ancestor', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();
    $top = Comment::factory()->approved()->state(['post_id' => $post->id])->create();
    $mid = Comment::factory()->approved()->state(['post_id' => $post->id, 'parent_id' => $top->id])->create();
    $user = User::factory()->create();

    $reply = app(CreateCommentAction::class)->handle($post, $user, [
        'body' => 'Goes flat',
        'parent_id' => $mid->id,
    ]);

    expect($reply->parent_id)->toBe($top->id);
});

test('reply refuses if parent belongs to a different post', function (): void {
    $post1 = Post::factory()->published()->state(['allow_comments' => true])->create();
    $post2 = Post::factory()->published()->state(['allow_comments' => true])->create();
    $foreignParent = Comment::factory()->approved()->state(['post_id' => $post2->id])->create();
    $user = User::factory()->create();

    expect(fn () => app(CreateCommentAction::class)->handle($post1, $user, [
        'body' => 'wrong-post reply',
        'parent_id' => $foreignParent->id,
    ]))->toThrow(ValidationException::class);
});

// -------------------------------------------------------------------------
// Moderation actions
// -------------------------------------------------------------------------

test('approve flips a pending comment to approved + stamps moderator', function (): void {
    $comment = Comment::factory()->pending()->create();
    $mod = moderatorUser();

    $result = app(ApproveCommentAction::class)->handle($comment, $mod);

    expect($result->status)->toBe(Comment::STATUS_APPROVED);
    expect($result->approved_at)->not->toBeNull();
    expect($result->moderated_by)->toBe($mod->id);
});

test('markSpam flips status to spam', function (): void {
    $comment = Comment::factory()->approved()->create();
    $mod = moderatorUser();

    $result = app(MarkSpamAction::class)->handle($comment, $mod);

    expect($result->status)->toBe(Comment::STATUS_SPAM);
    expect($result->moderated_by)->toBe($mod->id);
});

test('delete soft-deletes the comment', function (): void {
    $comment = Comment::factory()->approved()->create();
    $mod = moderatorUser();

    app(DeleteCommentAction::class)->handle($comment, $mod);

    expect(Comment::query()->find($comment->id))->toBeNull();
    expect(Comment::onlyTrashed()->whereKey($comment->id)->exists())->toBeTrue();
});

// -------------------------------------------------------------------------
// Frontend Livewire
// -------------------------------------------------------------------------

test('frontend comments component lists approved comments only', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();
    $approved = Comment::factory()->approved()->state(['post_id' => $post->id])->create();
    $pending = Comment::factory()->pending()->state(['post_id' => $post->id])->create();
    $spam = Comment::factory()->spam()->state(['post_id' => $post->id])->create();

    $component = Livewire::test(PostComments::class, ['post' => $post]);

    expect($component->instance()->thread->pluck('id'))->toContain($approved->id);
    expect($component->instance()->thread->pluck('id'))->not->toContain($pending->id);
    expect($component->instance()->thread->pluck('id'))->not->toContain($spam->id);
});

test('frontend submit creates a guest comment in pending state', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();

    Livewire::test(PostComments::class, ['post' => $post])
        ->set('body', 'Hello from a guest visitor')
        ->set('guestName', 'Alice')
        ->set('guestEmail', 'alice@example.com')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Comment::query()->where('post_id', $post->id)->pending()->count())->toBe(1);
});

test('frontend submit auto-approves an authenticated commenter', function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();

    Livewire::actingAs($user)
        ->test(PostComments::class, ['post' => $post])
        ->set('body', 'A signed-in comment')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Comment::query()->where('post_id', $post->id)->approved()->count())->toBe(1);
});

test('honeypot silently swallows the submission', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();

    Livewire::test(PostComments::class, ['post' => $post])
        ->set('body', 'bot body')
        ->set('guestName', 'Bot')
        ->set('guestEmail', 'bot@example.com')
        ->set('hp', 'caught')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Comment::query()->count())->toBe(0);
});

test('frontend rejects invalid guest email', function (): void {
    $post = Post::factory()->published()->state(['allow_comments' => true])->create();

    Livewire::test(PostComments::class, ['post' => $post])
        ->set('body', 'body')
        ->set('guestName', 'X')
        ->set('guestEmail', 'not-an-email')
        ->call('submit')
        ->assertHasErrors(['guestEmail']);
});

// -------------------------------------------------------------------------
// Admin moderation queue
// -------------------------------------------------------------------------

test('users without comments.moderate are denied access', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)->test(AdminCommentsIndex::class)->assertForbidden();
});

test('admin sees pending comments first by default', function (): void {
    $admin = moderatorUser();
    Comment::factory()->pending()->count(3)->create();
    Comment::factory()->approved()->count(2)->create();

    $component = Livewire::actingAs($admin)->test(AdminCommentsIndex::class);

    expect($component->instance()->comments->total())->toBe(3);
});

test('switching status filter updates the list', function (): void {
    $admin = moderatorUser();
    Comment::factory()->pending()->count(2)->create();
    Comment::factory()->spam()->count(5)->create();

    $component = Livewire::actingAs($admin)
        ->test(AdminCommentsIndex::class)
        ->set('statusFilter', Comment::STATUS_SPAM);

    expect($component->instance()->comments->total())->toBe(5);
});

test('admin approve action flips a pending comment', function (): void {
    $admin = moderatorUser();
    $comment = Comment::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test(AdminCommentsIndex::class)
        ->call('approve', $comment->id);

    expect($comment->fresh()->status)->toBe(Comment::STATUS_APPROVED);
});

test('admin markSpam works', function (): void {
    $admin = moderatorUser();
    $comment = Comment::factory()->approved()->create();

    Livewire::actingAs($admin)
        ->test(AdminCommentsIndex::class)
        ->call('markSpam', $comment->id);

    expect($comment->fresh()->status)->toBe(Comment::STATUS_SPAM);
});

test('admin delete soft-deletes the comment', function (): void {
    $admin = moderatorUser();
    $comment = Comment::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test(AdminCommentsIndex::class)
        ->call('deleteComment', $comment->id);

    expect(Comment::query()->find($comment->id))->toBeNull();
});

test('bulk approve flips every selected comment', function (): void {
    $admin = moderatorUser();
    $a = Comment::factory()->pending()->create();
    $b = Comment::factory()->pending()->create();
    $c = Comment::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test(AdminCommentsIndex::class)
        ->set('selectedIds', [$a->id, $b->id])
        ->call('bulkApprove');

    expect($a->fresh()->status)->toBe(Comment::STATUS_APPROVED);
    expect($b->fresh()->status)->toBe(Comment::STATUS_APPROVED);
    expect($c->fresh()->status)->toBe(Comment::STATUS_PENDING);     // untouched
});

test('counts computed reports per-status totals', function (): void {
    $admin = moderatorUser();
    Comment::factory()->pending()->count(2)->create();
    Comment::factory()->approved()->count(3)->create();
    Comment::factory()->spam()->count(1)->create();

    $component = Livewire::actingAs($admin)->test(AdminCommentsIndex::class);
    $counts = $component->instance()->counts;

    expect($counts['pending'])->toBe(2);
    expect($counts['approved'])->toBe(3);
    expect($counts['spam'])->toBe(1);
});
