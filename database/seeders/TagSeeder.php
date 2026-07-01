<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = User::query()->value('id') ?? 1;

        $tags = [
            ['name' => 'Featured', 'color' => '#fbbf24', 'type' => Tag::TYPE_GENERAL],
            ['name' => 'New', 'color' => '#60a5fa', 'type' => Tag::TYPE_GENERAL],
            ['name' => 'Verified', 'color' => '#10b981', 'type' => Tag::TYPE_GENERAL],
            ['name' => 'Premium', 'color' => '#8b5cf6', 'type' => Tag::TYPE_GENERAL],
            ['name' => 'Trending', 'color' => '#ef4444', 'type' => Tag::TYPE_GENERAL],
        ];

        foreach ($tags as $i => $tag) {
            Tag::query()->updateOrCreate(
                ['code' => 'TAG-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'name' => $tag['name'],
                    'slug' => Str::slug($tag['name']).'-'.($i + 1),
                    'color' => $tag['color'],
                    'type' => $tag['type'],
                    'status' => Tag::STATUS_PUBLISHED,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]
            );
        }
    }
}
