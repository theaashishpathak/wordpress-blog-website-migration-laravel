<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Post\PublishPostAction;
use App\Models\Post;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sweep posts in `scheduled` status whose scheduled_at has passed and
 * publish them via PublishPostAction.
 *
 * Wired into the Laravel scheduler in routes/console.php to run every
 * minute. With Laravel 13's scheduler this is the equivalent of a cron
 * job that auto-publishes news articles at their planned time.
 *
 * Designed to be safe under concurrent runs: PublishPostAction is
 * idempotent on already-published posts, and we wrap each call in its
 * own try/catch so one bad post never aborts the whole batch.
 */
class PublishScheduledPosts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'posts:publish-scheduled
                            {--dry-run : Show which posts would be published without changing anything}
                            {--limit=200 : Maximum posts to publish per run}';

    /**
     * @var string
     */
    protected $description = 'Publish scheduled posts whose scheduled_at has arrived.';

    public function handle(PublishPostAction $publishPost): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $posts = Post::query()
            ->scheduled()
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No scheduled posts due for publication.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d scheduled post%s due for publication.',
            $dryRun ? '[DRY RUN]' : 'Publishing',
            $posts->count(),
            $posts->count() === 1 ? '' : 's',
        ));

        $succeeded = 0;
        $failed = 0;

        foreach ($posts as $post) {
            if ($dryRun) {
                $this->line(sprintf(
                    '  • Post #%d "%s" — would publish (scheduled_at: %s)',
                    $post->id,
                    (string) ($post->translate('title') ?? '[no title]'),
                    $post->scheduled_at?->format('Y-m-d H:i:s') ?? 'null',
                ));

                continue;
            }

            try {
                $publishPost->handle($post, cascadeTranslations: true);
                $succeeded++;
                $this->line("  • Post #{$post->id} published.");
            } catch (Throwable $exception) {
                $failed++;
                $this->error("  • Post #{$post->id} failed: ".$exception->getMessage());
                report($exception);
            }
        }

        if (! $dryRun) {
            $this->info("Published: {$succeeded}, Failed: {$failed}.");
        }

        return self::SUCCESS;
    }
}
