<?php

declare(strict_types=1);

use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\NullProvider;
use Tests\TestCase;

uses(TestCase::class);

test('null provider reports its canonical name and is always healthy', function (): void {
    $provider = new NullProvider;

    expect($provider->name())->toBe(NullProvider::NAME);
    expect($provider->isHealthy())->toBeTrue();
    expect($provider->availableModels())->toBeArray()->toContain('null-stub');
    expect($provider->estimateCost('null-stub', 1000, 1000))->toBe(0.0);
});

test('complete returns deterministic placeholder content', function (): void {
    $provider = new NullProvider;

    $response = $provider->complete(new CompletionRequest(
        model: 'null-stub',
        systemPrompt: 'You are a test stub.',
        userPrompt: 'Write something about NewsPilot.',
        featureKey: 'article_writer',
    ));

    expect($response->providerName)->toBe(NullProvider::NAME);
    expect($response->content)->toStartWith(NullProvider::PLACEHOLDER_PREFIX);
    expect($response->content)->toContain('NewsPilot');
    expect($response->usage->totalTokens)->toBeGreaterThan(0);
    expect($response->usage->estimatedCostUsd)->toBe(0.0);
    expect($response->finishReason)->toBe('stop');
});

test('stream yields multiple chunks ending with a final usage chunk', function (): void {
    $provider = new NullProvider;

    $chunks = iterator_to_array($provider->stream(new CompletionRequest(
        model: 'null-stub',
        systemPrompt: 'You are a test stub.',
        userPrompt: 'A test prompt with multiple words to chunk on.',
    )));

    expect(count($chunks))->toBeGreaterThan(1);

    /** @var \App\Services\AI\DataTransferObjects\StreamChunk $finalChunk */
    $finalChunk = end($chunks);
    expect($finalChunk->isFinal)->toBeTrue();
    expect($finalChunk->usage)->not->toBeNull();
    expect($finalChunk->usage->totalTokens)->toBeGreaterThan(0);

    // Non-final chunks have content but no usage payload.
    expect($chunks[0]->isFinal)->toBeFalse();
    expect($chunks[0]->usage)->toBeNull();
});
