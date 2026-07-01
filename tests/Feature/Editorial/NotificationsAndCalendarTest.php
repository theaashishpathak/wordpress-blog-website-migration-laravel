<?php

declare(strict_types=1);

use App\Actions\Comment\CreateCommentAction;
use App\Actions\Editorial\ApprovePostAction;
use App\Actions\Editorial\RejectPostAction;
use App\Actions\Editorial\RequestChangesAction;
use App\Actions\Editorial\SubmitForReviewAction;
use App\Enums\PostStatus;
use App\Livewire\Admin\Editorial\Calendar;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Editorial\ChangesRequestedOnPost;
use App\Notifications\Editorial\NewCommentOnPost;
use App\Notifications\Editorial\PostApproved;
use App\Notifications\Editorial\PostRejected;
use App\Notifications\Editorial\PostSubmittedForReview;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
    Notification::fake();
});

function userWithRole(string $roleName): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

// -------------------------------------------------------------------------
// Workflow notifications
// -------------------------------------------------------------------------

test('SubmitForReviewAction notifies users with editorial.approve permission', function (): void {
    $author = userWithRole('Author');
    $editor = userWithRole('Editor');
    $admin = userWithRole('Admin');

    $post = Post::factory()->draft()->withAuthor($author->id)->create();

    app(SubmitForReviewAction::class)->handle($post, $author);

    Notification::assertSentTo($editor, PostSubmittedForReview::class);
    Notification::assertSentTo($admin, PostSubmittedForReview::class);
    Notification::assertNotSentTo($author, PostSubmittedForReview::class);   // author shouldn't ping self
});

test('ApprovePostAction notifies the author', function (): void {
    $author = userWithRole('Author');
    $editor = userWithRole('Editor');
    $post = Post::factory()->pendingReview()->withAuthor($author->id)->create();

    app(ApprovePostAction::class)->handle($post, $editor, 'Looks good');

    Notification::assertSentTo($author, PostApproved::class, function ($notif) use ($editor) {
        return $notif->editor->id === $editor->id;
    });
});

test('ApprovePostAction skips notification when editor is the author', function (): void {
    $admin = userWithRole('Admin');
    $post = Post::factory()->pendingReview()->withAuthor($admin->id)->create();

    app(ApprovePostAction::class)->handle($post, $admin);

    Notification::assertNothingSentTo($admin);
});

test('RejectPostAction notifies the author with the reason', function (): void {
    $author = userWithRole('Author');
    $editor = userWithRole('Editor');
    $post = Post::factory()->pendingReview()->withAuthor($author->id)->create();

    app(RejectPostAction::class)->handle($post, $editor, 'Too thin');

    Notification::assertSentTo($author, PostRejected::class, function ($notif) {
        return $notif->reason === 'Too thin';
    });
});

test('RequestChangesAction notifies the author with feedback', function (): void {
    $author = userWithRole('Author');
    $editor = userWithRole('Editor');
    $post = Post::factory()->pendingReview()->withAuthor($author->id)->create();

    app(RequestChangesAction::class)->handle($post, $editor, 'Tighten the intro');

    Notification::assertSentTo($author, ChangesRequestedOnPost::class, function ($notif) {
        return $notif->feedback === 'Tighten the intro';
    });
});

// -------------------------------------------------------------------------
// Comment notification
// -------------------------------------------------------------------------

test('CreateCommentAction notifies post author about new approved comment', function (): void {
    $author = userWithRole('Author');
    $commenter = User::factory()->create();
    $post = Post::factory()->published()->state(['allow_comments' => true])->withAuthor($author->id)->create();

    app(CreateCommentAction::class)->handle($post, $commenter, ['body' => 'Nice piece!']);

    Notification::assertSentTo($author, NewCommentOnPost::class);
});

test('CreateCommentAction notifies post author for guest comment too', function (): void {
    $author = userWithRole('Author');
    $post = Post::factory()->published()->state(['allow_comments' => true])->withAuthor($author->id)->create();

    app(CreateCommentAction::class)->handle($post, null, [
        'body' => 'Hello from a guest',
        'guest_name' => 'Alice',
        'guest_email' => 'alice@example.com',
    ]);

    Notification::assertSentTo($author, NewCommentOnPost::class);
});

test('CreateCommentAction skips notification when commenter IS the post author', function (): void {
    $author = userWithRole('Author');
    $post = Post::factory()->published()->state(['allow_comments' => true])->withAuthor($author->id)->create();

    app(CreateCommentAction::class)->handle($post, $author, ['body' => 'Self comment']);

    Notification::assertNothingSentTo($author);
});

// -------------------------------------------------------------------------
// Editorial Calendar
// -------------------------------------------------------------------------

test('users without editorial.calendar are denied', function (): void {
    $u = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($u)->test(Calendar::class)->assertForbidden();
});

test('admin can view the editorial calendar', function (): void {
    $admin = userWithRole('Admin');

    Livewire::actingAs($admin)
        ->test(Calendar::class)
        ->assertOk()
        ->assertSee(now()->format('F Y'));
});

test('calendar lands on current month by default', function (): void {
    $admin = userWithRole('Admin');

    Livewire::actingAs($admin)
        ->test(Calendar::class)
        ->assertSet('month', now()->format('Y-m'));
});

test('previousMonth + nextMonth navigate correctly', function (): void {
    $admin = userWithRole('Admin');

    $component = Livewire::actingAs($admin)
        ->test(Calendar::class)
        ->set('month', '2026-05')
        ->call('previousMonth');

    expect($component->get('month'))->toBe('2026-04');

    $component->call('nextMonth')->call('nextMonth');
    expect($component->get('month'))->toBe('2026-06');
});

test('jumpToday resets the month', function (): void {
    $admin = userWithRole('Admin');

    Livewire::actingAs($admin)
        ->test(Calendar::class)
        ->set('month', '2020-01')
        ->call('jumpToday')
        ->assertSet('month', now()->format('Y-m'));
});

test('postsByDay groups posts under the right day', function (): void {
    $admin = userWithRole('Admin');

    $today = now()->startOfDay();
    Post::factory()->published()->create([
        'published_at' => $today->copy()->addHours(9),
    ]);
    Post::factory()->state([
        'status' => PostStatus::Scheduled->value,
        'scheduled_at' => $today->copy()->addDays(3),
    ])->create();

    $component = Livewire::actingAs($admin)->test(Calendar::class);
    $byDay = $component->instance()->postsByDay;

    expect($byDay)->toHaveKey($today->format('Y-m-d'));
    expect($byDay)->toHaveKey($today->copy()->addDays(3)->format('Y-m-d'));
});

test('author filter narrows visible posts', function (): void {
    $admin = userWithRole('Admin');
    $author1 = userWithRole('Author');
    $author2 = userWithRole('Author');

    Post::factory()->published()->withAuthor($author1->id)->create(['published_at' => now()]);
    Post::factory()->published()->withAuthor($author2->id)->create(['published_at' => now()]);

    $component = Livewire::actingAs($admin)
        ->test(Calendar::class)
        ->set('authorFilter', (string) $author1->id);

    $allPosts = collect($component->instance()->postsByDay)->flatten(1);
    expect($allPosts->pluck('author_id')->unique()->all())->toBe([$author1->id]);
});

test('status filter narrows visible posts', function (): void {
    $admin = userWithRole('Admin');
    Post::factory()->published()->create(['published_at' => now()]);
    Post::factory()->draft()->create();

    $component = Livewire::actingAs($admin)
        ->test(Calendar::class)
        ->set('statusFilter', PostStatus::Published->value);

    $statuses = collect($component->instance()->postsByDay)->flatten(1)->pluck('status.value');
    expect($statuses->unique()->all())->toBe([PostStatus::Published->value]);
});

test('days computed produces a Sunday-anchored grid covering the month', function (): void {
    $admin = userWithRole('Admin');

    $component = Livewire::actingAs($admin)
        ->test(Calendar::class)
        ->set('month', '2026-05');

    $days = $component->instance()->days;

    // First day of grid must be a Sunday (Carbon::SUNDAY = 0).
    expect($days[0]['date']->dayOfWeek)->toBe(0);
    // Last day of grid must be a Saturday.
    expect(end($days)['date']->dayOfWeek)->toBe(6);
    // Total cells should be a multiple of 7.
    expect(count($days) % 7)->toBe(0);
});
