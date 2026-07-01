<?php

declare(strict_types=1);

namespace App\Notifications\Imports;

use App\Models\ImportSource;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the source's creator when an import run errored — either an
 * HTTP failure or an XML-parse failure. Dispatched from
 * ImportFeedAction::markError().
 */
class RssImportFailed extends Notification
{
    use Queueable;

    public function __construct(
        public ImportSource $source,
        public string $reason,
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
            'type' => 'rss.import.failed',
            'source_id' => $this->source->id,
            'source_name' => $this->source->name,
            'reason' => $this->reason,
            'icon' => 'triangle-alert',
            'color' => 'rose',
            'title' => 'RSS import failed',
            'message' => "{$this->source->name} couldn't be fetched. {$this->reason}",
            'url' => route('admin.imports.sources'),
        ];
    }
}
