<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Remove a media file from disk and delete its DB row.
 *
 * Any model referencing this media via FK (e.g., categories.image_id)
 * will be nulled automatically by the FK's nullOnDelete clause.
 *
 * Also removes every registered conversion file so we don't leak orphan
 * variants on the disk.
 */
class DeleteMediaAction
{
    public function handle(Media $media): void
    {
        DB::transaction(function () use ($media): void {
            $disk = Storage::disk((string) $media->disk);

            if ($disk->exists($media->path)) {
                $disk->delete($media->path);
            }

            foreach (($media->conversions ?? []) as $conversionPath) {
                if (is_string($conversionPath) && $conversionPath !== '' && $disk->exists($conversionPath)) {
                    $disk->delete($conversionPath);
                }
            }

            $media->delete();
        });
    }
}
