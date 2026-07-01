<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AIPromptTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIPromptTemplate>
 */
class AIPromptTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'test_template.'.fake()->unique()->slug(2, false),
            'version' => 1,
            'locale' => 'en',
            'system_prompt' => 'You are a helpful test assistant.',
            'user_prompt_template' => 'Write something about {{topic}}.',
            'variables' => ['topic'],
            'model_hint' => 'gpt-4o-mini',
            'temperature_hint' => 0.7,
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function forKey(string $key): static
    {
        return $this->state(fn (array $a): array => [
            'key' => $key,
        ]);
    }

    public function forLocale(string $locale): static
    {
        return $this->state(fn (array $a): array => [
            'locale' => $locale,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $a): array => [
            'is_active' => false,
        ]);
    }

    public function version(int $version): static
    {
        return $this->state(fn (array $a): array => [
            'version' => $version,
        ]);
    }

    public function articleWriter(): static
    {
        return $this->state(fn (array $a): array => [
            'key' => 'article_writer.long_form',
            'system_prompt' => 'You are a professional long-form article writer.',
            'user_prompt_template' => 'Write a {{word_count}}-word {{tone}} article about {{topic}}.',
            'variables' => ['word_count', 'tone', 'topic'],
        ]);
    }

    public function seoMeta(): static
    {
        return $this->state(fn (array $a): array => [
            'key' => 'seo_meta.default',
            'system_prompt' => 'You are an SEO expert.',
            'user_prompt_template' => 'Generate meta title (max 60 chars) and meta description (max 160 chars) for an article titled "{{title}}".',
            'variables' => ['title'],
        ]);
    }
}
