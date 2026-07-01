<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function makeUserAs(string $roleName): User
{
    $user = User::factory()->create();
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('super admin can do anything via Gate::before short-circuit', function (): void {
    $superAdmin = makeUserAs('Super Admin');
    $post = Post::factory()->published()->create();

    expect($superAdmin->can('update', $post))->toBeTrue();
    expect($superAdmin->can('delete', $post))->toBeTrue();
    expect($superAdmin->can('publish', $post))->toBeTrue();
});

test('author can edit only their own non-published posts', function (): void {
    $author = makeUserAs('Author');
    $other = makeUserAs('Author');

    $own = Post::factory()->draft()->withAuthor($author->id)->create();
    $otherPost = Post::factory()->draft()->withAuthor($other->id)->create();
    $ownPublished = Post::factory()->published()->withAuthor($author->id)->create();

    expect($author->can('update', $own))->toBeTrue();
    expect($author->can('update', $otherPost))->toBeFalse();
    expect($author->can('update', $ownPublished))->toBeFalse();   // can't edit after publish
});

test('author cannot publish', function (): void {
    $author = makeUserAs('Author');
    $post = Post::factory()->state([
        'status' => PostStatus::Approved,
        'author_id' => $author->id,
    ])->create();

    expect($author->can('publish', $post))->toBeFalse();
});

test('editor can update anyone\'s post and approve / reject / request changes', function (): void {
    $editor = makeUserAs('Editor');
    $author = makeUserAs('Author');
    $post = Post::factory()->pendingReview()->withAuthor($author->id)->create();

    expect($editor->can('update', $post))->toBeTrue();
    expect($editor->can('approve', $post))->toBeTrue();
    expect($editor->can('reject', $post))->toBeTrue();
    expect($editor->can('requestChanges', $post))->toBeTrue();
    expect($editor->can('publish', $post))->toBeTrue();
});

test('editor cannot delete published posts', function (): void {
    $editor = makeUserAs('Editor');
    $post = Post::factory()->published()->create();

    expect($editor->can('delete', $post))->toBeFalse();
});

test('contributor can create + edit own drafts but cannot publish', function (): void {
    $contributor = makeUserAs('Contributor');
    $post = Post::factory()->draft()->withAuthor($contributor->id)->create();

    expect($contributor->can('create', Post::class))->toBeTrue();
    expect($contributor->can('update', $post))->toBeTrue();
    expect($contributor->can('publish', $post))->toBeFalse();
});

test('subscriber cannot create or update any post', function (): void {
    $subscriber = makeUserAs('Subscriber');
    $post = Post::factory()->draft()->create();

    expect($subscriber->can('create', Post::class))->toBeFalse();
    expect($subscriber->can('update', $post))->toBeFalse();
});

test('submitForReview policy is true only for owner with valid source state', function (): void {
    $author = makeUserAs('Author');
    $other = makeUserAs('Author');

    $own = Post::factory()->draft()->withAuthor($author->id)->create();
    $ownPublished = Post::factory()->published()->withAuthor($author->id)->create();
    $othersDraft = Post::factory()->draft()->withAuthor($other->id)->create();

    expect($author->can('submitForReview', $own))->toBeTrue();
    expect($author->can('submitForReview', $ownPublished))->toBeFalse();  // bad source state
    expect($author->can('submitForReview', $othersDraft))->toBeFalse();   // not owner
});
