<?php

declare(strict_types=1);

use App\Actions\AI\GenerateSEOMetaAction;
use App\Models\AIPromptTemplate;
use App\Models\Language;
use App\Services\AI\AIManager;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\SEOMetaResult;
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

    AIPromptTemplate::factory()->seoMeta()->create();
});

test('returns SEOMetaResult with parsed fields and truncated lengths', function (): void {
    $longTitle = str_repeat('Very Long Title ', 10);   // > 60 chars

    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturn(new CompletionResponse(
            content: json_encode([
                'meta_title' => $longTitle,
                'meta_description' => 'A solid description.',
                'tags' => ['ai', 'tools', 'newspilot'],
                'slug' => 'AI Tools 2026 Edition',
            ]),
            usage: new TokenUsage(50, 100, 150, 0.001),
            providerName: 'openai',
            model: 'gpt-4o-mini',
        ));
    app()->instance(AIManager::class, $mock);

    $result = app(GenerateSEOMetaAction::class)->handle(
        title: 'AI Tools 2026',
        excerpt: 'A short excerpt.',
        focusKeyword: 'ai tools',
    );

    expect($result)->toBeInstanceOf(SEOMetaResult::class);
    expect(mb_strlen($result->metaTitle))->toBeLessThanOrEqual(60);
    expect($result->metaDescription)->toBe('A solid description.');
    expect($result->tags)->toBe(['ai', 'tools', 'newspilot']);
    expect($result->slug)->toBe('AI Tools 2026 Edition');
});

test('accepts JSON wrapped in markdown code fence', function (): void {
    $fenced = "Sure! Here you go:\n```json\n".json_encode([
        'meta_title' => 'Title',
        'meta_description' => 'Desc',
        'tags' => ['a'],
        'slug' => 'title',
    ])."\n```";

    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturn(new CompletionResponse(
            content: $fenced,
            usage: new TokenUsage(1, 1, 2, 0.0),
            providerName: 'openai',
            model: 'gpt-4o-mini',
        ));
    app()->instance(AIManager::class, $mock);

    $result = app(GenerateSEOMetaAction::class)->handle('t', 'e', 'k');

    expect($result->metaTitle)->toBe('Title');
    expect($result->tags)->toBe(['a']);
});

test('throws AIProviderException when response is not JSON', function (): void {
    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturn(new CompletionResponse(
            content: 'I am sorry, I cannot help with that.',
            usage: new TokenUsage(1, 1, 2, 0.0),
            providerName: 'openai',
            model: 'gpt-4o-mini',
        ));
    app()->instance(AIManager::class, $mock);

    app(GenerateSEOMetaAction::class)->handle('t', 'e', 'k');
})->throws(AIProviderException::class);

test('forwards feature_key = seo_meta and uses low temperature', function (): void {
    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturnUsing(function (CompletionRequest $request): CompletionResponse {
            expect($request->featureKey)->toBe('seo_meta');
            expect($request->temperature)->toBe(0.3);
            expect($request->promptTemplateKey)->toBe('seo_meta.default');

            return new CompletionResponse(
                content: '{"meta_title": "T", "meta_description": "D", "tags": [], "slug": "t"}',
                usage: new TokenUsage(1, 1, 2, 0.0),
                providerName: 'openai',
                model: 'gpt-4o-mini',
            );
        });
    app()->instance(AIManager::class, $mock);

    app(GenerateSEOMetaAction::class)->handle('t', 'e', 'k');
});
