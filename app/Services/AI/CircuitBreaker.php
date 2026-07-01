<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Per-provider failure tracker.
 *
 * - Counts consecutive failures in the cache.
 * - After FAILURE_THRESHOLD failures within COUNTER_TTL, opens the breaker
 *   for COOLDOWN_SECONDS — AIManager will skip this provider during that
 *   window and fall through to the next one in the chain.
 * - A successful call resets both the counter and the open flag.
 *
 * Spec: docs/AI Provider Contract.txt Section 7.
 */
class CircuitBreaker
{
    public const FAILURE_THRESHOLD = 5;

    public const COOLDOWN_SECONDS = 300;        // 5 minutes

    public const COUNTER_TTL_SECONDS = 900;     // 15 minutes (sliding window)

    public function __construct(private ?CacheRepository $cache = null) {}

    public function allow(string $providerName): bool
    {
        return ! $this->store()->has($this->openKey($providerName));
    }

    public function recordSuccess(string $providerName): void
    {
        $this->store()->forget($this->counterKey($providerName));
        $this->store()->forget($this->openKey($providerName));
    }

    public function recordFailure(string $providerName, Throwable $exception): void
    {
        $key = $this->counterKey($providerName);
        $store = $this->store();

        // Ensure the counter exists before incrementing.
        if (! $store->has($key)) {
            $store->put($key, 0, self::COUNTER_TTL_SECONDS);
        }

        $count = (int) $store->increment($key);

        // Refresh TTL on each failure so persistent-failure providers
        // accumulate until the threshold trips.
        $store->put($key, $count, self::COUNTER_TTL_SECONDS);

        if ($count >= self::FAILURE_THRESHOLD) {
            $store->put($this->openKey($providerName), true, self::COOLDOWN_SECONDS);
        }
    }

    public function failureCount(string $providerName): int
    {
        return (int) $this->store()->get($this->counterKey($providerName), 0);
    }

    public function isOpen(string $providerName): bool
    {
        return (bool) $this->store()->has($this->openKey($providerName));
    }

    public function reset(string $providerName): void
    {
        $this->store()->forget($this->counterKey($providerName));
        $this->store()->forget($this->openKey($providerName));
    }

    private function counterKey(string $provider): string
    {
        return "ai.cb.counter.{$provider}";
    }

    private function openKey(string $provider): string
    {
        return "ai.cb.open.{$provider}";
    }

    private function store(): CacheRepository
    {
        return $this->cache ?? Cache::store();
    }
}
