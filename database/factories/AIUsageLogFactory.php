<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AIUsageLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIUsageLog>
 */
class AIUsageLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prompt = fake()->numberBetween(50, 500);
        $completion = fake()->numberBetween(100, 2000);

        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(['openai', 'gemini', 'claude', 'null']),
            'model' => fake()->randomElement(['gpt-4o-mini', 'gpt-4o', 'gemini-1.5-pro', 'claude-3-5-sonnet']),
            'feature_key' => fake()->randomElement([
                'article_writer', 'seo_meta', 'rewrite', 'translate',
                'rss_rewrite', 'social_caption', 'faq', 'image_alt',
            ]),
            'prompt_template_key' => null,
            'prompt_template_version' => null,
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
            'total_tokens' => $prompt + $completion,
            'estimated_cost_usd' => fake()->randomFloat(6, 0.0001, 0.5),
            'duration_ms' => fake()->numberBetween(200, 5000),
            'status' => AIUsageLog::STATUS_SUCCESS,
            'error_message' => null,
            'request_metadata' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => AIUsageLog::STATUS_FAILED,
            'error_message' => fake()->sentence(),
            'completion_tokens' => 0,
        ]);
    }

    public function rateLimited(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => AIUsageLog::STATUS_RATE_LIMITED,
            'completion_tokens' => 0,
        ]);
    }

    public function feature(string $featureKey): static
    {
        return $this->state(fn (array $a): array => [
            'feature_key' => $featureKey,
        ]);
    }

    public function provider(string $providerName): static
    {
        return $this->state(fn (array $a): array => [
            'provider' => $providerName,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $a): array => [
            'user_id' => null,
        ]);
    }
}
