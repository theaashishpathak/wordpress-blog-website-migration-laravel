<?php

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;

/**
 * MediaMigrationService
 *
 * WHAT IT DOES:
 * Reads WordPress attachment records (featured images) and inserts them into
 * NewsPilot's `media` table, storing the public URL as the path.
 *
 * WHY MEDIA BEFORE POSTS:
 * NewsPilot's posts table has `featured_image_id` as a foreign key pointing
 * to the media table. If we insert posts first and reference a media ID that
 * doesn't exist yet, MySQL rejects it. Media must exist before posts.
 *
 * HOW WORDPRESS STORES IMAGES:
 * WordPress stores uploaded files as posts with post_type = 'attachment'.
 * The actual file URL is stored in wp_postmeta with meta_key = '_wp_attachment_metadata'
 * (a serialized PHP array) or in the simpler `guid` column of wp_posts which
 * holds the full public URL to the original file.
 *
 * WHAT WE STORE:
 * Rather than copying physical image files (which would require SSH/FTP access
 * to your WordPress hosting), we store the FULL URL of the WordPress image.
 * This means images continue to be served from WordPress's server while you
 * validate the migration. You can bulk-download and re-host them in Phase 3.
 *
 * FIELD MAPPING:
 * wp_posts.ID           → media.id            (keep same ID so post FK mapping works)
 * wp_posts.guid         → media.path          (full URL to the file)
 * wp_posts.guid         → media.source_url    (keep original WordPress URL for reference)
 * wp_posts.post_title   → media.original_filename
 * wp_posts.post_author  → media.uploaded_by   (FK to users — already migrated)
 * wp_postmeta (alt)     → media.alt_text
 */
class MediaMigrationService extends BaseMigrationService

{
    public function run(): void
    {
        // Fetch all WordPress attachment records
        // WHY post_type = 'attachment': In WordPress, every uploaded file is stored
        // as a special post with type 'attachment'. This is how WordPress tracks media.
        $attachments = $this->wp()
            ->table('wp_posts')
            ->where('post_type', 'attachment')
            ->whereNotNull('guid')
            ->where('guid', '!=', '')
            ->select('ID', 'guid', 'post_title', 'post_author', 'post_date', 'post_mime_type')
            ->get();

        $this->info("Found {$attachments->count()} WordPress media files.");

        $migrated = 0;
        $skipped = 0;

        foreach ($attachments as $attachment) {

            // Skip if already exists (safe re-run)
            $exists = DB::table('media')->where('id', $attachment->ID)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            // Get alt text from wp_postmeta
            // WHY: WordPress stores image alt text in postmeta with key '_wp_attachment_image_alt'
            // This is the accessibility text shown when the image can't load.
            $altText = $this->wp()
                ->table('wp_postmeta')
                ->where('post_id', $attachment->ID)
                ->where('meta_key', '_wp_attachment_image_alt')
                ->value('meta_value');

            // Extract just the filename from the full URL
            // Example: https://example.com/wp-content/uploads/2024/01/photo.jpg → photo.jpg
            $filename = basename(parse_url($attachment->guid, PHP_URL_PATH) ?? '');
            $mimeType = $attachment->post_mime_type ?: null;

            // Check if the uploading user exists in NewsPilot
            // WHY: The uploaded_by FK must point to a real user. If the author
            // doesn't exist for some reason, we set it to null (nullable FK).
            $uploaderExists = DB::table('users')->where('id', $attachment->post_author)->exists();

            DB::table('media')->insert([
                'id' => $attachment->ID,
                'disk' => 'public',
                // WHY STORE FULL URL AS PATH:
                // We're not copying files yet — just recording where they live.
                // The frontend can render <img src="{{ $media->path }}"> directly
                // and images load from WordPress's server. Clean them up later.
                'path' => $attachment->guid,
                'filename' => $filename,
                'original_filename' => $attachment->post_title ?: $filename,
                'mime_type' => $mimeType,
                'size' => 0,   // Unknown without reading the actual file
                'width' => null,
                'height' => null,
                'alt_text' => $altText ?: null,
                'caption' => null,
                'credit' => null,
                'source_url' => $attachment->guid,
                'conversions' => null,
                'uploaded_by' => $uploaderExists ? $attachment->post_author : null,
                'created_at' => $attachment->post_date,
                'updated_at' => now(),
            ]);

            $migrated++;
        }

        $this->info("  Done. Migrated: {$migrated}, Skipped: {$skipped}");
    }
}
