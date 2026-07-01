<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;
use App\Services\SettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Upload a file into the media library.
 *
 * Validation is settings-driven so admins can adjust upload limits
 * without a code change:
 *   storage.max_upload_mb         — hard cap, MB
 *   storage.allowed_file_types    — JSON array of mime types
 *   storage.default_disk          — public | local | s3
 *   storage.private_uploads       — when true, files go to a private path
 *
 * Dimensions are extracted for images via getimagesize() — pure PHP/GD,
 * no Imagick or Intervention Image dependency.
 *
 * Input:
 *   - $file: UploadedFile (typically from a Livewire WithFileUploads or HTTP form)
 *   - $meta: optional ['alt_text', 'caption', 'credit', 'source_url']
 *
 * Returns the persisted Media row.
 */
class UploadMediaAction
{
    public function __construct(private SettingService $settings) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function handle(UploadedFile $file, array $meta = [], ?int $uploaderId = null): Media
    {
        $this->validateMimeType($file);
        $this->validateSize($file);

        return DB::transaction(function () use ($file, $meta, $uploaderId): Media {
            $disk = (string) $this->settings->get('storage.default_disk', 'public');

            $folder = 'media/'.now()->format('Y/m');
            $filename = $this->buildStoredFilename($file);

            $relativePath = $file->storeAs($folder, $filename, ['disk' => $disk]);

            [$width, $height] = $this->extractDimensions($file);

            return Media::query()->create([
                'disk' => $disk,
                'path' => $relativePath,
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
                'width' => $width,
                'height' => $height,
                'alt_text' => $meta['alt_text'] ?? null,
                'caption' => $meta['caption'] ?? null,
                'credit' => $meta['credit'] ?? null,
                'source_url' => $meta['source_url'] ?? null,
                'uploaded_by' => $uploaderId ?? auth()->id(),
            ]);
        });
    }

    private function validateMimeType(UploadedFile $file): void
    {
        $allowed = (array) $this->settings->get('storage.allowed_file_types', [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            'video/mp4',
            'application/pdf',
        ]);

        if ($allowed === []) {
            return;
        }

        $mime = (string) $file->getMimeType();

        if (! in_array($mime, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => "Mime type [{$mime}] is not allowed.",
            ]);
        }
    }

    private function validateSize(UploadedFile $file): void
    {
        $maxMb = (float) $this->settings->get('storage.max_upload_mb', 10);

        if ($maxMb <= 0) {
            return;
        }

        $maxBytes = (int) ($maxMb * 1024 * 1024);
        $actual = (int) $file->getSize();

        if ($actual > $maxBytes) {
            $actualMb = number_format($actual / 1024 / 1024, 2);

            throw ValidationException::withMessages([
                'file' => "File size {$actualMb} MB exceeds the {$maxMb} MB limit.",
            ]);
        }
    }

    /**
     * @return array{0:int|null, 1:int|null}
     */
    private function extractDimensions(UploadedFile $file): array
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            return [null, null];
        }

        // SVG isn't a raster image — getimagesize() handles it weirdly,
        // skip dimension extraction for vectors.
        if ($file->getMimeType() === 'image/svg+xml') {
            return [null, null];
        }

        $info = @getimagesize($file->getRealPath());

        if ($info === false) {
            return [null, null];
        }

        return [(int) $info[0], (int) $info[1]];
    }

    private function buildStoredFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        if ($base === '') {
            $base = 'file';
        }

        return $base.'-'.Str::random(8).($extension !== '' ? '.'.$extension : '');
    }
}
