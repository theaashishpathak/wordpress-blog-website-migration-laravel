<?php

declare(strict_types=1);

/**
 * ReadingAndComment — the public reader journey:
 *
 *   Homepage → category page → single post → leave a comment → admin
 *   moderates it.
 *
 * Comments default to pending unless `Allow All` is configured, so the
 * admin moderation hop is part of the happy path.
 */

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Comment;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();

    $this->category = Category::factory()->withoutTranslations()->create();
    CategoryTranslation::query()->create([
        'category_id' => $this->category->id,
        'language_id' => $this->english->id,
        'name' => 'Tech',
        'slug' => 'tech',
    ]);

    $author = User::factory()->create(['email_verified_at' => now()]);
    $this->post = Post::factory()->published()
        ->withAuthor($author->id)
        ->state(['category_id' => $this->category->id, 'allow_comments' => true])
        ->create();

    PostTranslation::query()->where('post_id', $this->post->id)
        ->update(['title' => 'Hello AI World', 'slug' => 'hello-ai-world']);
});

test('visitor can browse from home to a single post via the category page', function (): void {
    visit('/')
        ->assertOk()
        ->assertSee('Hello AI World')
        ->click('a[href*="/category/tech"]')
        ->assertPathContains('/category/tech')
        ->assertSee('Tech')
        ->click('a[href$="/hello-ai-world"]')
        ->assertPathContains('/hello-ai-world')
        ->assertSee('Hello AI World');
});

test('visitor can submit a guest comment which lands as pending', function (): void {
    visit('/hello-ai-world')
        ->assertOk()
        ->fill('input[name="guest_name"]', 'Curious Reader')
        ->fill('input[name="guest_email"]', 'reader@example.com')
        ->fill('textarea[name="body"]', 'Great article! Looking forward to more.')
        ->press('Post Comment')
        ->assertSee('awaiting moderation');

    expect(Comment::query()->where('post_id', $this->post->id)->count())->toBe(1);
});

test('admin approves a pending comment from the moderation queue', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(Role::query()->where('name', 'Admin')->firstOrFail());

    $comment = Comment::factory()->state([
        'post_id' => $this->post->id,
        'status' => 'pending',
    ])->create();

    visit('/admin/comments', as: $admin->fresh())
        ->assertSee('Comment Moderation')
        ->assertSee($comment->body)
        ->press('Approve');

    expect($comment->fresh()->status)->toBe('approved');
});
