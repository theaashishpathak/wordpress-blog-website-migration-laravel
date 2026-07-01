<?php

declare(strict_types=1);

namespace App\Notifications\Imports;

use App\Models\ImportSource;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the user who created an RSS source when an import run
 * completes successfully AND produced at least one new post. Silent
 * fetches (zero new items) don't notify — that'd be too noisy for
 * sources running every 30 minutes.
 */
class RssImportCompleted extends Notification
{
    use Queueable;

    public function __construct(
        public ImportSource $source,
        public int $created,
        public int $skipped,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'rss.import.completed',
            'source_id' => $this->source->id,
            'source_name' => $this->source->name,
            'created' => $this->created,
            'skipped' => $this->skipped,
            'icon' => 'rss',
            'color' => 'sky',
            'title' => 'RSS import completed',
            'message' => "{$this->source->name} imported {$this->created} new post(s), skipped {$this->skipped} duplicate(s).",
            'url' => route('admin.imports.sources'),
        ];
    }
}
