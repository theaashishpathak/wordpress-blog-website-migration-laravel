<?php

declare(strict_types=1);

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('isImage detects image mime types', function (): void {
    $image = Media::factory()->image('image/png')->create();
    $video = Media::factory()->video()->create();
    $doc = Media::factory()->document()->create();

    expect($image->isImage())->toBeTrue();
    expect($image->isVideo())->toBeFalse();
    expect($image->isDocument())->toBeFalse();

    expect($video->isVideo())->toBeTrue();
    expect($video->isImage())->toBeFalse();

    expect($doc->isDocument())->toBeTrue();
    expect($doc->isImage())->toBeFalse();
});

test('sizeFormatted produces human-readable byte counts', function (): void {
    $small = Media::factory()->state(['size' => 512])->create();
    $kb = Media::factory()->state(['size' => 2048])->create();
    $mb = Media::factory()->state(['size' => 2 * 1024 * 1024])->create();

    expect($small->sizeFormatted())->toBe('512 B');
    expect($kb->sizeFormatted())->toBe('2.00 KB');
    expect($mb->sizeFormatted())->toBe('2.00 MB');
});

test('images scope returns only image-typed media', function (): void {
    Media::factory()->count(3)->image()->create();
    Media::factory()->video()->create();
    Media::factory()->document()->create();

    expect(Media::query()->images()->count())->toBe(3);
});

test('videos and documents scopes filter correctly', function (): void {
    Media::factory()->image()->create();
    Media::factory()->count(2)->video()->create();
    Media::factory()->document()->create();

    expect(Media::query()->videos()->count())->toBe(2);
    expect(Media::query()->documents()->count())->toBe(1);
});

test('recent scope filters by creation date window', function (): void {
    Media::factory()->image()->create();
    Media::factory()->image()->state([
        'created_at' => now()->subDays(60),
        'updated_at' => now()->subDays(60),
    ])->create();

    expect(Media::query()->recent(days: 30)->count())->toBe(1);
});

test('conversionUrl falls back to original when key missing', function (): void {
    $media = Media::factory()->image()->state([
        'path' => 'media/abc/test.jpg',
        'conversions' => null,
    ])->create();

    expect($media->conversionUrl('webp_800'))->toBe($media->url());
});

test('conversionUrl returns specific path when conversion exists', function (): void {
    $media = Media::factory()->image()->state([
        'path' => 'media/abc/test.jpg',
        'conversions' => ['webp_800' => 'media/abc/webp_800.webp'],
    ])->create();

    expect($media->conversionUrl('webp_800'))->toContain('webp_800.webp');
});

test('absolute URL paths bypass storage URL resolution', function (): void {
    $media = Media::factory()->state([
        'path' => 'https://cdn.example.com/photo.jpg',
    ])->create();

    expect($media->url())->toBe('https://cdn.example.com/photo.jpg');
});
