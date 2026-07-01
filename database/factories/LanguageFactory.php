<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = Str::lower(fake()->unique()->lexify('??'));

        return [
            'code' => $code,
            'name' => Str::title(fake()->word()),
            'native_name' => Str::title(fake()->word()),
            'flag_emoji' => null,
            'flag_icon' => null,
            'direction' => Language::DIRECTION_LTR,
            'is_default' => false,
            'is_active' => true,
            'is_admin_locale' => false,
            'sort_order' => 0,
            'locale_php' => null,
            'date_format' => null,
            'number_format' => null,
        ];
    }

    public function english(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'flag_emoji' => '🇺🇸',
            'direction' => Language::DIRECTION_LTR,
            'is_active' => true,
            'is_admin_locale' => true,
            'locale_php' => 'en_US',
            'sort_order' => 1,
        ]);
    }

    public function bangla(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => 'bn',
            'name' => 'Bangla',
            'native_name' => 'বাংলা',
            'flag_emoji' => '🇧🇩',
            'direction' => Language::DIRECTION_LTR,
            'is_active' => true,
            'is_admin_locale' => true,
            'locale_php' => 'bn_BD',
            'sort_order' => 2,
        ]);
    }

    public function arabicRtl(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => 'ar',
            'name' => 'Arabic',
            'native_name' => 'العربية',
            'flag_emoji' => '🇸🇦',
            'direction' => Language::DIRECTION_RTL,
            'is_active' => true,
            'locale_php' => 'ar_SA',
            'sort_order' => 3,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function adminLocale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_admin_locale' => true,
        ]);
    }
}
