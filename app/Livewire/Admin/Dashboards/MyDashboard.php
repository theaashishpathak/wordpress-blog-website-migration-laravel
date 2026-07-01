<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboards;

use App\Enums\PostStatus;
use App\Models\AIUsageLog;
use App\Models\Comment;
use App\Models\LoginLog;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * My Dashboard — the per-user personal command centre.
 *
 * Surfaces what the logged-in user is doing right now: their drafts,
 * pending submissions, AI spend, post performance, comments awaiting
 * their moderation, recent logins, and profile-completeness nudges.
 *
 * Every section is role-aware — an author sees only their own posts
 * and AI spend; an admin additionally sees platform totals and the
 * editorial queue across all authors.
 */
#[Layout('layouts.app')]
#[Title('My Dashboard')]
class MyDashboard extends Component
{
    /** @var User */
    public User $me;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $this->me = $user->load(['department', 'manager']);
    }

    // -----------------------------------------------------------------
    // Per-user computed metrics
    // -----------------------------------------------------------------

    /**
     * @return array{
     *   drafts:int, pending:int, published_total:int, published_month:int,
     *   views_30d:int, views_total:int, scheduled:int,
     *   comments_on_my_posts:int, pending_comments_on_my_posts:int,
     * }
     */
    #[Computed]
    public function myPostStats(): array
    {
        $myPostIds = Post::query()->where('author_id', $this->me->id)->pluck('id');

        $statusRows = Post::query()
            ->where('author_id', $this->me->id)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $publishedMonth = Post::query()
            ->where('author_id', $this->me->id)
            ->where('status', PostStatus::Published->value)
            ->where('published_at', '>=', now()->startOfMonth())
            ->count();

        $views30d = (int) Post::query()
            ->where('author_id', $this->me->id)
            ->where('status', PostStatus::Published->value)
            ->where('published_at', '>=', now()->subDays(30))
            ->sum('view_count');

        $viewsTotal = (int) Post::query()
            ->where('author_id', $this->me->id)
            ->sum('view_count');

        $commentsOnMyPosts = $myPostIds->isEmpty() ? 0 : Comment::query()
            ->whereIn('post_id', $myPostIds)
            ->count();

        $pendingCommentsOnMyPosts = $myPostIds->isEmpty() ? 0 : Comment::query()
            ->whereIn('post_id', $myPostIds)
            ->where('status', Comment::STATUS_PENDING)
            ->count();

        return [
            'drafts' => (int) ($statusRows[PostStatus::Draft->value] ?? 0),
            'pending' => (int) ($statusRows[PostStatus::PendingReview->value] ?? 0),
            'published_total' => (int) ($statusRows[PostStatus::Published->value] ?? 0),
            'published_month' => $publishedMonth,
            'scheduled' => (int) ($statusRows[PostStatus::Scheduled->value] ?? 0),
            'views_30d' => $views30d,
            'views_total' => $viewsTotal,
            'comments_on_my_posts' => $commentsOnMyPosts,
            'pending_comments_on_my_posts' => $pendingCommentsOnMyPosts,
        ];
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function myRecentPosts(): Collection
    {
        return Post::query()
            ->with(['translations', 'category.translations'])
            ->where('author_id', $this->me->id)
            ->latest('updated_at')
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function myTopPosts(): Collection
    {
        return Post::query()
            ->with(['translations', 'category.translations'])
            ->where('author_id', $this->me->id)
            ->where('status', PostStatus::Published->value)
            ->orderByDesc('view_count')
            ->limit(5)
            ->get();
    }

    /**
     * @return array{labels:list<string>, counts:list<int>}
     */
    #[Computed]
    public function myPublishingChart(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = Post::query()
            ->where('author_id', $this->me->id)
            ->where('status', PostStatus::Published->value)
            ->where('published_at', '>=', $start)
            ->selectRaw('DATE(published_at) as day, COUNT(*) as c')
            ->groupBy('day')
            ->pluck('c', 'day');

        $labels = [];
        $counts = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M j');
            $counts[] = (int) ($rows[$day] ?? 0);
        }

        return ['labels' => $labels, 'counts' => $counts];
    }

    /**
     * AI spend for the current user this month.
     *
     * @return array{calls:int, tokens:int, cost:float, last_used_at:?\Carbon\Carbon}
     */
    #[Computed]
    public function myAiSpend(): array
    {
        $row = AIUsageLog::query()
            ->where('user_id', $this->me->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('COUNT(*) as calls, COALESCE(SUM(total_tokens), 0) as tokens, COALESCE(SUM(estimated_cost_usd), 0) as cost, MAX(created_at) as last_used')
            ->first();

        return [
            'calls' => (int) ($row->calls ?? 0),
            'tokens' => (int) ($row->tokens ?? 0),
            'cost' => round((float) ($row->cost ?? 0), 4),
            'last_used_at' => $row->last_used ? \Carbon\Carbon::parse($row->last_used) : null,
        ];
    }

    /**
     * Top categories I tend to write in (last 90 days).
     *
     * @return \Illuminate\Support\Collection<int, object{name:string, c:int}>
     */
    #[Computed]
    public function myTopCategories(): \Illuminate\Support\Collection
    {
        return DB::table('posts')
            ->leftJoin('category_translations', function ($join): void {
                $join->on('posts.category_id', '=', 'category_translations.category_id');
                if ($lang = \App\Models\Language::query()->default()->first()) {
                    $join->where('category_translations.language_id', $lang->id);
                }
            })
            ->where('posts.author_id', $this->me->id)
            ->where('posts.created_at', '>=', now()->subDays(90))
            ->whereNotNull('posts.category_id')
            ->selectRaw('COALESCE(category_translations.name, "Uncategorised") as name, COUNT(posts.id) as c')
            ->groupBy('name')
            ->orderByDesc('c')
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, Comment>
     */
    #[Computed]
    public function recentCommentsOnMyPosts(): Collection
    {
        $myPostIds = Post::query()->where('author_id', $this->me->id)->pluck('id');

        if ($myPostIds->isEmpty()) {
            return new Collection();
        }

        return Comment::query()
            ->with('post.translations')
            ->whereIn('post_id', $myPostIds)
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * Notifications + login activity for the user.
     *
     * @return array{unread:int, latest:\Illuminate\Support\Collection}
     */
    #[Computed]
    public function notificationStats(): array
    {
        return [
            'unread' => (int) $this->me->unreadNotifications()->count(),
            'latest' => $this->me->notifications()->latest()->limit(5)->get(),
        ];
    }

    /**
     * @return Collection<int, LoginLog>
     */
    #[Computed]
    public function recentLogins(): Collection
    {
        return LoginLog::query()
            ->where('user_id', $this->me->id)
            ->latest('login_at')
            ->limit(5)
            ->get();
    }

    /**
     * Profile-completeness score — encourages users to fill in bio,
     * avatar, social links, 2FA. Each missing piece is a nudge card.
     *
     * @return array{score:int, missing:list<array{key:string, label:string, url:?string}>}
     */
    #[Computed]
    public function profileCompleteness(): array
    {
        $checks = [
            ['key' => 'avatar', 'label' => 'Upload a profile photo', 'url' => route('profile'), 'done' => ! empty($this->me->avatar)],
            ['key' => 'bio', 'label' => 'Write a short bio', 'url' => Gate::allows('posts.create') ? route('author.profile') : route('profile'), 'done' => ! empty($this->me->bio)],
            ['key' => 'social', 'label' => 'Add at least one social link', 'url' => Gate::allows('posts.create') ? route('author.profile') : route('profile'), 'done' => ! empty($this->me->social_links) && count((array) $this->me->social_links) > 0],
            ['key' => 'phone', 'label' => 'Set a contact phone', 'url' => route('profile'), 'done' => ! empty($this->me->phone)],
            ['key' => 'two_factor', 'label' => 'Enable two-factor auth', 'url' => route('profile'), 'done' => ! empty($this->me->two_factor_confirmed_at)],
        ];

        $done = count(array_filter($checks, fn ($c) => $c['done']));
        $score = (int) round(($done / count($checks)) * 100);

        $missing = array_values(array_filter(
            array_map(
                fn ($c) => $c['done'] ? null : ['key' => $c['key'], 'label' => $c['label'], 'url' => $c['url']],
                $checks,
            ),
        ));

        return ['score' => $score, 'missing' => $missing];
    }

    /**
     * Quick links the user can act on. Permission-gated so an author
     * sees only their writer-side shortcuts, a comment moderator sees
     * their queue, etc.
     *
     * @return list<array{label:string, url:string, icon:string, color:string}>
     */
    #[Computed]
    public function quickActions(): array
    {
        $actions = [];

        if (Gate::allows('posts.create')) {
            $actions[] = ['label' => 'New Post', 'url' => route('admin.posts.create'), 'icon' => 'plus', 'color' => 'from-indigo-500 to-violet-500'];
        }
        if (Gate::allows('ai.use_writer')) {
            $actions[] = ['label' => 'AI Writer', 'url' => route('admin.ai.writer'), 'icon' => 'sparkles', 'color' => 'from-violet-500 to-fuchsia-500'];
        }
        if (Gate::any(['posts.view', 'posts.view_any'])) {
            $actions[] = ['label' => 'My Posts', 'url' => route('admin.posts.index'), 'icon' => 'newspaper', 'color' => 'from-emerald-500 to-teal-500'];
        }
        if (Gate::allows('editorial.review_queue')) {
            $actions[] = ['label' => 'Review Queue', 'url' => route('admin.editorial.queue'), 'icon' => 'check-square', 'color' => 'from-amber-500 to-orange-500'];
        }
        if (Gate::allows('comments.moderate')) {
            $actions[] = ['label' => 'Moderate', 'url' => route('admin.comments.index'), 'icon' => 'message-square', 'color' => 'from-rose-500 to-pink-500'];
        }
        if (Gate::allows('posts.create')) {
            $actions[] = ['label' => 'Author Profile', 'url' => route('author.profile'), 'icon' => 'user-cog', 'color' => 'from-sky-500 to-cyan-500'];
        }

        return $actions;
    }

    #[Computed]
    public function teamCount(): int
    {
        return $this->me->subordinates()->count();
    }

    public function render(): View
    {
        return view('livewire.admin.dashboards.my-dashboard');
    }
}
