<?php

declare(strict_types=1);

namespace App\Actions\Import;

use App\Actions\Post\CreatePostAction;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\ImportedItem;
use App\Models\ImportSource;
use App\Models\User;
use App\Notifications\Imports\RssImportCompleted;
use App\Notifications\Imports\RssImportFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fetch an RSS source's feed XML and import any new <item> rows as
 * Post + PostTranslation records.
 *
 * Flow:
 *   1. HTTP GET the feed_url (timeout 15s, retries skipped since the
 *      scheduler will pick the source back up on the next interval).
 *   2. Parse the XML defensively — RSS feeds are notoriously
 *      inconsistent. SimpleXMLElement gives us the widest compatibility.
 *   3. For each item, derive a stable GUID (prefer <guid>, fall back to
 *      a hash of <link>+<title>) and skip if it already exists in
 *      imported_items for this source.
 *   4. Hand-build a Post payload and call CreatePostAction. The Post's
 *      `source_name` + `source_url` carry attribution back to the feed.
 *   5. Record an imported_items row to dedupe future fetches.
 *
 * Any thrown exception is caught at the per-source boundary by the
 * console command, so a single broken feed doesn't poison the batch.
 */
class ImportFeedAction
{
    public function __construct(private CreatePostAction $createPost) {}

    /**
     * @return array{fetched:int, created:int, skipped:int}
     */
    public function handle(ImportSource $source): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'NewsPilot AI Bot/1.0'])
                ->get($source->feed_url);
        } catch (Throwable $e) {
            $this->markError($source, 'HTTP exception: '.$e->getMessage());

            return ['fetched' => 0, 'created' => 0, 'skipped' => 0];
        }

        if (! $response->successful()) {
            $this->markError($source, "HTTP {$response->status()} from {$source->feed_url}");

            return ['fetched' => 0, 'created' => 0, 'skipped' => 0];
        }

        $body = $response->body();

        $xml = $this->parseFeed($body);

        if ($xml === null) {
            $this->markError($source, 'Feed body is not valid XML. First 200 bytes: '.mb_substr($body, 0, 200));

            return ['fetched' => 0, 'created' => 0, 'skipped' => 0];
        }

        $items = $this->extractItems($xml);
        $created = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $guid = $this->resolveGuid($item);

            if ($guid === '') {
                $skipped++;
                continue;
            }

            $exists = ImportedItem::query()
                ->where('source_id', $source->id)
                ->where('guid', $guid)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            try {
                $post = $this->createPostFromItem($source, $item);

                ImportedItem::query()->create([
                    'source_id' => $source->id,
                    'guid' => mb_substr($guid, 0, 500),
                    'item_url' => mb_substr((string) ($item['link'] ?? ''), 0, 1000),
                    'title' => mb_substr((string) ($item['title'] ?? ''), 0, 500),
                    'post_id' => $post->id,
                    'imported_at' => now(),
                ]);

                $created++;
            } catch (Throwable $e) {
                report($e);
                $skipped++;
            }
        }

        $source->forceFill([
            'status' => ImportSource::STATUS_ACTIVE,
            'last_fetched_at' => now(),
            'last_error' => null,
            'item_count' => $source->item_count + $created,
        ])->save();

        // Notify the source owner ONLY when new posts were actually
        // imported — silent fetches (every 30 min, zero new items) would
        // otherwise spam the notification list.
        if ($created > 0 && $source->created_by) {
            $owner = User::query()->find($source->created_by);
            if ($owner) {
                Notification::send($owner, new RssImportCompleted($source, $created, $skipped));
            }
        }

        return [
            'fetched' => count($items),
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    private function markError(ImportSource $source, string $message): void
    {
        $source->forceFill([
            'status' => ImportSource::STATUS_ERROR,
            'last_error' => mb_substr($message, 0, 1000),
            'last_fetched_at' => now(),
        ])->save();

        if ($source->created_by) {
            try {
                $owner = User::query()->find($source->created_by);
                if ($owner) {
                    Notification::send($owner, new RssImportFailed($source, $message));
                }
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    private function parseFeed(string $xml): ?\SimpleXMLElement
    {
        if (trim($xml) === '') {
            return null;
        }

        // Suppress libxml warnings — feeds in the wild violate the spec
        // routinely; we just want a best-effort parse.
        $prev = libxml_use_internal_errors(true);

        try {
            $parsed = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NOCDATA);
        } catch (Throwable) {
            $parsed = false;
        }

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $parsed === false ? null : $parsed;
    }

    /**
     * Extract item rows as plain associative arrays. Handles both RSS 2.0
     * (`<rss><channel><item>`) and Atom (`<feed><entry>`).
     *
     * @return list<array<string, mixed>>
     */
    private function extractItems(\SimpleXMLElement $xml): array
    {
        $items = [];

        // RSS 2.0
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $items[] = $this->normaliseRssItem($item);
            }

            return $items;
        }

        // Atom 1.0
        if (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $items[] = $this->normaliseAtomEntry($entry);
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function normaliseRssItem(\SimpleXMLElement $item): array
    {
        return [
            'guid' => (string) ($item->guid ?? ''),
            'title' => trim((string) ($item->title ?? '')),
            'link' => trim((string) ($item->link ?? '')),
            'description' => trim((string) ($item->description ?? '')),
            'pub_date' => (string) ($item->pubDate ?? ''),
            'creator' => trim((string) ($item->children('dc', true)->creator ?? '')),
            // Some publishers put the full body under content:encoded.
            'content' => trim((string) ($item->children('content', true)->encoded ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normaliseAtomEntry(\SimpleXMLElement $entry): array
    {
        $link = '';
        if (isset($entry->link)) {
            foreach ($entry->link as $l) {
                if ((string) $l['rel'] === '' || (string) $l['rel'] === 'alternate') {
                    $link = (string) $l['href'];
                    break;
                }
            }
        }

        return [
            'guid' => (string) ($entry->id ?? ''),
            'title' => trim((string) ($entry->title ?? '')),
            'link' => $link,
            'description' => trim((string) ($entry->summary ?? '')),
            'pub_date' => (string) ($entry->published ?? $entry->updated ?? ''),
            'creator' => trim((string) ($entry->author->name ?? '')),
            'content' => trim((string) ($entry->content ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveGuid(array $item): string
    {
        $explicit = trim((string) ($item['guid'] ?? ''));

        if ($explicit !== '') {
            return $explicit;
        }

        $link = trim((string) ($item['link'] ?? ''));
        $title = trim((string) ($item['title'] ?? ''));

        if ($link === '' && $title === '') {
            return '';
        }

        return 'sha1:'.sha1($link.'|'.$title);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function createPostFromItem(ImportSource $source, array $item): \App\Models\Post
    {
        $title = (string) ($item['title'] ?? 'Untitled');
        $body = (string) ($item['content'] !== '' ? $item['content'] : ($item['description'] ?? ''));
        $excerpt = $this->extractExcerpt($body, $item['description'] ?? '');

        $payload = [
            'type' => $source->default_post_type === 'news' ? PostType::News->value : PostType::Post->value,
            'author_id' => $this->resolveAuthorId($source),
            'category_id' => $source->category_id,
            'default_language_id' => $source->default_language_id,
            'status' => $source->auto_publish ? PostStatus::Published : PostStatus::Draft,
            'visibility' => \App\Models\Post::VISIBILITY_PUBLIC,
            'source_name' => $source->name,
            'source_url' => (string) ($item['link'] ?? ''),
            'translations' => [
                [
                    'language_id' => $source->default_language_id,
                    'title' => $title,
                    'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
                    'excerpt' => $excerpt !== '' ? $excerpt : null,
                    'content' => $body !== '' ? $body : null,
                ],
            ],
        ];

        if ($source->auto_publish) {
            $payload['published_at'] = now();
        }

        return $this->createPost->handle($payload);
    }

    /**
     * Resolve the author user_id used as the Post's `author_id`. Prefer the
     * source's `created_by` (the staff member who registered the feed),
     * then any authenticated user, then the first existing user, and
     * finally fall back to a synthesised "RSS Bot" system user so that
     * imports work on a fresh install (or in tests) without manual seeding.
     */
    private function resolveAuthorId(ImportSource $source): int
    {
        if ($source->created_by !== null && \App\Models\User::query()->whereKey($source->created_by)->exists()) {
            return (int) $source->created_by;
        }

        $authId = auth()->id();
        if ($authId !== null) {
            return (int) $authId;
        }

        $firstId = \App\Models\User::query()->value('id');
        if ($firstId !== null) {
            return (int) $firstId;
        }

        $bot = \App\Models\User::query()->firstOrCreate(
            ['email' => 'rss-bot@system.local'],
            [
                'name' => 'RSS Bot',
                'password' => Str::random(40),
                'email_verified_at' => now(),
            ],
        );

        return (int) $bot->id;
    }

    private function extractExcerpt(string $body, string $description): string
    {
        $source = $description !== '' ? $description : $body;
        $plain = trim(strip_tags($source));

        if (mb_strlen($plain) <= 250) {
            return $plain;
        }

        return Str::limit($plain, 247);
    }
}
