<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AuthorFollow;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Highlight;
use App\Models\NotificationPreference;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\ReadingHistory;
use App\Models\ReadingListItem;
use App\Models\Tag;
use App\Models\TopicFollow;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Populate visitor demo accounts with engagement signals so the Reader
 * Portal feels alive on a fresh install.
 *
 * Three archetypes — picked by email — keep both empty-state and
 * populated-state screenshots covered without manual tweaking:
 *
 *   power user   visitor@demo.com    rich data (×1.5 scale)
 *   mid-tier     commuter@demo.com   moderate data (×1.0 scale)
 *   fresh        newbie@demo.com     no data (skipped entirely)
 *   anonymous    everyone else       light-moderate data (×0.7 scale)
 *
 * Idempotent — uses firstOrCreate / updateOrCreate so re-seeding the
 * same DB does not stack duplicates.
 */
class VisitorPortalDemoSeeder extends Seeder
{
    /** Power-user accounts get richer engagement counts. */
    private const ARCHETYPE_SCALE = [
        'visitor@demo.com'  => 2.0,
        'commuter@demo.com' => 1.0,
    ];

    /** Accounts listed here get no engagement data — fresh-signup feel. */
    private const FRESH_EMAILS = ['newbie@demo.com'];

    public function run(): void
    {
        $visitors = User::query()->where('portal_type', 'visitor')->get();
        $posts = Post::query()
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->whereNotNull('published_at')
            ->limit(60)
            ->get();

        if ($visitors->isEmpty() || $posts->isEmpty()) {
            $this->command?->info('VisitorPortalDemoSeeder: no visitors or posts found — skipping.');

            return;
        }

        $authors = User::query()->where('portal_type', 'author')->limit(10)->get();
        $tags = Tag::query()->limit(20)->get();
        $categories = Category::query()->limit(8)->get();

        $populated = 0;
        $fresh = 0;

        foreach ($visitors as $visitor) {
            if (in_array($visitor->email, self::FRESH_EMAILS, true)) {
                // Fresh visitor — leave every list empty so the empty
                // states render. Still seed default notification prefs
                // so the prefs matrix doesn't 500.
                $fresh++;
                continue;
            }

            $scale = self::ARCHETYPE_SCALE[$visitor->email] ?? 0.7;

            $this->seedLibrary($visitor, $posts, $scale);
            $this->seedReactions($visitor, $posts, $scale);
            $this->seedHighlights($visitor, $posts, $scale);
            $this->seedComments($visitor, $posts, $scale);
            $this->seedFollows($visitor, $authors, $tags, $categories, $visitors, $scale);
            $this->seedPreferences($visitor);
            $populated++;
        }

        $this->seedNotifications($visitors);

        $this->command?->info(sprintf(
            'VisitorPortalDemoSeeder: %d visitors populated, %d kept fresh.',
            $populated, $fresh,
        ));
    }

    private function seedLibrary($visitor, $posts, float $scale): void
    {
        $bookmarkCount = (int) round(8 * $scale);
        foreach ($posts->random(min($bookmarkCount, $posts->count())) as $post) {
            Bookmark::query()->firstOrCreate([
                'user_id' => $visitor->id,
                'post_id' => $post->id,
            ], [
                'created_at' => Carbon::now()->subDays(rand(1, 25)),
            ]);
        }

        $listCount = (int) round(5 * $scale);
        foreach ($posts->random(min($listCount, $posts->count())) as $post) {
            ReadingListItem::query()->firstOrCreate([
                'user_id' => $visitor->id,
                'post_id' => $post->id,
            ], [
                'added_at' => Carbon::now()->subDays(rand(1, 15)),
                'dismissed_at' => rand(1, 4) === 1 ? Carbon::now()->subDays(rand(0, 5)) : null,
            ]);
        }

        // Reading history — bigger spread (20→24 base) + at least one
        // entry within the past 24h so the "Today" date bucket has a
        // row on every populated visitor.
        $historyCount = (int) round(24 * $scale);
        $historyPicks = $posts->random(min($historyCount, $posts->count()));
        foreach ($historyPicks as $i => $post) {
            $firstRead = $i === 0
                ? Carbon::now()->subHours(rand(1, 18))
                : Carbon::now()->subDays(rand(1, 50));
            ReadingHistory::query()->firstOrCreate([
                'user_id' => $visitor->id,
                'post_id' => $post->id,
            ], [
                'first_read_at' => $firstRead,
                'last_read_at' => (clone $firstRead)->addHours(rand(0, 24 * 5)),
                'read_count' => rand(1, 4),
                'read_duration_seconds' => rand(45, 600),
                'completed' => rand(1, 3) !== 1,
            ]);
        }
    }

    private function seedReactions($visitor, $posts, float $scale): void
    {
        $likeCount = (int) round(12 * $scale);
        foreach ($posts->random(min($likeCount, $posts->count())) as $post) {
            PostReaction::query()->firstOrCreate(
                ['user_id' => $visitor->id, 'post_id' => $post->id],
                ['type' => PostReaction::TYPE_LIKE]
            );
        }

        // Dislikes are rarer — match real engagement patterns.
        $dislikeCount = (int) round(3 * $scale);
        $alreadyReacted = PostReaction::query()
            ->where('user_id', $visitor->id)
            ->pluck('post_id')
            ->all();
        $candidates = $posts->whereNotIn('id', $alreadyReacted);
        foreach ($candidates->take($dislikeCount) as $post) {
            PostReaction::query()->firstOrCreate(
                ['user_id' => $visitor->id, 'post_id' => $post->id],
                ['type' => PostReaction::TYPE_DISLIKE]
            );
        }
    }

    private function seedHighlights($visitor, $posts, float $scale): void
    {
        $count = (int) round(6 * $scale);

        // Believable pull-quotes — kept inline so the seeder remains
        // idempotent via firstOrCreate. The HighlightFactory uses a
        // similar bank for ad-hoc generation in tests.
        $snippets = [
            'The new flagship model adds real-time vision, longer context windows, and tool-use that finally rivals human reasoning.',
            'A solid twelve-page report on what this means for working journalists in newsrooms still finding their footing.',
            'Bangladesh — a country of 170 million — leapfrogged direct to digital, skipping the legacy systems entirely.',
            'AI-assisted reporting still demands editorial judgement at every step. The tools are leverage, not replacement.',
            'Climate policy moves at the speed of public attention, and attention is finite.',
            'These regulatory shifts will compound through 2026 and beyond — the second-order effects matter more than the headlines.',
            'The most interesting line in the filing is buried halfway through page nineteen.',
            'You can\'t legislate trust into existence, but you can build the infrastructure that earns it back over time.',
        ];

        $notes = [
            'Worth revisiting later.',
            'Quote for the weekly digest.',
            'Counter-evidence to last month\'s piece.',
        ];

        foreach ($posts->random(min($count, $posts->count())) as $post) {
            $text = $snippets[array_rand($snippets)];
            Highlight::query()->firstOrCreate([
                'user_id' => $visitor->id,
                'post_id' => $post->id,
                'context_hash' => sha1($text),
            ], [
                'selected_text' => $text,
                'note' => rand(1, 3) === 1 ? $notes[array_rand($notes)] : null,
                'start_offset' => rand(0, 500),
                'end_offset' => rand(500, 1200),
            ]);
        }
    }

    private function seedComments($visitor, $posts, float $scale): void
    {
        // Idempotency — if this visitor already has comments, leave
        // them alone. Re-seeding would otherwise stack duplicates.
        if (Comment::query()->where('user_id', $visitor->id)->exists()) {
            return;
        }

        $count = (int) round(4 * $scale);

        $bodies = [
            'Really thoughtful piece — the analysis on the second half landed for me.',
            'I would push back on the framing here, but the data is solid.',
            'Best thing I\'ve read on this topic all week. Sharing internally.',
            'Anyone else struggling to reconcile this with last week\'s coverage?',
            'Bookmarked. Too much to absorb in one read.',
            'The chart in section three is doing a lot of heavy lifting. Source?',
            'Worth pairing this with the Bloomberg piece earlier this week.',
            'Strong reporting. The closing reframe is going to stick with me.',
        ];

        $commentablePosts = $posts->where('allow_comments', true);
        if ($commentablePosts->isEmpty()) {
            return;
        }

        foreach ($commentablePosts->random(min($count, $commentablePosts->count())) as $post) {
            // ~80% approved, ~15% pending, ~5% spam — same shape as
            // the public-side comment seed.
            $status = match (true) {
                rand(1, 100) <= 5 => Comment::STATUS_SPAM,
                rand(1, 100) <= 20 => Comment::STATUS_PENDING,
                default => Comment::STATUS_APPROVED,
            };

            Comment::query()->create([
                'post_id' => $post->id,
                'user_id' => $visitor->id,
                'body' => $bodies[array_rand($bodies)],
                'status' => $status,
                'approved_at' => $status === Comment::STATUS_APPROVED ? Carbon::now()->subDays(rand(0, 20)) : null,
                'created_at' => Carbon::now()->subDays(rand(0, 40)),
            ]);
        }
    }

    private function seedFollows($visitor, $authors, $tags, $categories, $allVisitors, float $scale): void
    {
        // Topic follows — mix of tags + categories.
        foreach ($tags->random(min((int) round(6 * $scale), $tags->count())) as $tag) {
            TopicFollow::query()->firstOrCreate([
                'user_id' => $visitor->id,
                'followable_type' => Tag::class,
                'followable_id' => $tag->id,
            ], ['notify_on_post' => rand(1, 2) === 1]);
        }
        foreach ($categories->random(min((int) round(3 * $scale), $categories->count())) as $category) {
            TopicFollow::query()->firstOrCreate([
                'user_id' => $visitor->id,
                'followable_type' => Category::class,
                'followable_id' => $category->id,
            ], ['notify_on_post' => true]);
        }

        // Author follows.
        if ($authors->isNotEmpty()) {
            foreach ($authors->random(min((int) round(4 * $scale), $authors->count())) as $author) {
                AuthorFollow::query()->firstOrCreate([
                    'follower_id' => $visitor->id,
                    'author_id' => $author->id,
                ], ['notify_on_publish' => true]);
            }
        }

        // User follows — pick a couple of other visitors at random.
        $otherVisitors = $allVisitors->where('id', '!=', $visitor->id);
        if ($otherVisitors->isNotEmpty()) {
            foreach ($otherVisitors->random(min((int) round(3 * $scale), $otherVisitors->count())) as $other) {
                UserFollow::query()->firstOrCreate([
                    'follower_id' => $visitor->id,
                    'followed_id' => $other->id,
                ]);
            }
        }
    }

    private function seedPreferences($visitor): void
    {
        // The main demo visitor opts in to email for a couple of events
        // so the email column in the prefs matrix isn't all "off" on a
        // fresh install. Other visitors stick with the catalog defaults.
        if ($visitor->email !== 'visitor@demo.com') {
            return;
        }

        NotificationPreference::setValue($visitor->id, 'comment_reply', 'email', true);
        NotificationPreference::setValue($visitor->id, 'weekly_digest', 'email', true);
    }

    /**
     * Drop a handful of synthetic in-app notifications so the bell
     * shows a fresh badge and the notifications page has content
     * to render on a first login.
     */
    private function seedNotifications($visitors): void
    {
        $samples = [
            [
                'type' => 'comment_reply',
                'title' => 'Marcus Hale replied to your comment',
                'message' => '"Great point on the long-term cost projection — agreed."',
                'icon' => 'message-square-reply',
            ],
            [
                'type' => 'author_published',
                'title' => 'A writer you follow just published',
                'message' => '"What\'s next for the green industrial strategy"',
                'icon' => 'pen-tool',
            ],
            [
                'type' => 'new_follower',
                'title' => 'Nora Kowalski started following you',
                'message' => 'Open your followers list to see who else has joined.',
                'icon' => 'user-plus',
            ],
            [
                'type' => 'comment_approved',
                'title' => 'Your comment is live',
                'message' => '"Bookmarked for later — too much to absorb in one read."',
                'icon' => 'check-circle-2',
            ],
            [
                'type' => 'highlight_resurfaced',
                'title' => 'A highlight from last month came up',
                'message' => '"AI-assisted reporting still demands editorial judgement at every step."',
                'icon' => 'highlighter',
            ],
            [
                'type' => 'weekly_digest',
                'title' => 'Your weekly reading digest is ready',
                'message' => '12 stories curated from your followed topics this week.',
                'icon' => 'mail',
            ],
        ];

        foreach ($visitors as $visitor) {
            // Fresh-archetype visitors get no notifications either.
            if (in_array($visitor->email, self::FRESH_EMAILS, true)) {
                continue;
            }

            // Skip if visitor already has notifications (idempotency).
            if ($visitor->notifications()->count() > 0) {
                continue;
            }

            // Power user gets 5–6 notifications; mid-tier gets 3–4;
            // anonymous visitors get 2. Keeps the bell badge variety
            // honest across the demo accounts.
            $perVisitor = match (true) {
                $visitor->email === 'visitor@demo.com' => 6,
                $visitor->email === 'commuter@demo.com' => 4,
                default => 2,
            };

            foreach (array_slice($samples, 0, $perVisitor) as $i => $sample) {
                $createdAt = Carbon::now()->subHours(rand(1, 72));
                $readAt = $i % 3 === 0 ? null : Carbon::now()->subHours(rand(0, 12));

                $visitor->notifications()->create([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\\Notifications\\Reader\\Sample',
                    'data' => $sample,
                    'read_at' => $readAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}
