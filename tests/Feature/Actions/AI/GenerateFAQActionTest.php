<?php

declare(strict_types=1);

use App\Actions\AI\GenerateFAQAction;
use App\Models\AIPromptTemplate;
use App\Models\Language;
use App\Services\AI\AIManager;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\FAQResult;
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
        'key' => 'faq_generator.default',
        'locale' => 'en',
        'system_prompt' => 'You generate FAQ sections.',
        'user_prompt_template' => 'Article: {{article}}, count: {{faq_count}}.',
        'variables' => ['article', 'faq_count'],
    ])->create();
});

test('returns FAQResult parsed from model JSON output', function (): void {
    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturn(new CompletionResponse(
            content: json_encode([
                'faqs' => [
                    ['question' => 'Q1?', 'answer' => 'A1.'],
                    ['question' => 'Q2?', 'answer' => 'A2.'],
                    ['question' => '', 'answer' => 'orphan'],   // filtered out
                ],
            ]),
            usage: new TokenUsage(1, 1, 2, 0.0),
            providerName: 'openai',
            model: 'gpt-4o-mini',
        ));
    app()->instance(AIManager::class, $mock);

    $result = app(GenerateFAQAction::class)->handle('Some article body.');

    expect($result)->toBeInstanceOf(FAQResult::class);
    expect($result->count())->toBe(2);
    expect($result->faqs[0]->question)->toBe('Q1?');
    expect($result->faqs[1]->answer)->toBe('A2.');
});

test('clamps faqCount into the safe 1-15 range', function (): void {
    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturnUsing(function ($request): CompletionResponse {
            // Request should have faq_count clamped to 15 (caller asked for 100).
            expect($request->userPrompt)->toContain('count: 15');

            return new CompletionResponse(
                content: '{"faqs": []}',
                usage: new TokenUsage(1, 1, 2, 0.0),
                providerName: 'openai',
                model: 'gpt-4o-mini',
            );
        });
    app()->instance(AIManager::class, $mock);

    app(GenerateFAQAction::class)->handle('article', faqCount: 100);
});

test('schema.org export contains FAQPage Question entities', function (): void {
    $mock = mock(AIManager::class);
    $mock->shouldReceive('complete')
        ->once()
        ->andReturn(new CompletionResponse(
            content: '{"faqs": [{"question": "What is X?", "answer": "X is..."}]}',
            usage: new TokenUsage(1, 1, 2, 0.0),
            providerName: 'openai',
            model: 'gpt-4o-mini',
        ));
    app()->instance(AIManager::class, $mock);

    $result = app(GenerateFAQAction::class)->handle('article');

    $entities = $result->toSchemaOrgEntities();
    expect($entities[0]['@type'])->toBe('Question');
    expect($entities[0]['name'])->toBe('What is X?');
    expect($entities[0]['acceptedAnswer']['@type'])->toBe('Answer');
});
