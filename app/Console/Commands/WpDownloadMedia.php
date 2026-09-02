<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WpDownloadMedia extends Command
{
    protected $signature = 'wp:download-media {--limit=0 : Limit number of downloads} {--concurrency=15 : Parallel download requests}';
    protected $description = 'Physically download all WordPress media files from source into local storage';

    public function handle(): int
    {
        $this->info('Starting WordPress media download pipeline...');

        $mediaList = DB::table('media')
            ->whereNotNull('source_url')
            ->where('source_url', '!=', '')
            ->select('id', 'path', 'source_url', 'disk')
            ->orderBy('id')
            ->get();

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $mediaList = $mediaList->take($limit);
        }

        $concurrency = (int) $this->option('concurrency') ?: 15;

        $total = $mediaList->count();
        $this->info("Found {$total} media records in database. Concurrency: {$concurrency}");

        $downloaded = 0;
        $skipped = 0;
        $failed = 0;

        // Group into concurrent batches
        $chunks = $mediaList->chunk($concurrency);
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($chunks as $chunk) {
            $toDownload = [];

            foreach ($chunk as $m) {
                $relPath = $m->path ?: 'media/' . basename(parse_url($m->source_url, PHP_URL_PATH) ?: 'image.jpg');

                // Ensure parent directory exists
                $dir = dirname($relPath);
                if (!Storage::disk('public')->exists($dir)) {
                    Storage::disk('public')->makeDirectory($dir);
                }

                if (Storage::disk('public')->exists($relPath) && Storage::disk('public')->size($relPath) > 0) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $toDownload[] = [
                    'id' => $m->id,
                    'url' => $m->source_url,
                    'path' => $relPath,
                ];
            }

            if (empty($toDownload)) {
                continue;
            }

            $responses = Http::pool(function (Pool $pool) use ($toDownload) {
                $reqs = [];
                foreach ($toDownload as $item) {
                    $reqs[$item['id']] = $pool->as((string) $item['id'])
                        ->timeout(12)
                        ->retry(2, 200)
                        ->get($item['url']);
                }
                return $reqs;
            });

            foreach ($toDownload as $item) {
                $id = (string) $item['id'];
                $res = $responses[$id] ?? null;

                if ($res && $res->successful() && strlen($res->body()) > 0) {
                    Storage::disk('public')->put($item['path'], $res->body());
                    DB::table('media')->where('id', $item['id'])->update([
                        'path' => $item['path'],
                        'size' => strlen($res->body()),
                    ]);
                    $downloaded++;
                } else {
                    $failed++;
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->info('');
        $this->info("Completed. Downloaded: {$downloaded}, Skipped (already local): {$skipped}, Failed: {$failed}");

        return 0;
    }
}
