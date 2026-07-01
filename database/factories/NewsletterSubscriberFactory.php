<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    protected $model = NewsletterSubscriber::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->optional()->name(),
            'status' => NewsletterSubscriber::STATUS_PENDING,
            'confirmation_token' => Str::random(48),
            'unsubscribe_token' => Str::random(48),
            'source' => fake()->randomElement(['footer_form', 'inline_widget', 'api', null]),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => NewsletterSubscriber::STATUS_CONFIRMED,
            'confirmed_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            'confirmed_at' => now()->subDays(fake()->numberBetween(30, 90)),
            'unsubscribed_at' => now()->subDays(fake()->numberBetween(1, 20)),
        ]);
    }

    public function bounced(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => NewsletterSubscriber::STATUS_BOUNCED,
        ]);
    }
}
