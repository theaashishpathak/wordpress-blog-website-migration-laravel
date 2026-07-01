<?php

declare(strict_types=1);

use App\Models\AIPromptTemplate;
use Database\Seeders\AIPromptTemplateSeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seeder ships all six core templates in both English and Bangla', function (): void {
    app(LanguageSeeder::class)->run();
    app(AIPromptTemplateSeeder::class)->run();

    $expectedKeys = [
        'article_writer.long_form',
        'article_writer.news',
        'seo_meta.default',
        'faq_generator.default',
        'rewrite.paragraph',
        'translate.article',
    ];

    foreach ($expectedKeys as $key) {
        expect(AIPromptTemplate::query()->where('key', $key)->where('locale', 'en')->exists())
            ->toBeTrue("Missing English template [{$key}].");

        expect(AIPromptTemplate::query()->where('key', $key)->where('locale', 'bn')->exists())
            ->toBeTrue("Missing Bangla template [{$key}].");
    }
});

test('every seeded template is active and version=1', function (): void {
    app(LanguageSeeder::class)->run();
    app(AIPromptTemplateSeeder::class)->run();

    $rows = AIPromptTemplate::query()->get();

    expect($rows->every(fn ($t): bool => (bool) $t->is_active))->toBeTrue();
    expect($rows->every(fn ($t): bool => (int) $t->version === 1))->toBeTrue();
});

test('every seeded template declares its variables', function (): void {
    app(LanguageSeeder::class)->run();
    app(AIPromptTemplateSeeder::class)->run();

    $rows = AIPromptTemplate::query()->get();

    expect($rows->every(fn ($t): bool => is_array($t->variables) && $t->variables !== []))
        ->toBeTrue();
});

test('reseeding is idempotent and does not duplicate templates', function (): void {
    app(LanguageSeeder::class)->run();

    app(AIPromptTemplateSeeder::class)->run();
    $countAfterFirst = AIPromptTemplate::query()->count();

    app(AIPromptTemplateSeeder::class)->run();
    $countAfterSecond = AIPromptTemplate::query()->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
});

test('article_writer.long_form English template declares required variables', function (): void {
    app(LanguageSeeder::class)->run();
    app(AIPromptTemplateSeeder::class)->run();

    $template = AIPromptTemplate::query()
        ->where('key', 'article_writer.long_form')
        ->where('locale', 'en')
        ->firstOrFail();

    expect($template->variables)->toContain('topic', 'tone', 'word_count', 'audience', 'focus_keyword');
});

test('seo_meta.default English template requires title, excerpt, focus_keyword', function (): void {
    app(LanguageSeeder::class)->run();
    app(AIPromptTemplateSeeder::class)->run();

    $template = AIPromptTemplate::query()
        ->where('key', 'seo_meta.default')
        ->where('locale', 'en')
        ->firstOrFail();

    expect($template->variables)->toContain('title', 'excerpt', 'focus_keyword');
});

test('bumpVersion creates a new active row and deactivates the previous one', function (): void {
    app(LanguageSeeder::class)->run();
    app(AIPromptTemplateSeeder::class)->run();

    $v1 = AIPromptTemplate::active('seo_meta.default', 'en');
    expect($v1->version)->toBe(1);

    $v2 = $v1->bumpVersion(['system_prompt' => 'v2 system prompt']);

    expect($v2->version)->toBe(2);
    expect($v2->is_active)->toBeTrue();
    expect($v2->system_prompt)->toBe('v2 system prompt');

    expect($v1->fresh()->is_active)->toBeFalse();
    expect(AIPromptTemplate::active('seo_meta.default', 'en')->version)->toBe(2);
});
