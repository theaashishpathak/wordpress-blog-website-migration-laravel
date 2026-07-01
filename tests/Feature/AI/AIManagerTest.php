<?php

declare(strict_types=1);

use App\Models\AIUsageLog;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\AIUsageTracker;
use App\Services\AI\CircuitBreaker;
use App\Services\AI\Contracts\AIProvider;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\TokenUsage;
use App\Services\AI\Exceptions\AIProviderException;
use App\Services\AI\Exceptions\ProviderUnavailableException;
use App\Services\AI\Exceptions\QuotaExceededException;
use App\Services\AI\NullProvider;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * Fake provider — always succeeds, returns canned content keyed by the name.
 */
class FakeSuccessProvider implements AIProvider
{
    public function __construct(public string $providerName = 'fake-success') {}

    public function name(): string
    {
        return $this->providerName;
    }

    public function complete(CompletionRequest $request): CompletionResponse
    {
        return new CompletionResponse(
            content: "OK from {$this->providerName}",
            usage: new TokenUsage(10, 20, 30, 0.0001),
            providerName: $this->providerName,
            model: $request->model,
        );
    }

    public function availableModels(): array
    {
        return ['fake-model'];
    }

    public function isHealthy(): bool
    {
        return true;
    }

    public function estimateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        return 0.0;
    }
}

/**
 * Fake provider — always throws.
 */
class FakeFailingProvider implements AIProvider
{
    public function __construct(public string $providerName = 'fake-fail') {}

    public function name(): string
    {
        return $this->providerName;
    }

    public function complete(CompletionRequest $request): CompletionResponse
    {
        throw new AIProviderException("{$this->providerName} is intentionally broken.");
    }

    public function availableModels(): array
    {
        return [];
    }

    public function isHealthy(): bool
    {
        return false;
    }

    public function estimateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        return 0.0;
    }
}

beforeEach(function (): void {
    Cache::flush();
});

function makeManager(): AIManager
{
    return new AIManager(
        circuitBreaker: app(CircuitBreaker::class),
        usageTracker: app(AIUsageTracker::class),
        settings: app(SettingService::class),
        container: app(),
    );
}

function basicRequest(?int $userId = null): CompletionRequest
{
    return new CompletionRequest(
        model: 'fake-model',
        systemPrompt: 'sys',
        userPrompt: 'hello',
        featureKey: 'article_writer',
        userId: $userId,
    );
}

test('manager resolves a registered provider by name', function (): void {
    $manager = makeManager();
    $manager->register('fake-ok', FakeSuccessProvider::class);

    $provider = $manager->driver('fake-ok');

    expect($provider)->toBeInstanceOf(FakeSuccessProvider::class);
});

test('manager falls through to the next provider when the first throws', function (): void {
    app()->bind('Test\\AI\\Failing', fn () => new FakeFailingProvider('primary'));
    app()->bind('Test\\AI\\Working', fn () => new FakeSuccessProvider('secondary'));

    $manager = makeManager();
    $manager->register('primary', 'Test\\AI\\Failing');
    $manager->register('secondary', 'Test\\AI\\Working');

    app(SettingService::class)->set('ai.fallback_chain', ['primary', 'secondary']);
    app(SettingService::class)->reloadCache();

    $response = $manager->complete(basicRequest());

    expect($response->providerName)->toBe('secondary');
    expect($response->content)->toBe('OK from secondary');

    // Both an error log and a success log should exist for this call.
    expect(AIUsageLog::query()->where('status', AIUsageLog::STATUS_FAILED)->where('provider', 'primary')->exists())->toBeTrue();
    expect(AIUsageLog::query()->where('status', AIUsageLog::STATUS_SUCCESS)->where('provider', 'secondary')->exists())->toBeTrue();
});

test('manager throws ProviderUnavailableException when every provider fails', function (): void {
    app()->bind('Test\\AI\\Fail1', fn () => new FakeFailingProvider('p1'));
    app()->bind('Test\\AI\\Fail2', fn () => new FakeFailingProvider('p2'));

    $manager = makeManager();
    $manager->register('p1', 'Test\\AI\\Fail1');
    $manager->register('p2', 'Test\\AI\\Fail2');
    // Replace NullProvider entry with a failing class so the safety net is gone.
    $manager->register(NullProvider::NAME, 'Test\\AI\\Fail2');

    app(SettingService::class)->set('ai.fallback_chain', ['p1', 'p2']);
    app(SettingService::class)->reloadCache();

    $manager->complete(basicRequest());
})->throws(ProviderUnavailableException::class);

test('manager always tries NullProvider as the final safety net', function (): void {
    $manager = makeManager();
    $manager->register(NullProvider::NAME, NullProvider::class);

    // Only configure a failing provider; NullProvider should still be tried last.
    app()->bind('Test\\AI\\Failing', fn () => new FakeFailingProvider('p1'));
    $manager->register('p1', 'Test\\AI\\Failing');

    app(SettingService::class)->set('ai.fallback_chain', ['p1']);
    app(SettingService::class)->reloadCache();

    $response = $manager->complete(basicRequest());

    expect($response->providerName)->toBe(NullProvider::NAME);
    expect($response->content)->toStartWith(NullProvider::PLACEHOLDER_PREFIX);
});

test('manager records usage log with feature key and template metadata', function (): void {
    $user = User::factory()->create();

    $manager = makeManager();
    $manager->register(NullProvider::NAME, NullProvider::class);

    app(SettingService::class)->set('ai.fallback_chain', [NullProvider::NAME]);
    app(SettingService::class)->reloadCache();

    $request = new CompletionRequest(
        model: 'null-stub',
        systemPrompt: 'sys',
        userPrompt: 'usr',
        featureKey: 'seo_meta',
        promptTemplateKey: 'seo_meta.default',
        userId: $user->id,
        metadata: ['template_version' => 2],
    );

    $manager->complete($request);

    $log = AIUsageLog::query()->where('user_id', $user->id)->latest()->first();

    expect($log)->not->toBeNull();
    expect($log->feature_key)->toBe('seo_meta');
    expect($log->prompt_template_key)->toBe('seo_meta.default');
    expect($log->prompt_template_version)->toBe(2);
    expect($log->status)->toBe(AIUsageLog::STATUS_SUCCESS);
});

test('cost ceiling guard prevents new calls when user is over the monthly cap', function (): void {
    $user = User::factory()->create();

    AIUsageLog::factory()
        ->count(2)
        ->create(['user_id' => $user->id, 'estimated_cost_usd' => 20.0]);

    $manager = makeManager();
    $manager->register(NullProvider::NAME, NullProvider::class);

    app(SettingService::class)->set('ai.user_monthly_cost_ceiling_usd', 25.0);
    app(SettingService::class)->reloadCache();

    $manager->complete(basicRequest($user->id));
})->throws(QuotaExceededException::class);

test('cost ceiling does not affect anonymous (system) requests', function (): void {
    $manager = makeManager();
    $manager->register(NullProvider::NAME, NullProvider::class);

    app(SettingService::class)->set('ai.user_monthly_cost_ceiling_usd', 0.01);
    app(SettingService::class)->reloadCache();

    // userId omitted → counts as system job, ceiling skipped.
    $response = $manager->complete(basicRequest(userId: null));

    expect($response->providerName)->toBe(NullProvider::NAME);
});

test('stream() yields multiple chunks and records exactly one usage log', function (): void {
    $manager = makeManager();
    $manager->register(NullProvider::NAME, NullProvider::class);

    app(SettingService::class)->set('ai.fallback_chain', [NullProvider::NAME]);
    app(SettingService::class)->reloadCache();

    $user = User::factory()->create();

    $chunks = iterator_to_array($manager->stream(basicRequest($user->id)));

    expect(count($chunks))->toBeGreaterThan(1);
    /** @var \App\Services\AI\DataTransferObjects\StreamChunk $finalChunk */
    $finalChunk = end($chunks);
    expect($finalChunk->isFinal)->toBeTrue();

    expect(AIUsageLog::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('circuit breaker is incremented on provider failure', function (): void {
    app()->bind('Test\\AI\\Fail', fn () => new FakeFailingProvider('busted'));

    $manager = makeManager();
    $manager->register('busted', 'Test\\AI\\Fail');
    $manager->register(NullProvider::NAME, NullProvider::class);

    app(SettingService::class)->set('ai.fallback_chain', ['busted']);
    app(SettingService::class)->reloadCache();

    // First call — failure recorded, NullProvider serves it.
    $manager->complete(basicRequest());

    expect(app(CircuitBreaker::class)->failureCount('busted'))->toBe(1);
});

test('manager exposes the list of registered providers', function (): void {
    $manager = makeManager();
    $manager->register(NullProvider::NAME, NullProvider::class);
    $manager->register('openai', FakeSuccessProvider::class);
    $manager->register('gemini', FakeSuccessProvider::class);

    expect($manager->registeredProviders())
        ->toContain(NullProvider::NAME)
        ->toContain('openai')
        ->toContain('gemini');
});
