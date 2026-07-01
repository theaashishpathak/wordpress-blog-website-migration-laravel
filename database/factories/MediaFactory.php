<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 *
 * Factory creates metadata only — no real file uploads. Tests that need
 * an actual stored file should use UploadMediaAction with
 * Storage::fake() + UploadedFile::fake().
 */
class MediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->unique()->slug(3).'.jpg';

        return [
            'disk' => 'public',
            'path' => 'media/'.fake()->uuid().'/'.$filename,
            'filename' => $filename,
            'original_filename' => $filename,
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(50_000, 5_000_000),
            'width' => fake()->numberBetween(800, 2400),
            'height' => fake()->numberBetween(600, 1600),
            'alt_text' => fake()->sentence(),
            'caption' => null,
            'credit' => null,
            'source_url' => null,
            'conversions' => null,
            'uploaded_by' => null,
        ];
    }

    public function image(string $mimeType = 'image/jpeg'): static
    {
        return $this->state(fn (array $a): array => [
            'mime_type' => $mimeType,
        ]);
    }

    public function video(): static
    {
        $filename = fake()->unique()->slug(3).'.mp4';

        return $this->state(fn (array $a): array => [
            'mime_type' => 'video/mp4',
            'filename' => $filename,
            'original_filename' => $filename,
            'path' => 'media/'.fake()->uuid().'/'.$filename,
            'width' => null,
            'height' => null,
            'size' => fake()->numberBetween(1_000_000, 50_000_000),
        ]);
    }

    public function document(): static
    {
        $filename = fake()->unique()->slug(3).'.pdf';

        return $this->state(fn (array $a): array => [
            'mime_type' => 'application/pdf',
            'filename' => $filename,
            'original_filename' => $filename,
            'path' => 'media/'.fake()->uuid().'/'.$filename,
            'width' => null,
            'height' => null,
        ]);
    }

    public function withConversions(): static
    {
        return $this->state(function (array $a): array {
            $folder = (string) Str::beforeLast((string) $a['path'], '/');

            return [
                'conversions' => [
                    'webp_800' => $folder.'/webp_800.webp',
                    'thumb_300' => $folder.'/thumb_300.jpg',
                ],
            ];
        });
    }
}
