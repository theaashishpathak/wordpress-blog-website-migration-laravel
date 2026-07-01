<?php

declare(strict_types=1);

use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\Exceptions\AIProviderException;
use App\Services\AI\Exceptions\RateLimitException;
use App\Services\AI\OpenAIProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Cache::flush();
});

function makeOpenAIRequest(string $model = 'gpt-4o-mini'): CompletionRequest
{
    return new CompletionRequest(
        model: $model,
        systemPrompt: 'You are helpful.',
        userPrompt: 'Write a tagline.',
        temperature: 0.5,
        maxTokens: 200,
        featureKey: 'article_writer',
    );
}

test('complete posts to chat/completions endpoint with correct payload', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [[
                'message' => ['content' => 'Generated tagline.'],
                'finish_reason' => 'stop',
            ]],
            'usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 250,
                'total_tokens' => 350,
            ],
        ]),
    ]);

    $provider = new OpenAIProvider('sk-test-fake');
    $response = $provider->complete(makeOpenAIRequest());

    expect($response->content)->toBe('Generated tagline.');
    expect($response->providerName)->toBe('openai');
    expect($response->finishReason)->toBe('stop');
    expect($response->usage->promptTokens)->toBe(100);
    expect($response->usage->completionTokens)->toBe(250);
    expect($response->usage->totalTokens)->toBe(350);

    Http::assertSent(function (Request $request): bool {
        if ($request->url() !== 'https://api.openai.com/v1/chat/completions') {
            return false;
        }

        if ($request->method() !== 'POST') {
            return false;
        }

        if ($request->header('Authorization')[0] !== 'Bearer sk-test-fake') {
            return false;
        }

        $body = $request->data();
        return $body['model'] === 'gpt-4o-mini'
            && $body['temperature'] === 0.5
            && $body['max_tokens'] === 200
            && $body['messages'][0]['role'] === 'system'
            && $body['messages'][0]['content'] === 'You are helpful.'
            && $body['messages'][1]['role'] === 'user'
            && $body['messages'][1]['content'] === 'Write a tagline.';
    });
});

test('throws AIProviderException when no API key is configured', function (): void {
    $provider = new OpenAIProvider('');

    $provider->complete(makeOpenAIRequest());
})->throws(AIProviderException::class, 'OpenAI API key is not configured');

test('throws RateLimitException on HTTP 429', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'error' => ['message' => 'Rate limit reached for organisation X.'],
        ], 429, ['Retry-After' => '30']),
    ]);

    $provider = new OpenAIProvider('sk-test');

    try {
        $provider->complete(makeOpenAIRequest());
        $this->fail('Expected RateLimitException.');
    } catch (RateLimitException $exception) {
        expect($exception->getMessage())->toContain('Rate limit reached');
        expect($exception->providerName)->toBe('openai');
        expect($exception->retryAfterSeconds)->toBe(30);
    }
});

test('throws AIProviderException on other 4xx errors', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'error' => ['message' => 'Invalid model.'],
        ], 400),
    ]);

    $provider = new OpenAIProvider('sk-test');

    try {
        $provider->complete(makeOpenAIRequest());
        $this->fail('Expected AIProviderException.');
    } catch (AIProviderException $e) {
        expect($e)->not->toBeInstanceOf(RateLimitException::class);
        expect($e->getMessage())->toContain('Invalid model');
        expect($e->getCode())->toBe(400);
    }
});

test('estimateCost returns accurate USD for known models', function (): void {
    $provider = new OpenAIProvider('sk-test');

    // gpt-4o-mini: $0.15 / 1M input, $0.60 / 1M output
    // 1M input + 1M output = 0.15 + 0.60 = 0.75
    expect($provider->estimateCost('gpt-4o-mini', 1_000_000, 1_000_000))->toBe(0.75);

    // gpt-4o: $2.50 / 1M input, $10 / 1M output
    expect($provider->estimateCost('gpt-4o', 1_000_000, 1_000_000))->toBe(12.50);

    // gpt-3.5-turbo: $0.50 in, $1.50 out
    expect($provider->estimateCost('gpt-3.5-turbo', 1_000_000, 1_000_000))->toBe(2.00);
});

test('estimateCost handles unknown models with conservative default', function (): void {
    $provider = new OpenAIProvider('sk-test');

    // Unknown model falls back to gpt-3.5-turbo rates.
    expect($provider->estimateCost('gpt-future-7', 1_000_000, 1_000_000))->toBe(2.00);
});

test('availableModels returns the supported model list', function (): void {
    $provider = new OpenAIProvider('sk-test');

    $models = $provider->availableModels();
    expect($models)->toContain('gpt-4o', 'gpt-4o-mini', 'gpt-3.5-turbo');
});

test('isHealthy returns false when no API key is set', function (): void {
    $provider = new OpenAIProvider('');

    expect($provider->isHealthy())->toBeFalse();
});

test('isHealthy probes /models endpoint and caches the result', function (): void {
    Http::fake([
        'api.openai.com/v1/models' => Http::response(['data' => []]),
    ]);

    $provider = new OpenAIProvider('sk-test');

    expect($provider->isHealthy())->toBeTrue();
    expect($provider->isHealthy())->toBeTrue();   // cached — should not hit HTTP again

    Http::assertSentCount(1);
});

test('top_p and stop_sequences are forwarded when set', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'ok'], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
        ]),
    ]);

    $provider = new OpenAIProvider('sk-test');
    $provider->complete(new CompletionRequest(
        model: 'gpt-4o-mini',
        systemPrompt: 'sys',
        userPrompt: 'hi',
        topP: 0.9,
        stopSequences: ['END'],
    ));

    Http::assertSent(function (Request $request): bool {
        $body = $request->data();
        return ($body['top_p'] ?? null) === 0.9
            && ($body['stop'] ?? null) === ['END'];
    });
});
