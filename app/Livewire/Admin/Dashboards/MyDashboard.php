<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboards;

use App\Enums\PostStatus;
use App\Models\AIUsageLog;
use App\Models\Comment;
use App\Models\LoginLog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('My Dashboard')]
class MyDashboard extends Component
{
    public User $me;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);
        $this->me = $user->load(['department', 'manager']);
    }

    // -------------------------------------------------------------------------
    // SUPER ADMIN: Site-wide tiles
    // -------------------------------------------------------------------------

    /** @return array<string, int> */
    #[Computed]
    public function siteTiles(): array
    {
        return [
            'total_published' => Post::where('status', PostStatus::Published->value)->count(),
            'published_today' => Post::where('status', PostStatus::Published->value)
                ->whereDate('published_at', today())->count(),
            'pending_review' => Post::whereIn('status', [
                PostStatus::PendingReview->value,
                PostStatus::InReview->value,
            ])->count(),
            'scheduled' => Post::where('status', PostStatus::Scheduled->value)->count(),
            'drafts' => Post::where('status', PostStatus::Draft->value)->count(),
            'pending_comments' => Comment::where('status', Comment::STATUS_PENDING)->count(),
        ];
    }

    // -------------------------------------------------------------------------
    // SUPER ADMIN: Pending review queue
    // -------------------------------------------------------------------------

    /** @return Collection<int, Post> */
    #[Computed]
    public function pendingReviewQueue(): Collection
    {
        return Post::with(['translations', 'category.translations', 'author:id,name'])
            ->whereIn('status', [
                PostStatus::PendingReview->value,
                PostStatus::InReview->value,
            ])
            ->latest('updated_at')
            ->limit(8)
            ->get();
    }

    // -------------------------------------------------------------------------
    // SUPER ADMIN: Scheduled posts
    // -------------------------------------------------------------------------

    /** @return Collection<int, Post> */
    #[Computed]
    public function scheduledPosts(): Collection
    {
        return Post::with(['translations', 'category.translations', 'author:id,name'])
            ->where('status', PostStatus::Scheduled->value)
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->limit(6)
            ->get();
    }

    // -------------------------------------------------------------------------
    // SUPER ADMIN: Homepage featured content snapshot
    // -------------------------------------------------------------------------

    /** @return array<string, Collection<int, Post>> */
    #[Computed]
    public function featuredContent(): array
    {
        $base = Post::with(['translations', 'category.translations', 'author:id,name'])
            ->where('status', PostStatus::Published->value);

        return [
            'breaking' => (clone $base)->where('is_breaking', true)
                ->where(fn($q) => $q->whereNull('breaking_expires_at')
                    ->orWhere('breaking_expires_at', '>', now()))
                ->latest('published_at')->limit(3)->get(),
            'featured' => (clone $base)->where('is_featured', true)
                ->latest('published_at')->limit(4)->get(),
            'trending' => (clone $base)->where('is_trending', true)
                ->latest('published_at')->limit(4)->get(),
            'editors_pick' => (clone $base)->where('is_editors_pick', true)
                ->latest('published_at')->limit(4)->get(),
        ];
    }

    // -------------------------------------------------------------------------
    // SUPER ADMIN: Team activity (who published what, last 7 days)
    // -------------------------------------------------------------------------

    /** @return \Illuminate\Support\Collection<int, object> */
    #[Computed]
    public function authorActivity(): \Illuminate\Support\Collection
    {
        return DB::table('posts')
            ->join('users', 'posts.author_id', '=', 'users.id')
            ->where('posts.status', PostStatus::Published->value)
            ->where('posts.published_at', '>=', now()->subDays(7))
            ->whereNull('posts.deleted_at')
            ->selectRaw('users.id, users.name,
                COUNT(posts.id) as published_count,
                MAX(posts.published_at) as last_published_at')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('published_count')
            ->limit(8)
            ->get();
    }

    // -------------------------------------------------------------------------
    // SUPER ADMIN: Recently published (all authors)
    // -------------------------------------------------------------------------

    /** @return Collection<int, Post> */
    #[Computed]
    public function recentlyPublished(): Collection
    {
        return Post::with(['translations', 'category.translations', 'author:id,name'])
            ->where('status', PostStatus::Published->value)
            ->latest('published_at')
            ->limit(8)
            ->get();
    }

    // -------------------------------------------------------------------------
    // PER-USER: My post stats
    // -------------------------------------------------------------------------

    /** @return array<string, int> */
    #[Computed]
    public function myPostStats(): array
    {
        $myPostIds = Post::where('author_id', $this->me->id)->pluck('id');

        $statusRows = Post::where('author_id', $this->me->id)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $publishedMonth = Post::where('author_id', $this->me->id)
            ->where('status', PostStatus::Published->value)
            ->where('published_at', '>=', now()->startOfMonth())
            ->count();

        $views30d = (int) Post::where('author_id', $this->me->id)
            ->where('status', PostStatus::Published->value)
            ->where('published_at', '>=', now()->subDays(30))
            ->sum('view_count');

        $viewsTotal = (int) Post::where('author_id', $this->me->id)->sum('view_count');

        $commentsOnMyPosts = $myPostIds->isEmpty() ? 0 : Comment::whereIn('post_id', $myPostIds)->count();
        $pendingCommentsOnMyPosts = $myPostIds->isEmpty() ? 0 : Comment::whereIn('post_id', $myPostIds)
            ->where('status', Comment::STATUS_PENDING)->count();

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

    /** @return Collection<int, Post> */
    #[Computed]
    public function myRecentPosts(): Collection
    {
        return Post::with(['translations', 'category.translations'])
            ->where('author_id', $this->me->id)
            ->latest('updated_at')
            ->limit(6)
            ->get();
    }

    /** @return Collection<int, Post> */
    #[Computed]
    public function myTopPosts(): Collection
    {
        return Post::with(['translations', 'category.translations'])
            ->where('author_id', $this->me->id)
            ->where('status', PostStatus::Published->value)
            ->orderByDesc('view_count')
            ->limit(5)
            ->get();
    }

    /** @return array{labels:list<string>, counts:list<int>} */
    #[Computed]
    public function myPublishingChart(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = Post::where('author_id', $this->me->id)
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

    /** @return array{calls:int, tokens:int, cost:float, last_used_at:?\Carbon\Carbon} */
    #[Computed]
    public function myAiSpend(): array
    {
        $row = AIUsageLog::where('user_id', $this->me->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('COUNT(*) as calls, COALESCE(SUM(total_tokens),0) as tokens,
                         COALESCE(SUM(estimated_cost_usd),0) as cost, MAX(created_at) as last_used')
            ->first();

        return [
            'calls' => (int) ($row->calls ?? 0),
            'tokens' => (int) ($row->tokens ?? 0),
            'cost' => round((float) ($row->cost ?? 0), 4),
            'last_used_at' => $row->last_used ? \Carbon\Carbon::parse($row->last_used) : null,
        ];
    }

    /** @return \Illuminate\Support\Collection<int, object> */
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

    /** @return Collection<int, Comment> */
    #[Computed]
    public function recentCommentsOnMyPosts(): Collection
    {
        $myPostIds = Post::where('author_id', $this->me->id)->pluck('id');

        if ($myPostIds->isEmpty()) {
            return new Collection;
        }

        return Comment::with('post.translations')
            ->whereIn('post_id', $myPostIds)
            ->latest()
            ->limit(5)
            ->get();
    }

    /** @return array{unread:int, latest:\Illuminate\Support\Collection} */
    #[Computed]
    public function notificationStats(): array
    {
        return [
            'unread' => (int) $this->me->unreadNotifications()->count(),
            'latest' => $this->me->notifications()->latest()->limit(5)->get(),
        ];
    }

    /** @return Collection<int, LoginLog> */
    #[Computed]
    public function recentLogins(): Collection
    {
        return LoginLog::where('user_id', $this->me->id)
            ->latest('login_at')
            ->limit(5)
            ->get();
    }

    /** @return array{score:int, missing:list<array{key:string, label:string, url:?string}>} */
    #[Computed]
    public function profileCompleteness(): array
    {
        $checks = [
            ['key' => 'avatar', 'label' => 'Upload a profile photo', 'url' => route('profile'), 'done' => !empty($this->me->avatar)],
            ['key' => 'bio', 'label' => 'Write a short bio', 'url' => Gate::allows('posts.create') ? route('author.profile') : route('profile'), 'done' => !empty($this->me->bio)],
            ['key' => 'social', 'label' => 'Add at least one social link', 'url' => Gate::allows('posts.create') ? route('author.profile') : route('profile'), 'done' => !empty($this->me->social_links) && count((array) $this->me->social_links) > 0],
            ['key' => 'two_factor', 'label' => 'Enable two-factor auth', 'url' => route('profile'), 'done' => !empty($this->me->two_factor_confirmed_at)],
        ];

        $done = count(array_filter($checks, fn($c) => $c['done']));
        $score = (int) round(($done / count($checks)) * 100);

        $missing = array_values(array_filter(array_map(
            fn($c) => $c['done'] ? null : ['key' => $c['key'], 'label' => $c['label'], 'url' => $c['url']],
            $checks,
        )));

        return ['score' => $score, 'missing' => $missing];
    }

    /** @return list<array{label:string, url:string, icon:string}> */
    #[Computed]
    public function quickActions(): array
    {
        $actions = [];

        if (Gate::allows('posts.create'))
            $actions[] = ['label' => 'New Post', 'url' => route('admin.posts.create'), 'icon' => 'plus'];
        if (Gate::allows('ai.use_writer'))
            $actions[] = ['label' => 'AI Writer', 'url' => route('admin.ai.writer'), 'icon' => 'sparkles'];
        if (Gate::any(['posts.view', 'posts.view_any']))
            $actions[] = ['label' => 'My Posts', 'url' => route('admin.posts.index'), 'icon' => 'newspaper'];
        if (Gate::allows('editorial.review_queue'))
            $actions[] = ['label' => 'Review Queue', 'url' => route('admin.editorial.queue'), 'icon' => 'check-square'];
        if (Gate::allows('comments.moderate'))
            $actions[] = ['label' => 'Moderate', 'url' => route('admin.comments.index'), 'icon' => 'message-square'];

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
