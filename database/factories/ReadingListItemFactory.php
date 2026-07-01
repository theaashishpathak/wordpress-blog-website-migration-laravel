<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use App\Models\ReadingListItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingListItem>
 */
class ReadingListItemFactory extends Factory
{
    protected $model = ReadingListItem::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'added_at' => now(),
            'dismissed_at' => null,
        ];
    }

    public function dismissed(): static
    {
        return $this->state(fn (): array => ['dismissed_at' => now()]);
    }
}
