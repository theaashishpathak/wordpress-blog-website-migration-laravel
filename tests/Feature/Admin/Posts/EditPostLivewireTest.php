<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Livewire\Admin\Posts\Edit;
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

function editUser(string $roleName): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('mounts with existing post values populated into form fields', function (): void {
    $admin = editUser('Admin');
    $post = Post::factory()->draft()->create();
    $post->translations()->first()->update([
        'title' => 'Original Title',
        'content' => '<p>Original body</p>',
    ]);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post->fresh()])
        ->assertSet('title', 'Original Title')
        ->assertSet('content', '<p>Original body</p>');
});

test('save() updates the post and creates a revision', function (): void {
    $admin = editUser('Admin');
    $post = Post::factory()->draft()->create();

    expect($post->revisions()->count())->toBe(0);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->set('title', 'Renamed Title')
        ->set('content', '<p>Edited body</p>')
        ->call('save');

    $post->refresh();
    expect($post->translate('title'))->toBe('Renamed Title');
    expect($post->translate('content'))->toBe('<p>Edited body</p>');
    expect($post->revisions()->count())->toBe(1);
});

test('author can submit own draft for review with a note', function (): void {
    $author = editUser('Author');
    $post = Post::factory()->draft()->withAuthor($author->id)->create();

    Livewire::actingAs($author)
        ->test(Edit::class, ['post' => $post])
        ->set('editorialNote', 'Ready for review!')
        ->call('submitForReview');

    $post->refresh();
    expect($post->status)->toBe(PostStatus::PendingReview);
    expect($post->editorialNotes()->count())->toBe(1);
    expect($post->editorialNotes()->first()->body)->toBe('Ready for review!');
});

test('editor can approve a pending post with note creating an approve EditorialNote', function (): void {
    $editor = editUser('Editor');
    $post = Post::factory()->pendingReview()->create();

    Livewire::actingAs($editor)
        ->test(Edit::class, ['post' => $post])
        ->set('editorialNote', 'Looks great!')
        ->call('approve');

    $post->refresh();
    expect($post->status)->toBe(PostStatus::Approved);
    expect($post->editorialNotes()->ofType(EditorialNote::TYPE_APPROVE)->count())->toBe(1);
});

test('editor request-changes requires feedback note', function (): void {
    $editor = editUser('Editor');
    $post = Post::factory()->pendingReview()->create();

    Livewire::actingAs($editor)
        ->test(Edit::class, ['post' => $post])
        ->set('editorialNote', '')                       // intentionally blank
        ->call('requestChanges');

    // Status should NOT change because RequestChangesAction throws ValidationException.
    expect($post->fresh()->status)->toBe(PostStatus::PendingReview);
});

test('editor reject moves to Rejected when reason supplied', function (): void {
    $editor = editUser('Editor');
    $post = Post::factory()->pendingReview()->create();

    Livewire::actingAs($editor)
        ->test(Edit::class, ['post' => $post])
        ->set('editorialNote', 'Off-brand content.')
        ->call('reject');

    expect($post->fresh()->status)->toBe(PostStatus::Rejected);
});

test('admin publish moves Approved post to Published', function (): void {
    $admin = editUser('Admin');
    $post = Post::factory()->state(['status' => PostStatus::Approved])->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->call('publish');

    expect($post->fresh()->status)->toBe(PostStatus::Published);
});

test('admin archive moves Published post to Archived', function (): void {
    $admin = editUser('Admin');
    $post = Post::factory()->published()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->call('archive');

    expect($post->fresh()->status)->toBe(PostStatus::Archived);
});

test('author cannot edit another author\'s draft', function (): void {
    $author = editUser('Author');
    $other = editUser('Author');
    $post = Post::factory()->draft()->withAuthor($other->id)->create();

    Livewire::actingAs($author)
        ->test(Edit::class, ['post' => $post])
        ->assertForbidden();
});

test('author cannot publish even own approved post', function (): void {
    $author = editUser('Author');
    $post = Post::factory()->state(['status' => PostStatus::Approved])->withAuthor($author->id)->create();

    // Author mount fails because update() permission denies edit on Approved posts.
    Livewire::actingAs($author)
        ->test(Edit::class, ['post' => $post])
        ->assertForbidden();
});
