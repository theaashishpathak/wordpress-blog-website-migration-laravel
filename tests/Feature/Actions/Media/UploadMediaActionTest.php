<?php

declare(strict_types=1);

use App\Actions\Media\UploadMediaAction;
use App\Models\Media;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');

    // Configure permissive upload settings for tests.
    Setting::query()->create([
        'group' => 'file-storage-settings',
        'key' => 'storage.default_disk',
        'type' => Setting::TYPE_SELECT,
    ])->setValue('public') ?? null;

    app(SettingService::class)->set('storage.default_disk', 'public', 'file-storage-settings', Setting::TYPE_SELECT);
    app(SettingService::class)->set('storage.max_upload_mb', 10, 'file-storage-settings', Setting::TYPE_NUMBER);
    app(SettingService::class)->set('storage.allowed_file_types', [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'application/pdf',
    ], 'file-storage-settings', Setting::TYPE_JSON);
    app(SettingService::class)->reloadCache();

    $this->user = User::factory()->create();
});

test('uploads an image, extracts dimensions, and creates a Media row', function (): void {
    $file = UploadedFile::fake()->image('photo.jpg', 1200, 800);

    $media = app(UploadMediaAction::class)->handle(
        $file,
        meta: ['alt_text' => 'A test photo', 'caption' => 'Caption here'],
        uploaderId: $this->user->id,
    );

    expect($media)->toBeInstanceOf(Media::class);
    expect($media->disk)->toBe('public');
    expect($media->mime_type)->toBe('image/jpeg');
    expect($media->width)->toBe(1200);
    expect($media->height)->toBe(800);
    expect($media->alt_text)->toBe('A test photo');
    expect($media->caption)->toBe('Caption here');
    expect($media->uploaded_by)->toBe($this->user->id);

    Storage::disk('public')->assertExists($media->path);
});

test('original filename is preserved separately from the stored filename', function (): void {
    $file = UploadedFile::fake()->image('My Vacation Photo.jpg');

    $media = app(UploadMediaAction::class)->handle($file, uploaderId: $this->user->id);

    expect($media->original_filename)->toBe('My Vacation Photo.jpg');
    expect($media->filename)->not->toBe('My Vacation Photo.jpg');   // slugged + random suffix
    expect($media->filename)->toContain('my-vacation-photo');
    expect($media->filename)->toEndWith('.jpg');
});

test('upload rejects disallowed mime types', function (): void {
    $file = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');

    app(UploadMediaAction::class)->handle($file, uploaderId: $this->user->id);
})->throws(ValidationException::class);

test('upload rejects files over the configured size cap', function (): void {
    app(SettingService::class)->set('storage.max_upload_mb', 1, 'file-storage-settings', Setting::TYPE_NUMBER);
    app(SettingService::class)->reloadCache();

    // 2 MB JPEG ≫ 1 MB cap.
    $file = UploadedFile::fake()->create('big.jpg', 2048, 'image/jpeg');

    app(UploadMediaAction::class)->handle($file, uploaderId: $this->user->id);
})->throws(ValidationException::class);

test('non-image files (e.g. PDFs) get null dimensions', function (): void {
    $file = UploadedFile::fake()->create('report.pdf', 500, 'application/pdf');

    $media = app(UploadMediaAction::class)->handle($file, uploaderId: $this->user->id);

    expect($media->mime_type)->toBe('application/pdf');
    expect($media->width)->toBeNull();
    expect($media->height)->toBeNull();
});

test('SVG images store as image/svg+xml with null dimensions', function (): void {
    $file = UploadedFile::fake()->createWithContent(
        'logo.svg',
        '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"></svg>',
    );

    // Manually mark the mime so the fake matches what Laravel would detect.
    $file = UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml');

    $media = app(UploadMediaAction::class)->handle($file, uploaderId: $this->user->id);

    expect($media->mime_type)->toBe('image/svg+xml');
    expect($media->width)->toBeNull();
    expect($media->height)->toBeNull();
});
