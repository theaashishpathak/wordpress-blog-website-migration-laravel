<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Livewire\Admin\Posts\Create;
use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
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

function createUser(string $roleName = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('saveDraft creates a post with default-language translation and tags', function (): void {
    $admin = createUser('Admin');
    $category = Category::factory()->create();
    $tag1 = Tag::factory()->create();
    $tag2 = Tag::factory()->create();
    $language = Language::query()->default()->firstOrFail();

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('title', 'My First AI-Written Article')
        ->set('content', '<p>Body content here.</p>')
        ->set('excerpt', 'Short summary.')
        ->set('categoryId', $category->id)
        ->set('defaultLanguageId', $language->id)
        ->set('tagIds', [$tag1->id, $tag2->id])
        ->set('isFeatured', true)
        ->call('saveDraft')
        ->assertHasNoErrors()
        ->assertRedirect();

    $post = Post::query()->latest('id')->first();

    expect($post)->not->toBeNull();
    expect($post->status)->toBe(PostStatus::Draft);
    expect($post->author_id)->toBe($admin->id);
    expect($post->is_featured)->toBeTrue();
    expect($post->translate('title', 'en'))->toBe('My First AI-Written Article');
    expect($post->category_id)->toBe($category->id);
    expect($post->tags()->pluck('tags.id')->all())->toContain($tag1->id, $tag2->id);
});

test('title is required', function (): void {
    $admin = createUser('Admin');

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('title', '')
        ->call('saveDraft')
        ->assertHasErrors(['title' => 'required']);
});

test('slug auto-derives from title when blank', function (): void {
    $admin = createUser('Admin');

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('title', 'Breaking AI News Update')
        ->assertSet('slug', 'breaking-ai-news-update');
});

test('slug stays user-edited once manually typed', function (): void {
    $admin = createUser('Admin');

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('slug', 'custom-slug')
        ->set('title', 'New Title That Should Not Override Slug')
        ->assertSet('slug', 'custom-slug');
});

test('saveAndSubmit transitions to PendingReview after creation', function (): void {
    $author = createUser('Author');
    $language = Language::query()->default()->firstOrFail();

    Livewire::actingAs($author)
        ->test(Create::class)
        ->set('title', 'Pitch idea')
        ->set('content', '<p>Body</p>')
        ->set('defaultLanguageId', $language->id)
        ->call('saveAndSubmit')
        ->assertRedirect();

    $post = Post::query()->latest('id')->first();
    expect($post->status)->toBe(PostStatus::PendingReview);
    expect($post->editorialNotes()->count())->toBe(0);   // no note was passed
});

test('savePublish is rejected for users without publish permission', function (): void {
    $author = createUser('Author');
    $language = Language::query()->default()->firstOrFail();

    Livewire::actingAs($author)
        ->test(Create::class)
        ->set('title', 'I should not be able to publish directly')
        ->set('content', '<p>Body</p>')
        ->set('defaultLanguageId', $language->id)
        ->call('savePublish')
        ->assertForbidden();
});

test('savePublish moves status to Published when user has permission', function (): void {
    $admin = createUser('Admin');
    $language = Language::query()->default()->firstOrFail();

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('title', 'Direct-publish test')
        ->set('content', '<p>Body</p>')
        ->set('defaultLanguageId', $language->id)
        ->call('savePublish')
        ->assertRedirect();

    $post = Post::query()->latest('id')->first();
    expect($post->status)->toBe(PostStatus::Published);
    expect($post->published_at)->not->toBeNull();
});

test('subscribers cannot mount the Create component', function (): void {
    $subscriber = createUser('Subscriber');

    Livewire::actingAs($subscriber)
        ->test(Create::class)
        ->assertForbidden();
});
