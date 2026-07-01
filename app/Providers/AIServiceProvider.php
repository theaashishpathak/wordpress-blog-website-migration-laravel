<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\AI\AIManager;
use App\Services\AI\AIUsageTracker;
use App\Services\AI\CircuitBreaker;
use App\Services\AI\GeminiProvider;
use App\Services\AI\NullProvider;
use App\Services\AI\OpenAIProvider;
use App\Services\AI\PromptBuilder;
use App\Services\SettingService;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the AI subsystem.
 *
 * Phase 3A:
 *   - Registers OpenAIProvider + GeminiProvider as singletons, each
 *     configured from encrypted settings (ai.openai_api_key /
 *     ai.gemini_api_key).
 *   - Keeps NullProvider as final fallback in the AIManager chain.
 *
 * Application code MUST inject AIManager — never a concrete provider.
 * The arch test (tests/Arch.php) enforces this.
 */
class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CircuitBreaker::class);
        $this->app->singleton(AIUsageTracker::class);
        $this->app->singleton(PromptBuilder::class);
        $this->app->singleton(NullProvider::class);

        $this->registerOpenAI();
        $this->registerGemini();

        $this->app->singleton(AIManager::class, function ($app): AIManager {
            $manager = new AIManager(
                circuitBreaker: $app->make(CircuitBreaker::class),
                usageTracker: $app->make(AIUsageTracker::class),
                settings: $app->make(SettingService::class),
                container: $app,
            );

            // Order is documentation only — AIManager picks the active
            // chain at runtime from settings.ai.fallback_chain.
            $manager->register(NullProvider::NAME, NullProvider::class);
            $manager->register(OpenAIProvider::NAME, OpenAIProvider::class);
            $manager->register(GeminiProvider::NAME, GeminiProvider::class);

            return $manager;
        });
    }

    public function boot(): void
    {
        // Providers resolved lazily on first AIManager::driver() call.
    }

    private function registerOpenAI(): void
    {
        $this->app->singleton(OpenAIProvider::class, function ($app): OpenAIProvider {
            $settings = $app->make(SettingService::class);

            return new OpenAIProvider(
                apiKey: (string) ($settings->get('ai.openai_api_key', '') ?? ''),
                baseUrl: (string) ($settings->get('ai.openai_base_url', 'https://api.openai.com/v1') ?? 'https://api.openai.com/v1'),
                timeoutSeconds: 60,
            );
        });
    }

    private function registerGemini(): void
    {
        $this->app->singleton(GeminiProvider::class, function ($app): GeminiProvider {
            $settings = $app->make(SettingService::class);

            return new GeminiProvider(
                apiKey: (string) ($settings->get('ai.gemini_api_key', '') ?? ''),
                baseUrl: 'https://generativelanguage.googleapis.com/v1beta',
                timeoutSeconds: 60,
            );
        });
    }
}
