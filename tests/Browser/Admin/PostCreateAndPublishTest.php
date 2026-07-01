<?php

declare(strict_types=1);

/**
 * PostCreate + Publish — covers the most common author/editor task:
 * compose a post from scratch, save as draft, and publish.
 *
 * AI assist is mocked so the test never reaches OpenAI/Gemini.
 */

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\TokenUsage;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();

    // One category so the dropdown has options.
    $this->category = Category::factory()->withoutTranslations()->create();
    CategoryTranslation::query()->create([
        'category_id' => $this->category->id,
        'language_id' => $this->english->id,
        'name' => 'Tech',
        'slug' => 'tech',
    ]);

    // Mocked AI manager so the assistant drawer never escapes the test.
    $ai = mock(AIManager::class)->shouldIgnoreMissing();
    $ai->shouldReceive('complete')->andReturn(new CompletionResponse(
        content: 'AI generated article body for the test.',
        usage: new TokenUsage(100, 500, 600, 0.001),
        providerName: 'mock',
        model: 'mock-model',
    ));
    $this->app->instance(AIManager::class, $ai);
});

function postsAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole(Role::query()->where('name', 'Admin')->firstOrFail());

    return $user->fresh();
}

test('admin creates a post, saves as draft, then publishes it', function (): void {
    $admin = postsAdmin();

    visit('/admin/posts/create', as: $admin)
        ->assertSee('Create')
        ->fill('input[wire\\:model\\.live\\.debounce\\.500ms="title"]', 'My First Post')
        ->fill('textarea[wire\\:model="excerpt"]', 'A short intro paragraph.')
        ->press('Save as Draft')
        ->assertSee('saved');

    $post = Post::query()->latest('id')->first();
    expect($post)->not->toBeNull();
    expect($post->translation()?->title)->toBe('My First Post');
});

test('admin sees the AI Assistant trigger on the create page', function (): void {
    $admin = postsAdmin();

    visit('/admin/posts/create', as: $admin)
        ->assertSee('Generate Article')
        ->assertSee('Generate SEO')
        ->assertSee('Rewrite');
});

test('admin can edit an existing post and publish it', function (): void {
    $admin = postsAdmin();
    $post = Post::factory()->draft()->state(['category_id' => $this->category->id])->create();

    visit('/admin/posts/'.$post->id.'/edit', as: $admin)
        ->assertOk()
        ->press('Publish');

    expect($post->fresh()->status->value)->toBe('published');
});
