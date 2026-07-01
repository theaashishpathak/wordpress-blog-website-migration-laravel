<?php

declare(strict_types=1);

use App\Livewire\Admin\Posts\AiAssistantDrawer;
use App\Livewire\Admin\Posts\Create as CreatePostLivewire;
use App\Livewire\Admin\Posts\Edit as EditPostLivewire;
use App\Models\AIPromptTemplate;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\TokenUsage;
use App\Services\AI\Exceptions\AIProviderException;
use App\Services\AI\PromptBuilder;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PromptBuilder::class)->flushCache();
    app(PermissionSeeder::class)->run();

    // Seed the prompt templates the Actions rely on.
    AIPromptTemplate::factory()->articleWriter()->create();
    AIPromptTemplate::factory()->seoMeta()->create();
    AIPromptTemplate::factory()->state([
        'key' => 'rewrite.paragraph',
        'locale' => 'en',
        'system_prompt' => 'You rewrite paragraphs.',
        'user_prompt_template' => 'Rewrite in {{tone}}: {{paragraph}}',
        'variables' => ['tone', 'paragraph'],
    ])->create();
});

function aiDrawerUser(string $roleName): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

function mockAiManagerWithContent(string $content): void
{
    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->andReturn(new CompletionResponse(
            content: $content,
            usage: new TokenUsage(50, 200, 250, 0.001),
            providerName: 'openai',
            model: 'gpt-4o-mini',
        ));
    app()->instance(AIManager::class, $mock);
}

test('openFor sets mode and seeds inputs from payload', function (): void {
    $user = aiDrawerUser('Admin');

    Livewire::actingAs($user)
        ->test(AiAssistantDrawer::class)
        ->dispatch('ai-assistant.open', payload: [
            'mode' => 'article',
            'locale' => 'en',
            'topic' => 'AI in 2026',
            'focus_keyword' => 'newspilot',
        ])
        ->assertSet('open', true)
        ->assertSet('mode', 'article')
        ->assertSet('topic', 'AI in 2026')
        ->assertSet('focusKeyword', 'newspilot');
});

test('generateArticle calls AIManager and stores output', function (): void {
    $user = aiDrawerUser('Admin');
    mockAiManagerWithContent('# Generated Article\n\nBody content here.');

    Livewire::actingAs($user)
        ->test(AiAssistantDrawer::class)
        ->set('mode', 'article')
        ->set('topic', 'AI tools')
        ->set('tone', 'professional')
        ->call('generateArticle')
        ->assertSet('output', '# Generated Article\n\nBody content here.')
        ->assertSet('isGenerating', false);
});

test('generateArticle rejects empty topic with a toast', function (): void {
    $user = aiDrawerUser('Admin');

    Livewire::actingAs($user)
        ->test(AiAssistantDrawer::class)
        ->set('topic', '   ')
        ->call('generateArticle')
        ->assertDispatched('toast.danger')
        ->assertSet('output', '');
});

test('generateArticle is denied for users without ai.use_writer', function (): void {
    $user = aiDrawerUser('Subscriber');

    Livewire::actingAs($user)
        ->test(AiAssistantDrawer::class)
        ->set('topic', 'AI tools')
        ->call('generateArticle')
        ->assertForbidden();
});

test('generateSeoMeta calls AIManager and parses output into seoOutput', function (): void {
    $user = aiDrawerUser('Admin');
    mockAiManagerWithContent(json_encode([
        'meta_title' => 'AI Tools 2026',
        'meta_description' => 'Discover the best AI tools for newsroom workflow.',
        'tags' => ['ai', 'tools', 'newsroom'],
        'slug' => 'ai-tools-2026',
    ]));

    $component = Livewire::actingAs($user)
        ->test(AiAssistantDrawer::class)
        ->set('mode', 'seo')
        ->set('seoTitle', 'AI Tools 2026')
        ->set('seoExcerpt', 'Discover the best AI tools for newsroom workflow.')
        ->set('seoFocusKeyword', 'ai tools')
        ->call('generateSeoMeta');

    expect($component->get('seoOutput'))->toBeArray();
    expect($component->get('seoOutput')['meta_title'] ?? null)->toBe('AI Tools 2026');
    expect($component->get('seoOutput')['tags'] ?? null)->toBe(['ai', 'tools', 'newsroom']);
});

test('AI errors surface as a danger toast and do not crash the drawer', function (): void {
    $user = aiDrawerUser('Admin');

    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')->andThrow(new AIProviderException('Provider down.'));
    app()->instance(AIManager::class, $mock);

    Livewire::actingAs($user)
        ->test(AiAssistantDrawer::class)
        ->set('mode', 'article')
        ->set('topic', 'Test')
        ->call('generateArticle')
        ->assertDispatched('toast.danger')
        ->assertSet('isGenerating', false);
});

test('rewriteParagraph requires non-empty input', function (): void {
    $user = aiDrawerUser('Admin');

    Livewire::actingAs($user)
        ->test(AiAssistantDrawer::class)
        ->set('mode', 'rewrite')
        ->set('paragraph', '')
        ->call('rewriteParagraph')
        ->assertDispatched('toast.danger');
});

test('rewriteParagraph dispatches rewrite event when applied to editor', function (): void {
    $user = aiDrawerUser('Admin');
    mockAiManagerWithContent('Rewritten paragraph here.');

    Livewire::actingAs($user)
        ->test(AiAssistantDrawer::class)
        ->set('mode', 'rewrite')
        ->set('paragraph', 'Old paragraph.')
        ->call('rewriteParagraph')
        ->assertSet('output', 'Rewritten paragraph here.')
        ->call('applyToEditor', 'replace')
        ->assertDispatched('ai.rewrite-completed')
        ->assertSet('open', false);
});

test('CreatePost picks up ai.article-generated event and updates content', function (): void {
    $user = aiDrawerUser('Admin');
    $language = Language::query()->default()->firstOrFail();

    Livewire::actingAs($user)
        ->test(CreatePostLivewire::class)
        ->set('defaultLanguageId', $language->id)
        ->call('applyGeneratedArticle', '# AI Article\n\nBody.')
        ->assertSet('content', '# AI Article\n\nBody.')
        ->assertDispatched('toast.success');
});

test('CreatePost append strategy concatenates new content onto existing', function (): void {
    $user = aiDrawerUser('Admin');
    $language = Language::query()->default()->firstOrFail();

    Livewire::actingAs($user)
        ->test(CreatePostLivewire::class)
        ->set('defaultLanguageId', $language->id)
        ->set('content', 'Existing intro.')
        ->call('applyGeneratedArticle', 'Appended section.', 'append')
        ->assertSet('content', "Existing intro.\n\nAppended section.");
});

test('EditPost picks up ai.seo-generated event and updates slug', function (): void {
    $user = aiDrawerUser('Admin');
    $post = Post::factory()->draft()->create();

    Livewire::actingAs($user)
        ->test(EditPostLivewire::class, ['post' => $post])
        ->call('applyGeneratedSeoMeta', [
            'meta_title' => 'New Title',
            'meta_description' => 'Description.',
            'slug' => 'new-slug-from-ai',
            'tags' => ['tag1'],
        ])
        ->assertSet('slug', 'new-slug-from-ai')
        ->assertDispatched('toast.success');
});

test('openAIAssistant on CreatePost dispatches the drawer-open event with locale', function (): void {
    $user = aiDrawerUser('Admin');
    $language = Language::query()->default()->firstOrFail();

    Livewire::actingAs($user)
        ->test(CreatePostLivewire::class)
        ->set('defaultLanguageId', $language->id)
        ->set('title', 'AI Topic Title')
        ->call('openAIAssistant', 'article')
        ->assertDispatched(
            'ai-assistant.open',
            // First positional arg is treated as a predicate when callable.
            // Signature: ($event, array $params) where $params is keyed by
            // the named arguments passed to $this->dispatch(...).
            function (string $event, array $params): bool {
                $payload = $params['payload'] ?? [];

                return is_array($payload)
                    && ($payload['mode'] ?? null) === 'article'
                    && ($payload['locale'] ?? null) === 'en'
                    && ($payload['topic'] ?? null) === 'AI Topic Title';
            },
        );
});
