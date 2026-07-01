<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImportedItem;
use App\Models\ImportSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportedItem>
 */
class ImportedItemFactory extends Factory
{
    protected $model = ImportedItem::class;

    public function definition(): array
    {
        return [
            'source_id' => ImportSource::factory(),
            'guid' => 'https://feed.example.com/item-'.fake()->unique()->randomNumber(8),
            'item_url' => fake()->url(),
            'title' => fake()->sentence(),
            'post_id' => null,
            'imported_at' => now(),
        ];
    }
}
