<?php

declare(strict_types=1);

use App\Actions\Media\DeleteMediaAction;
use App\Actions\Media\UploadMediaAction;
use App\Models\Category;
use App\Models\Language;
use App\Models\Media;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');

    app(SettingService::class)->set('storage.default_disk', 'public', 'file-storage-settings', Setting::TYPE_SELECT);
    app(SettingService::class)->set('storage.max_upload_mb', 10, 'file-storage-settings', Setting::TYPE_NUMBER);
    app(SettingService::class)->set('storage.allowed_file_types', [
        'image/jpeg', 'image/png',
    ], 'file-storage-settings', Setting::TYPE_JSON);
    app(SettingService::class)->reloadCache();

    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();

    $this->user = User::factory()->create();
});

test('delete removes the file from disk and deletes the row', function (): void {
    $file = UploadedFile::fake()->image('photo.jpg');
    $media = app(UploadMediaAction::class)->handle($file, uploaderId: $this->user->id);
    $path = $media->path;

    Storage::disk('public')->assertExists($path);

    app(DeleteMediaAction::class)->handle($media);

    Storage::disk('public')->assertMissing($path);
    expect(Media::query()->find($media->id))->toBeNull();
});

test('delete also removes registered conversion files', function (): void {
    $file = UploadedFile::fake()->image('photo.jpg');
    $media = app(UploadMediaAction::class)->handle($file, uploaderId: $this->user->id);

    // Simulate a Phase 3 image processor that wrote a WebP variant.
    $conversionPath = $media->path.'.webp';
    Storage::disk('public')->put($conversionPath, 'fake-webp-bytes');
    $media->update(['conversions' => ['webp_800' => $conversionPath]]);

    Storage::disk('public')->assertExists($conversionPath);

    app(DeleteMediaAction::class)->handle($media->fresh());

    Storage::disk('public')->assertMissing($conversionPath);
});

test('deleting a media row nulls out FK on dependent Category.image_id', function (): void {
    $file = UploadedFile::fake()->image('photo.jpg');
    $media = app(UploadMediaAction::class)->handle($file, uploaderId: $this->user->id);

    $category = Category::factory()->state(['image_id' => $media->id])->create();
    expect($category->fresh()->image_id)->toBe($media->id);

    app(DeleteMediaAction::class)->handle($media);

    expect($category->fresh()->image_id)->toBeNull();
});
