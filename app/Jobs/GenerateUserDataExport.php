<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DataExportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Assemble a per-user data archive (GDPR right-to-portability).
 *
 * Output: a ZIP at storage/app/exports/{uuid}.zip on the `local` (private)
 * disk. The ZIP contains JSON files for each module + a README.txt summary.
 * Status moves pending → processing → ready (or failed). `expires_at` is
 * set to +7 days so the link auto-expires.
 *
 * Queueable, but runs synchronously on the `sync` default driver. Switch
 * QUEUE_CONNECTION to redis/database for off-thread processing.
 */
class GenerateUserDataExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 2;

    public function __construct(public DataExportRequest $request) {}

    public function handle(): void
    {
        $request = $this->request->fresh();
        if ($request === null) {
            return;
        }

        $request->update(['status' => DataExportRequest::STATUS_PROCESSING]);

        try {
            $user = $request->user;
            if ($user === null) {
                $request->update([
                    'status' => DataExportRequest::STATUS_FAILED,
                    'error' => 'User no longer exists.',
                ]);

                return;
            }

            $payload = $this->collectUserData($user);

            $disk = Storage::disk('local');
            $filename = 'exports/'.\Illuminate\Support\Str::uuid().'.zip';
            $absolute = $disk->path($filename);
            $disk->makeDirectory('exports');

            $zip = new ZipArchive;
            if ($zip->open($absolute, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not open zip for writing.');
            }

            foreach ($payload as $section => $data) {
                $zip->addFromString(
                    $section.'.json',
                    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                );
            }

            $zip->addFromString('README.txt', $this->buildReadme($user, $payload));
            $zip->close();

            $request->update([
                'status' => DataExportRequest::STATUS_READY,
                'file_path' => $filename,
                'file_size_bytes' => $disk->size($filename),
                'completed_at' => now(),
                'expires_at' => now()->addDays(7),
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            $request->update([
                'status' => DataExportRequest::STATUS_FAILED,
                'error' => mb_substr($e->getMessage(), 0, 1000),
            ]);
            report($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function collectUserData(\App\Models\User $user): array
    {
        return [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'bio' => $user->bio ?? null,
                'social_links' => $user->social_links ?? [],
                'portal_type' => $user->portal_type,
                'locale' => $user->locale,
                'timezone' => $user->timezone,
                'joined_at' => $user->created_at?->toIso8601String(),
                'last_updated_at' => $user->updated_at?->toIso8601String(),
            ],
            'bookmarks' => $user->bookmarks()->with('post:id')->get(['id', 'post_id', 'created_at'])->toArray(),
            'reading_list_items' => $user->readingListItems()->get(['id', 'post_id', 'added_at', 'dismissed_at'])->toArray(),
            'reading_history' => $user->readingHistory()->get(['id', 'post_id', 'first_read_at', 'last_read_at', 'read_count', 'read_duration_seconds', 'completed'])->toArray(),
            'reactions' => $user->reactions()->get(['id', 'post_id', 'type', 'created_at'])->toArray(),
            'highlights' => $user->highlights()->get(['id', 'post_id', 'selected_text', 'note', 'created_at'])->toArray(),
            'comments' => \App\Models\Comment::query()->where('user_id', $user->id)->get(['id', 'post_id', 'parent_id', 'body', 'status', 'created_at'])->toArray(),
            'topic_follows' => $user->topicFollows()->get(['id', 'followable_type', 'followable_id', 'notify_on_post', 'created_at'])->toArray(),
            'author_follows' => $user->authorFollows()->get(['id', 'author_id', 'notify_on_publish', 'created_at'])->toArray(),
            'user_follows_outgoing' => $user->following()->get(['id', 'followed_id', 'created_at'])->toArray(),
            'user_follows_incoming' => $user->followers()->get(['id', 'follower_id', 'created_at'])->toArray(),
            'notification_preferences' => $user->notificationPreferences()->get(['key', 'channel', 'enabled'])->toArray(),
            'user_settings' => $user->userSettings()->get(['key', 'value'])->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function buildReadme(\App\Models\User $user, array $payload): string
    {
        $lines = [
            'NewsPilot AI — Personal Data Export',
            '===================================',
            '',
            'Generated: '.now()->toIso8601String(),
            'For:       '.$user->email,
            'Expires:   '.now()->addDays(7)->toIso8601String().' (download link)',
            '',
            'Contents:',
            '---------',
        ];

        foreach ($payload as $section => $rows) {
            $count = is_countable($rows) ? count($rows) : 1;
            $lines[] = '  '.str_pad($section.'.json', 32).' — '.$count.' '.\Illuminate\Support\Str::plural('row', $count);
        }

        $lines[] = '';
        $lines[] = 'Each file is JSON. Open with any text editor or import into your tool of choice.';
        $lines[] = 'Questions about your data? Reply to the email this archive was attached to.';

        return implode("\n", $lines);
    }
}
