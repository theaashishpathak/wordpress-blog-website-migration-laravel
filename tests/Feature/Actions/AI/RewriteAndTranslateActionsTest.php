<?php

declare(strict_types=1);

use App\Actions\AI\RewriteParagraphAction;
use App\Actions\AI\TranslateContentAction;
use App\Models\AIPromptTemplate;
use App\Models\Language;
use App\Services\AI\AIManager;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\TokenUsage;
use App\Services\AI\PromptBuilder;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PromptBuilder::class)->flushCache();

    AIPromptTemplate::factory()->state([
        'key' => 'rewrite.paragraph',
        'locale' => 'en',
        'system_prompt' => 'You rewrite paragraphs.',
        'user_prompt_template' => 'Rewrite in {{tone}} tone: {{paragraph}}',
        'variables' => ['tone', 'paragraph'],
    ])->create();

    AIPromptTemplate::factory()->state([
        'key' => 'translate.article',
        'locale' => 'en',
        'system_prompt' => 'You translate articles.',
        'user_prompt_template' => 'Translate to {{target_language}}: {{article}}',
        'variables' => ['target_language', 'article'],
    ])->create();
});

test('rewrite-paragraph returns trimmed text from manager', function (): void {
    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturnUsing(function (CompletionRequest $request): CompletionResponse {
            expect($request->featureKey)->toBe('rewrite');
            expect($request->promptTemplateKey)->toBe('rewrite.paragraph');
            expect($request->temperature)->toBe(0.6);
            expect($request->userPrompt)->toContain('casual');
            expect($request->userPrompt)->toContain('Original sentence here.');

            return new CompletionResponse(
                content: "  Casual rewritten version.  \n",
                usage: new TokenUsage(1, 1, 2, 0.0),
                providerName: 'openai',
                model: 'gpt-4o-mini',
            );
        });
    app()->instance(AIManager::class, $mock);

    $result = app(RewriteParagraphAction::class)->handle(
        paragraph: 'Original sentence here.',
        tone: 'casual',
    );

    expect($result)->toBe('Casual rewritten version.');
});

test('translate uses translate.article template and forwards target_language metadata', function (): void {
    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturnUsing(function (CompletionRequest $request): CompletionResponse {
            expect($request->featureKey)->toBe('translate');
            expect($request->promptTemplateKey)->toBe('translate.article');
            expect($request->temperature)->toBe(0.3);
            expect($request->userPrompt)->toContain('Bangla');
            expect($request->metadata['source_locale'] ?? null)->toBe('en');
            expect($request->metadata['target_language'] ?? null)->toBe('Bangla');

            return new CompletionResponse(
                content: 'অনূদিত নিবন্ধ',
                usage: new TokenUsage(1, 1, 2, 0.0),
                providerName: 'openai',
                model: 'gpt-4o-mini',
            );
        });
    app()->instance(AIManager::class, $mock);

    $result = app(TranslateContentAction::class)->handle(
        article: 'English article body.',
        targetLanguage: 'Bangla',
    );

    expect($result)->toBe('অনূদিত নিবন্ধ');
});

test('translate adapts max_tokens to article length', function (): void {
    $longArticle = str_repeat('Lorem ipsum ', 500);   // ~5500 chars

    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturnUsing(function (CompletionRequest $request) use ($longArticle): CompletionResponse {
            // max_tokens should scale with article length (length * 2 floor).
            expect($request->maxTokens)->toBeGreaterThanOrEqual(2000);
            expect($request->maxTokens)->toBeGreaterThanOrEqual(mb_strlen($longArticle));

            return new CompletionResponse(
                content: 'translated',
                usage: new TokenUsage(1, 1, 2, 0.0),
                providerName: 'openai',
                model: 'gpt-4o-mini',
            );
        });
    app()->instance(AIManager::class, $mock);

    app(TranslateContentAction::class)->handle($longArticle, 'Arabic');
});
