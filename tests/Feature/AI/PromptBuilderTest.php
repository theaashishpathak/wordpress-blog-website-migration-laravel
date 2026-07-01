<?php

declare(strict_types=1);

use App\Models\AIPromptTemplate;
use App\Models\Language;
use App\Services\AI\PromptBuilder;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    Language::factory()->bangla()->create();
    app(LocaleResolver::class)->flush();
    app(PromptBuilder::class)->flushCache();
});

test('interpolates variables in both system and user prompts', function (): void {
    AIPromptTemplate::factory()->state([
        'key' => 'test.basic',
        'locale' => 'en',
        'system_prompt' => 'You write about {{topic}}.',
        'user_prompt_template' => 'Topic: {{topic}}, audience: {{audience}}.',
        'variables' => ['topic', 'audience'],
    ])->create();

    $rendered = app(PromptBuilder::class)->build('test.basic', 'en', [
        'topic' => 'AI',
        'audience' => 'developers',
    ]);

    expect($rendered->systemPrompt)->toBe('You write about AI.');
    expect($rendered->userPrompt)->toBe('Topic: AI, audience: developers.');
    expect($rendered->templateKey)->toBe('test.basic');
    expect($rendered->templateVersion)->toBe(1);
    expect($rendered->locale)->toBe('en');
});

test('throws ValidationException when a required variable is missing', function (): void {
    AIPromptTemplate::factory()->state([
        'key' => 'test.requires',
        'locale' => 'en',
        'system_prompt' => 'sys',
        'user_prompt_template' => '{{required_thing}}',
        'variables' => ['required_thing'],
    ])->create();

    app(PromptBuilder::class)->build('test.requires', 'en', []);
})->throws(ValidationException::class);

test('falls back to default locale when requested locale has no active template', function (): void {
    // Only English is seeded for this key — Bangla request should fall back to English.
    AIPromptTemplate::factory()->state([
        'key' => 'test.fallback',
        'locale' => 'en',
        'system_prompt' => 'EN system',
        'user_prompt_template' => 'EN user {{x}}',
        'variables' => ['x'],
    ])->create();

    $rendered = app(PromptBuilder::class)->build('test.fallback', 'bn', ['x' => '1']);

    expect($rendered->locale)->toBe('en');
    expect($rendered->systemPrompt)->toBe('EN system');
});

test('throws RuntimeException when no template exists in any locale', function (): void {
    app(PromptBuilder::class)->build('nonexistent.key', 'en', []);
})->throws(RuntimeException::class);

test('prefers the active version when multiple versions exist', function (): void {
    AIPromptTemplate::factory()->state([
        'key' => 'test.versions',
        'locale' => 'en',
        'version' => 1,
        'system_prompt' => 'v1 system',
        'user_prompt_template' => 'v1 {{x}}',
        'variables' => ['x'],
        'is_active' => false,
    ])->create();

    AIPromptTemplate::factory()->state([
        'key' => 'test.versions',
        'locale' => 'en',
        'version' => 2,
        'system_prompt' => 'v2 system',
        'user_prompt_template' => 'v2 {{x}}',
        'variables' => ['x'],
        'is_active' => true,
    ])->create();

    $rendered = app(PromptBuilder::class)->build('test.versions', 'en', ['x' => 'ok']);

    expect($rendered->templateVersion)->toBe(2);
    expect($rendered->systemPrompt)->toBe('v2 system');
});

test('renders array variables as JSON', function (): void {
    AIPromptTemplate::factory()->state([
        'key' => 'test.array',
        'locale' => 'en',
        'system_prompt' => 'sys',
        'user_prompt_template' => 'List: {{items}}',
        'variables' => ['items'],
    ])->create();

    $rendered = app(PromptBuilder::class)->build('test.array', 'en', [
        'items' => ['apple', 'banana'],
    ]);

    expect($rendered->userPrompt)->toContain('apple');
    expect($rendered->userPrompt)->toContain('banana');
});

test('leaves unknown {{variable}} placeholders untouched', function (): void {
    AIPromptTemplate::factory()->state([
        'key' => 'test.unknown',
        'locale' => 'en',
        'system_prompt' => 'sys',
        'user_prompt_template' => 'Known: {{x}}, unknown: {{nope}}',
        'variables' => ['x'],
    ])->create();

    $rendered = app(PromptBuilder::class)->build('test.unknown', 'en', ['x' => '1']);

    expect($rendered->userPrompt)->toBe('Known: 1, unknown: {{nope}}');
});

test('cache flushes correctly across requests', function (): void {
    AIPromptTemplate::factory()->state([
        'key' => 'test.cache',
        'locale' => 'en',
        'system_prompt' => 'first',
        'user_prompt_template' => '{{x}}',
        'variables' => ['x'],
    ])->create();

    $builder = app(PromptBuilder::class);

    $first = $builder->resolveTemplate('test.cache', 'en');
    expect($first?->system_prompt)->toBe('first');

    // Use a direct DB update so the cached Model instance isn't mutated
    // (Eloquent's $model->update() would mutate the same object the cache
    // holds, masking the cache check).
    DB::table('ai_prompt_templates')
        ->where('id', $first->id)
        ->update(['system_prompt' => 'updated']);

    // Without flush — cached result still says 'first'.
    expect($builder->resolveTemplate('test.cache', 'en')?->system_prompt)->toBe('first');

    $builder->flushCache();

    expect($builder->resolveTemplate('test.cache', 'en')?->system_prompt)->toBe('updated');
});
