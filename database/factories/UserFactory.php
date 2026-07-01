<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->phoneNumber(),
            'avatar' => null,
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'portal_type' => 'author',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * State: admin portal user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'portal_type' => 'admin',
        ]);
    }

    /**
     * State: author portal user (writers, editors, internal newsroom team).
     */
    public function author(): static
    {
        return $this->state(fn (array $attributes): array => [
            'portal_type' => 'author',
        ]);
    }

    /**
     * Back-compat alias — old `staff()` callers still resolve to the
     * new `author` portal type. Safe to remove once all tests/seeders
     * have been migrated.
     */
    public function staff(): static
    {
        return $this->author();
    }

    /**
     * State: visitor portal user (frontend reader, subscriber, commenter).
     */
    public function visitor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'portal_type' => 'visitor',
        ]);
    }

    /**
     * State: inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }

    /**
     * State: suspended user.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Add a believable bio + social handles. Useful for visitor and
     * author demo accounts so the public profile page renders fully.
     */
    public function withBio(): static
    {
        $bios = [
            'Reader, tinkerer, occasional commenter.',
            'Curious about everything between politics and physics.',
            'Newsroom alum — now reading more than I write.',
            'Books, tea, and the long-form weekend edit.',
            'Tracking AI, climate, and how cities work.',
            'Sceptic by default, persuadable on the evidence.',
        ];

        return $this->state(fn (array $attributes): array => [
            'bio' => fake()->randomElement($bios),
            'social_links' => [
                'twitter' => '@'.Str::slug($attributes['name'] ?? 'reader', ''),
                'linkedin' => Str::slug($attributes['name'] ?? 'reader'),
            ],
        ]);
    }
}
