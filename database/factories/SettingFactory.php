<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = 'app.'.Str::slug(fake()->unique()->word());

        return [
            'group' => 'app-preferences',
            'key' => $key,
            'value' => json_encode(fake()->word()),
            'type' => Setting::TYPE_TEXT,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
