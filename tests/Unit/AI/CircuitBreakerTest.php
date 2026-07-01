<?php

declare(strict_types=1);

use App\Services\AI\CircuitBreaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    // Cache is array-driven during tests; flush so each test starts cold.
    Cache::flush();
});

test('breaker allows calls by default', function (): void {
    $breaker = new CircuitBreaker;

    expect($breaker->allow('openai'))->toBeTrue();
    expect($breaker->failureCount('openai'))->toBe(0);
    expect($breaker->isOpen('openai'))->toBeFalse();
});

test('breaker opens after threshold failures', function (): void {
    $breaker = new CircuitBreaker;

    for ($i = 0; $i < CircuitBreaker::FAILURE_THRESHOLD; $i++) {
        $breaker->recordFailure('openai', new RuntimeException('boom'));
    }

    expect($breaker->isOpen('openai'))->toBeTrue();
    expect($breaker->allow('openai'))->toBeFalse();
    expect($breaker->failureCount('openai'))->toBe(CircuitBreaker::FAILURE_THRESHOLD);
});

test('breaker stays closed below threshold', function (): void {
    $breaker = new CircuitBreaker;

    for ($i = 0; $i < CircuitBreaker::FAILURE_THRESHOLD - 1; $i++) {
        $breaker->recordFailure('gemini', new RuntimeException('boom'));
    }

    expect($breaker->isOpen('gemini'))->toBeFalse();
    expect($breaker->allow('gemini'))->toBeTrue();
});

test('success resets failure counter and opens flag', function (): void {
    $breaker = new CircuitBreaker;

    for ($i = 0; $i < CircuitBreaker::FAILURE_THRESHOLD; $i++) {
        $breaker->recordFailure('openai', new RuntimeException('boom'));
    }

    expect($breaker->isOpen('openai'))->toBeTrue();

    $breaker->recordSuccess('openai');

    expect($breaker->isOpen('openai'))->toBeFalse();
    expect($breaker->failureCount('openai'))->toBe(0);
    expect($breaker->allow('openai'))->toBeTrue();
});

test('breakers are isolated per provider', function (): void {
    $breaker = new CircuitBreaker;

    for ($i = 0; $i < CircuitBreaker::FAILURE_THRESHOLD; $i++) {
        $breaker->recordFailure('openai', new RuntimeException('boom'));
    }

    expect($breaker->allow('openai'))->toBeFalse();
    expect($breaker->allow('gemini'))->toBeTrue();
});

test('reset clears state', function (): void {
    $breaker = new CircuitBreaker;

    for ($i = 0; $i < CircuitBreaker::FAILURE_THRESHOLD; $i++) {
        $breaker->recordFailure('claude', new RuntimeException('boom'));
    }

    $breaker->reset('claude');

    expect($breaker->isOpen('claude'))->toBeFalse();
    expect($breaker->failureCount('claude'))->toBe(0);
});
