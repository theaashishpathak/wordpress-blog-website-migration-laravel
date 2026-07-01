<?php

declare(strict_types=1);

use App\Services\Seo\DataTransferObjects\SeoScoreInput;
use App\Services\Seo\SeoScoreService;

function makeInput(array $overrides = []): SeoScoreInput
{
    $defaults = [
        'title' => '',
        'slug' => '',
        'excerpt' => '',
        'content' => '',
        'metaTitle' => '',
        'metaDescription' => '',
        'focusKeyword' => '',
    ];

    return new SeoScoreInput(
        title: $overrides['title'] ?? $defaults['title'],
        slug: $overrides['slug'] ?? $defaults['slug'],
        excerpt: $overrides['excerpt'] ?? $defaults['excerpt'],
        content: $overrides['content'] ?? $defaults['content'],
        metaTitle: $overrides['metaTitle'] ?? $defaults['metaTitle'],
        metaDescription: $overrides['metaDescription'] ?? $defaults['metaDescription'],
        focusKeyword: $overrides['focusKeyword'] ?? $defaults['focusKeyword'],
    );
}

beforeEach(function (): void {
    $this->service = new SeoScoreService;
});

test('empty input returns zero score with all checks failing', function (): void {
    $result = $this->service->score(makeInput());

    expect($result->overall)->toBe(0);
    expect($result->level())->toBe('bad');
    expect($result->checks)->toHaveCount(9);

    foreach ($result->checks as $check) {
        expect($check->level)->toBe('bad');
    }
});

test('ideal-length meta title scores good', function (): void {
    $title = str_repeat('a', 55);
    $result = $this->service->score(makeInput(['metaTitle' => $title]));

    $check = collect($result->checks)->firstWhere('key', 'meta_title_length');
    expect($check->level)->toBe('good');
});

test('over-long meta title scores bad', function (): void {
    $longTitle = str_repeat('a', 120);
    $result = $this->service->score(makeInput(['metaTitle' => $longTitle]));

    $check = collect($result->checks)->firstWhere('key', 'meta_title_length');
    expect($check->level)->toBe('bad');
    expect($check->message)->toContain('too long');
});

test('ideal-length meta description scores good', function (): void {
    $description = str_repeat('word ', 30);             // ~150 chars
    $result = $this->service->score(makeInput(['metaDescription' => $description]));

    $check = collect($result->checks)->firstWhere('key', 'meta_description_length');
    expect($check->level)->toBe('good');
});

test('focus keyword absent from title fails the keyword-in-title check', function (): void {
    $result = $this->service->score(makeInput([
        'title' => 'Unrelated Headline',
        'focusKeyword' => 'ai marketing',
    ]));

    $check = collect($result->checks)->firstWhere('key', 'focus_keyword_in_title');
    expect($check->level)->toBe('bad');
});

test('focus keyword present in title and slug scores both good', function (): void {
    $result = $this->service->score(makeInput([
        'title' => 'AI Marketing in 2026',
        'slug' => 'ai-marketing-in-2026',
        'focusKeyword' => 'ai marketing',
    ]));

    $titleCheck = collect($result->checks)->firstWhere('key', 'focus_keyword_in_title');
    $slugCheck = collect($result->checks)->firstWhere('key', 'focus_keyword_in_slug');

    expect($titleCheck->level)->toBe('good');
    expect($slugCheck->level)->toBe('good');
});

test('keyword stuffing flags density as bad', function (): void {
    $content = str_repeat('ai marketing ', 50);          // 100 words, all "ai marketing"
    $result = $this->service->score(makeInput([
        'content' => $content,
        'focusKeyword' => 'ai marketing',
    ]));

    $check = collect($result->checks)->firstWhere('key', 'keyword_density');
    expect($check->level)->toBe('bad');
    expect($check->message)->toContain('stuffing');
});

test('long content with natural keyword density scores well overall', function (): void {
    // 400 plain words with the focus keyword appearing 6 times (1.5% density).
    $filler = str_repeat('lorem ipsum dolor sit amet consectetur adipiscing elit. ', 60);
    $content = "AI Marketing is a growing field. ".$filler.
        " The future of ai marketing is bright. Many ai marketing tools exist today. ".
        "Companies invest in ai marketing strategy. Ai marketing roi is improving. Choosing the right ai marketing partner matters. ";

    $result = $this->service->score(makeInput([
        'title' => 'AI Marketing in 2026: The Complete Field Guide',
        'slug' => 'ai-marketing-2026-complete-guide',
        'content' => $content,
        'metaTitle' => 'AI Marketing in 2026: The Complete Field Guide for Teams',
        'metaDescription' => 'Discover how AI marketing tools, automation, and analytics reshape modern campaigns. Practical strategies and roi-focused guidance.',
        'focusKeyword' => 'ai marketing',
    ]));

    expect($result->overall)->toBeGreaterThanOrEqual(70);
});

test('html content is stripped before scoring', function (): void {
    $html = '<p>AI marketing is great.</p><p>'.str_repeat('AI marketing helps brands grow. ', 40).'</p>';

    $result = $this->service->score(makeInput([
        'content' => $html,
        'focusKeyword' => 'ai marketing',
    ]));

    $lengthCheck = collect($result->checks)->firstWhere('key', 'content_length');

    // 40 repetitions × 5 words + the intro sentence ≈ 200+ words after strip.
    expect($lengthCheck->level)->not->toBe('bad');
});

test('overall level reflects banded ranges', function (): void {
    expect($this->service->score(makeInput())->level())->toBe('bad');

    // Hand-tuned input that should land in the warning band.
    $warningInput = makeInput([
        'title' => 'AI Marketing',
        'slug' => 'ai-marketing',
        'metaTitle' => str_repeat('a', 50),
        'metaDescription' => str_repeat('a', 120),
        'focusKeyword' => 'ai marketing',
        'content' => 'Some content here.',
    ]);
    $level = $this->service->score($warningInput)->level();
    expect($level)->toBeIn(['warning', 'good']);
});
