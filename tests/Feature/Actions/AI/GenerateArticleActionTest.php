<?php

declare(strict_types=1);

use App\Actions\AI\GenerateArticleAction;
use App\Models\AIPromptTemplate;
use App\Models\Language;
use App\Services\AI\AIManager;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\TokenUsage;
use App\Services\AI\Exceptions\AIProviderException;
use App\Services\AI\PromptBuilder;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PromptBuilder::class)->flushCache();

    AIPromptTemplate::factory()->articleWriter()->create();
});

test('generates article and forwards correct CompletionRequest to AIManager', function (): void {
    $mock = mock(AIManager::class);

    $mock->shouldReceive('complete')
        ->once()
        ->andReturnUsing(function (CompletionRequest $request, ?string $preferred = null): CompletionResponse {
            expect($request->featureKey)->toBe('article_writer');
            expect($request->promptTemplateKey)->toBe('article_writer.long_form');
            expect($request->metadata['template_version'] ?? null)->toBe(1);
            expect($request->systemPrompt)->toContain('long-form article writer');
            expect($request->userPrompt)->toContain('AI tools');         // topic
            expect($request->userPrompt)->toContain('professional');     // tone
            expect($request->userPrompt)->toContain('800');              // word_count
            expect($preferred)->toBe('openai');

            return new CompletionResponse(
                content: "  # Generated article body\n\nWith content.  ",
                usage: new TokenUsage(100, 1000, 1100, 0.001),
                providerName: 'openai',
                model: 'gpt-4o-mini',
            );
        });

    app()->instance(AIManager::class, $mock);

    $result = app(GenerateArticleAction::class)->handle(
        topic: 'AI tools',
        locale: 'en',
        tone: 'professional',
        wordCount: 800,
        audience: 'developers',
        focusKeyword: 'ai-tools',
        preferredProvider: 'openai',
    );

    // trim() strips leading/trailing whitespace (edges) only — internal
    // markdown structure (blank lines, intentional hard-break spaces)
    // must be preserved verbatim.
    expect($result)->toBe("# Generated article body\n\nWith content.");
});

test('propagates AIProviderException from the manager', function (): void {
    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')->once()->andThrow(new AIProviderException('boom'));
    app()->instance(AIManager::class, $mock);

    app(GenerateArticleAction::class)->handle('topic');
})->throws(AIProviderException::class, 'boom');

test('uses default model from settings when none supplied', function (): void {
    app(\App\Services\SettingService::class)->set('ai.default_model', 'gpt-4o', 'ai-providers', \App\Models\Setting::TYPE_TEXT);

    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturnUsing(function (CompletionRequest $request): CompletionResponse {
            expect($request->model)->toBe('gpt-4o');

            return new CompletionResponse(
                content: 'ok',
                usage: new TokenUsage(1, 1, 2, 0.0),
                providerName: 'openai',
                model: 'gpt-4o',
            );
        });
    app()->instance(AIManager::class, $mock);

    app(GenerateArticleAction::class)->handle('topic');
});
