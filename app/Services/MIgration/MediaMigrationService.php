<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * MediaMigrationService
 *
 * Migrates WordPress media attachments into Rupantrix's `media` table.
 *
 * Responsibilities:
 * 1. Extract attachments from wp_posts (post_type = 'attachment').
 * 2. Extract metadata from wp_postmeta (_wp_attached_file, _wp_attachment_metadata, _wp_attachment_image_alt).
 * 3. Extract dimensions (width, height), mime_type, alt_text, caption, credit.
 * 4. Update media path to relative Laravel storage path so frontend never depends on old WordPress domain.
 */
class MediaMigrationService extends BaseMigrationService
{
    protected bool $localizeFiles = true;

    public function enableLocalization(bool $enable = true): static
    {
        $this->localizeFiles = $enable;
        return $this;
    }

    public function run(): void
    {
        $this->info('Starting Media migration...');

        // Fetch all WordPress attachments
        $attachments = $this->wp()
            ->table('wp_posts')
            ->where('post_type', 'attachment')
            ->whereNotNull('guid')
            ->where('guid', '!=', '')
            ->select('ID', 'guid', 'post_title', 'post_excerpt', 'post_content', 'post_author', 'post_date', 'post_mime_type')
            ->orderBy('ID')
            ->get();

        $this->info("Found {$attachments->count()} WordPress media files.");

        // Ensure storage directory exists
        Storage::disk('public')->makeDirectory('media');

        // Preload valid users map
        $validUsers = DB::table('users')->pluck('id')->flip()->toArray();

        // Process attachments in chunks
        $chunks = $attachments->chunk(300);
        $migrated = 0;
        $localized = 0;

        foreach ($chunks as $chunk) {
            $postIds = $chunk->pluck('ID')->toArray();

            // Fetch postmeta for this chunk in a single query
            $postMetas = $this->wp()->table('wp_postmeta')
                ->whereIn('post_id', $postIds)
                ->whereIn('meta_key', ['_wp_attached_file', '_wp_attachment_metadata', '_wp_attachment_image_alt'])
                ->get()
                ->groupBy('post_id');

            $batchUpsert = [];

            foreach ($chunk as $attachment) {
                $postId = (int) $attachment->ID;
                $metaRows = $postMetas->get($postId);

                $attachedFile = null;
                $altText = null;
                $metaDataRaw = null;

                if ($metaRows) {
                    foreach ($metaRows as $row) {
                        if ($row->meta_key === '_wp_attached_file') $attachedFile = $row->meta_value;
                        if ($row->meta_key === '_wp_attachment_image_alt') $altText = $row->meta_value;
                        if ($row->meta_key === '_wp_attachment_metadata') $metaDataRaw = $row->meta_value;
                    }
                }

                $width = null;
                $height = null;
                $fileSize = 0;
                $conversions = null;

                if ($metaDataRaw) {
                    $metaArr = @unserialize($metaDataRaw);
                    if (is_array($metaArr)) {
                        $width = isset($metaArr['width']) ? (int) $metaArr['width'] : null;
                        $height = isset($metaArr['height']) ? (int) $metaArr['height'] : null;
                        if (isset($metaArr['filesize'])) {
                            $fileSize = (int) $metaArr['filesize'];
                        }
                        if (isset($metaArr['sizes']) && is_array($metaArr['sizes'])) {
                            $conversions = [];
                            $baseDir = dirname($attachedFile ?: '');
                            $dirPrefix = ($baseDir !== '' && $baseDir !== '.') ? $baseDir . '/' : '';
                            foreach ($metaArr['sizes'] as $sizeKey => $sizeData) {
                                if (isset($sizeData['file'])) {
                                    $conversions[$sizeKey] = 'media/' . $dirPrefix . $sizeData['file'];
                                }
                            }
                        }
                    }
                }

                $originalFilename = Str::limit($attachment->post_title ?: basename($attachedFile ?: parse_url($attachment->guid, PHP_URL_PATH) ?: 'image.jpg'), 250, '');
                $filename = Str::limit(basename($attachedFile ?: parse_url($attachment->guid, PHP_URL_PATH) ?: 'image.jpg'), 250, '');
                $mimeType = $attachment->post_mime_type ?: null;

                $relStoragePath = 'media/' . ($attachedFile ?: $filename);
                $uploadedBy = isset($validUsers[(int) $attachment->post_author]) ? (int) $attachment->post_author : null;

                $localFileExists = Storage::disk('public')->exists($relStoragePath);

                if ($this->localizeFiles && !$localFileExists) {
                    try {
                        $response = Http::timeout(8)->get($attachment->guid);
                        if ($response->successful()) {
                            Storage::disk('public')->put($relStoragePath, $response->body());
                            $fileSize = strlen($response->body());
                            $localFileExists = true;
                            $localized++;
                        }
                    } catch (\Throwable $e) {
                        // Fall back gracefully
                    }
                }

                if ($localFileExists && $fileSize === 0) {
                    $fileSize = Storage::disk('public')->size($relStoragePath);
                }

                $finalPath = $localFileExists ? $relStoragePath : ($attachedFile ? 'media/' . $attachedFile : $relStoragePath);
                $caption = $attachment->post_excerpt ? Str::limit((string) $attachment->post_excerpt, 250, '') : null;
                $altText = $altText ? Str::limit((string) $altText, 250, '') : null;

                $batchUpsert[] = [
                    'id' => $postId,
                    'disk' => 'public',
                    'path' => $finalPath,
                    'filename' => $filename,
                    'original_filename' => $originalFilename,
                    'mime_type' => $mimeType,
                    'size' => $fileSize,
                    'width' => $width,
                    'height' => $height,
                    'alt_text' => $altText,
                    'caption' => $caption,
                    'credit' => null,
                    'source_url' => $attachment->guid,
                    'conversions' => $conversions ? json_encode($conversions) : null,
                    'uploaded_by' => $uploadedBy,
                    'created_at' => $attachment->post_date,
                    'updated_at' => now(),
                ];
            }

            if (!empty($batchUpsert)) {
                DB::table('media')->upsert(
                    $batchUpsert,
                    ['id'],
                    ['disk', 'path', 'filename', 'original_filename', 'mime_type', 'size', 'width', 'height', 'alt_text', 'caption', 'credit', 'source_url', 'conversions', 'uploaded_by', 'updated_at']
                );
                $migrated += count($batchUpsert);
            }
        }

        $this->info("  Done. Media records migrated/upserted: {$migrated} (Physically localized: {$localized})");
    }
}
