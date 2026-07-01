<?php

declare(strict_types=1);

use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\Exceptions\AIProviderException;
use App\Services\AI\Exceptions\RateLimitException;
use App\Services\AI\GeminiProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Cache::flush();
});

function makeGeminiRequest(string $model = 'gemini-1.5-flash'): CompletionRequest
{
    return new CompletionRequest(
        model: $model,
        systemPrompt: 'You are helpful.',
        userPrompt: 'Write a tagline.',
        temperature: 0.5,
        maxTokens: 200,
    );
}

test('complete posts to generateContent endpoint with API key as query param', function (): void {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [['text' => 'Tagline output.']],
                ],
                'finishReason' => 'STOP',
            ]],
            'usageMetadata' => [
                'promptTokenCount' => 100,
                'candidatesTokenCount' => 250,
                'totalTokenCount' => 350,
            ],
        ]),
    ]);

    $provider = new GeminiProvider('gem-test-fake');
    $response = $provider->complete(makeGeminiRequest());

    expect($response->content)->toBe('Tagline output.');
    expect($response->providerName)->toBe('gemini');
    expect($response->finishReason)->toBe('stop');           // lower-cased
    expect($response->usage->totalTokens)->toBe(350);

    Http::assertSent(function (Request $request): bool {
        $url = $request->url();

        return str_contains($url, 'models/gemini-1.5-flash:generateContent')
            && str_contains($url, 'key=gem-test-fake')
            && $request->method() === 'POST';
    });
});

test('payload uses Gemini-specific shape (contents + systemInstruction + generationConfig)', function (): void {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'ok']]], 'finishReason' => 'STOP']],
            'usageMetadata' => ['promptTokenCount' => 1, 'candidatesTokenCount' => 1, 'totalTokenCount' => 2],
        ]),
    ]);

    $provider = new GeminiProvider('gem-test');
    $provider->complete(makeGeminiRequest());

    Http::assertSent(function (Request $request): bool {
        $body = $request->data();
        return isset($body['contents'][0]['parts'][0]['text'])
            && $body['contents'][0]['parts'][0]['text'] === 'Write a tagline.'
            && $body['contents'][0]['role'] === 'user'
            && $body['systemInstruction']['parts'][0]['text'] === 'You are helpful.'
            && $body['generationConfig']['temperature'] === 0.5
            && $body['generationConfig']['maxOutputTokens'] === 200;
    });
});

test('throws AIProviderException when no API key is configured', function (): void {
    $provider = new GeminiProvider('');

    $provider->complete(makeGeminiRequest());
})->throws(AIProviderException::class, 'Gemini API key is not configured');

test('throws RateLimitException on HTTP 429', function (): void {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['message' => 'Quota exceeded.'],
        ], 429),
    ]);

    $provider = new GeminiProvider('gem-test');

    try {
        $provider->complete(makeGeminiRequest());
        $this->fail('Expected RateLimitException.');
    } catch (RateLimitException $e) {
        expect($e->providerName)->toBe('gemini');
    }
});

test('estimateCost returns USD for known Gemini models', function (): void {
    $provider = new GeminiProvider('gem-test');

    // gemini-2.0-flash: $0.10 in, $0.40 out per 1M tokens.
    expect($provider->estimateCost('gemini-2.0-flash', 1_000_000, 1_000_000))->toBe(0.5);

    // gemini-flash-lite-latest: $0.075 in, $0.30 out per 1M tokens.
    expect($provider->estimateCost('gemini-flash-lite-latest', 1_000_000, 1_000_000))
        ->toBe(0.375);
});

test('availableModels returns the supported model list', function (): void {
    $provider = new GeminiProvider('gem-test');

    $models = $provider->availableModels();
    expect($models)->toContain('gemini-2.0-flash', 'gemini-2.5-pro');
});

test('isHealthy returns false when no API key', function (): void {
    $provider = new GeminiProvider('');

    expect($provider->isHealthy())->toBeFalse();
});

test('isHealthy probes /models and caches the result', function (): void {
    Http::fake([
        'generativelanguage.googleapis.com/v1beta/models*' => Http::response(['models' => []]),
    ]);

    $provider = new GeminiProvider('gem-test');

    expect($provider->isHealthy())->toBeTrue();
    expect($provider->isHealthy())->toBeTrue();

    Http::assertSentCount(1);
});
