<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Import\ImportFeedAction;
use App\Models\ImportSource;
use Illuminate\Console\Command;
use Throwable;

/**
 * Walk every due-for-fetch RSS source and run ImportFeedAction.
 *
 * Designed to be cron-scheduled every 5 minutes — the per-source
 * `fetch_interval_minutes` check inside dueForFetch() prevents any
 * single feed from being hammered too often.
 *
 * Use `--source=` to fetch a specific source manually (admin "Fetch
 * now" button), bypassing the interval gate.
 */
class ImportRssFeeds extends Command
{
    protected $signature = 'rss:import {--source= : Run only this source id, ignoring the interval}';

    protected $description = 'Fetch and import items from active RSS feed sources.';

    public function handle(ImportFeedAction $importer): int
    {
        $query = $this->option('source')
            ? ImportSource::query()->whereKey((int) $this->option('source'))
            : ImportSource::query()->dueForFetch();

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->info('No sources due for fetch.');

            return self::SUCCESS;
        }

        $totalCreated = 0;
        $totalSkipped = 0;
        $errors = 0;

        foreach ($sources as $source) {
            $this->line("→ {$source->name} ({$source->feed_url})");

            try {
                $result = $importer->handle($source);

                $totalCreated += $result['created'];
                $totalSkipped += $result['skipped'];

                $this->info("  ✓ fetched {$result['fetched']}, created {$result['created']}, skipped {$result['skipped']}");
            } catch (Throwable $exception) {
                $errors++;
                report($exception);
                $this->error('  ✗ '.$exception->getMessage());
            }
        }

        $this->newLine();
        $this->info("Done: {$totalCreated} created, {$totalSkipped} skipped, {$errors} errored.");

        return self::SUCCESS;
    }
}
