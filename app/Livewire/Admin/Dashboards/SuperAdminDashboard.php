<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboards;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\LoginLog;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Super Admin Dashboard — Central Command Centre
 *
 * Provides complete visibility into the entire content publishing ecosystem:
 * - Site-wide statistics at a glance
 * - Complete content pipeline (drafts → review → scheduled → published)
 * - Recent activity feed across all users
 * - Team performance and author activity
 * - Quick management actions for all content types
 */
#[Layout('layouts.app')]
#[Title('Super Admin Dashboard')]
class SuperAdminDashboard extends Component
{
    public User $me;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);
        abort_unless(
            $user->hasAnyRole(['Super Admin', 'Super-Admin', 'super_admin', 'Admin', 'admin']),
            403,
        );

        $this->me = $user->load(['department', 'manager']);
    }

    // -------------------------------------------------------------------------
    // SITE-WIDE STATISTICS
    // -------------------------------------------------------------------------

    /** @return array<string, int> */
    #[Computed]
    public function siteStats(): array
    {
        return [
            'total_posts' => Post::count(),
            'published' => Post::where('status', PostStatus::Published->value)->count(),
            'scheduled' => Post::where('status', PostStatus::Scheduled->value)->count(),
            'drafts' => Post::where('status', PostStatus::Draft->value)->count(),
            'pending_review' => Post::whereIn('status', [
                PostStatus::PendingReview->value,
                PostStatus::InReview->value,
            ])->count(),
            'archived' => Post::onlyTrashed()->count(),
            'total_authors' => User::whereHas('posts')->count(),
            'total_categories' => Category::count(),
            'total_tags' => Tag::count(),
            'total_views' => (int) Post::sum('view_count'),
            'pending_comments' => Comment::where('status', Comment::STATUS_PENDING)->count(),
            'total_comments' => Comment::count(),
        ];
    }

    // -------------------------------------------------------------------------
    // CONTENT PIPELINE (Full Workflow)
    // -------------------------------------------------------------------------

    /** @return Collection<int, Post> */
    #[Computed]
    public function pendingReviewPosts(): Collection
    {
        return Post::with(['translations', 'author', 'category.translations'])
            ->whereIn('status', [
                PostStatus::PendingReview->value,
                PostStatus::InReview->value,
            ])
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    /** @return Collection<int, Post> */
    #[Computed]
    public function scheduledPosts(): Collection
    {
        return Post::with(['translations', 'author', 'category.translations'])
            ->where('status', PostStatus::Scheduled->value)
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();
    }

    /** @return Collection<int, Post> */
    #[Computed]
    public function draftPosts(): Collection
    {
        return Post::with(['translations', 'author', 'category.translations'])
            ->where('status', PostStatus::Draft->value)
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    /** @return Collection<int, Post> */
    #[Computed]
    public function readyToPublishPosts(): Collection
    {
        return Post::with(['translations', 'author', 'category.translations'])
            ->where('status', PostStatus::Approved->value)
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    // -------------------------------------------------------------------------
    // RECENT PUBLISHING ACTIVITY
    // -------------------------------------------------------------------------

    /** @return Collection<int, Post> */
    #[Computed]
    public function recentlyPublished(): Collection
    {
        return Post::with(['translations', 'author', 'category.translations'])
            ->where('status', PostStatus::Published->value)
            ->latest('published_at')
            ->limit(10)
            ->get();
    }

    /** @return Collection<int, Post> */
    #[Computed]
    public function recentlyUpdated(): Collection
    {
        return Post::with(['translations', 'author'])
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    // -------------------------------------------------------------------------
    // ACTIVITY FEED (Timeline)
    // -------------------------------------------------------------------------

    /** @return \Illuminate\Support\Collection<int, object> */
    #[Computed]
    public function activityFeed(): \Illuminate\Support\Collection
    {
        // Combine recent posts, comments, and user logins into a unified feed
        $posts = Post::with(['author'])
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(function ($post) {
                $status = $post->status?->value ?? 'created';
                $action = match ($status) {
                    'published' => 'published',
                    'scheduled' => 'scheduled',
                    'pending_review' => 'submitted for review',
                    'in_review' => 'moved to review',
                    'draft' => 'created/updated',
                    default => 'updated',
                };

                return (object) [
                    'type' => 'post',
                    'icon' => 'newspaper',
                    'color' => 'from-indigo-500 to-violet-500',
                    'title' => $post->translation()?->title ?? '#'.$post->id,
                    'user' => $post->author?->name ?? 'System',
                    'action' => $action,
                    'timestamp' => $post->updated_at,
                    'url' => route('admin.posts.edit', $post),
                    'status' => $status,
                ];
            });

        $comments = Comment::with(['author', 'post'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function ($comment) {
                return (object) [
                    'type' => 'comment',
                    'icon' => 'message-circle',
                    'color' => 'from-sky-500 to-indigo-500',
                    'title' => 'Comment on: ' . ($comment->post?->translation()?->title ?? '#'.$comment->post_id),
                    'user' => $comment->authorName(),
                    'action' => 'commented',
                    'timestamp' => $comment->created_at,
                    'url' => route('admin.comments.index'),
                    'status' => $comment->status,
                ];
            });

        $logins = LoginLog::with(['user'])
            ->latest('login_at')
            ->limit(10)
            ->get()
            ->map(function ($login) {
                return (object) [
                    'type' => 'login',
                    'icon' => 'log-in',
                    'color' => 'from-emerald-500 to-teal-500',
                    'title' => 'User login',
                    'user' => $login->user?->name ?? 'Unknown',
                    'action' => 'signed in',
                    'timestamp' => $login->login_at ?? $login->created_at,
                    'url' => route('admin.staff.index'),
                    'status' => null,
                ];
            });

        // Merge and sort by timestamp
        return $posts->concat($comments)->concat($logins)
            ->sortByDesc(fn($item) => $item->timestamp)
            ->take(20)
            ->values();
    }

    // -------------------------------------------------------------------------
    // TEAM PERFORMANCE
    // -------------------------------------------------------------------------

    /** @return \Illuminate\Support\Collection<int, object> */
    #[Computed]
    public function authorPerformance(): \Illuminate\Support\Collection
    {
        return DB::table('users')
            ->leftJoin('posts', 'users.id', '=', 'posts.author_id')
            ->where('posts.status', PostStatus::Published->value)
            ->selectRaw('
                users.id,
                users.name,
                users.avatar,
                COUNT(posts.id) as published_count,
                COALESCE(SUM(posts.view_count), 0) as total_views,
                MAX(posts.published_at) as last_published_at,
                MIN(posts.published_at) as first_published_at
            ')
            ->groupBy('users.id', 'users.name', 'users.avatar')
            ->orderByDesc('published_count')
            ->limit(10)
            ->get();
    }

    // -------------------------------------------------------------------------
    // PERFORMANCE INSIGHTS (Analytics)
    // -------------------------------------------------------------------------

    /** @return Collection<int, Post> */
    #[Computed]
    public function mostViewedPosts(): Collection
    {
        return Post::with(['translations', 'author', 'category.translations'])
            ->where('status', PostStatus::Published->value)
            ->orderByDesc('view_count')
            ->limit(5)
            ->get();
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    #[Computed]
    public function topCategories(): \Illuminate\Support\Collection
    {
        return DB::table('posts')
            ->join('categories', 'posts.category_id', '=', 'categories.id')
            ->join('category_translations', function ($join) {
                $join->on('categories.id', '=', 'category_translations.category_id');
                if ($lang = \App\Models\Language::query()->default()->first()) {
                    $join->where('category_translations.language_id', $lang->id);
                }
            })
            ->where('posts.status', PostStatus::Published->value)
            ->selectRaw('
                categories.id,
                category_translations.name as category_name,
                COUNT(posts.id) as post_count,
                COALESCE(SUM(posts.view_count), 0) as total_views
            ')
            ->groupBy('categories.id', 'category_translations.name')
            ->orderByDesc('post_count')
            ->limit(5)
            ->get();
    }

    /** @return array{labels:list<string>, published:list<int>, scheduled:list<int>, draft:list<int>} */
    #[Computed]
    public function publishingChart(): array
    {
        $start = now()->subDays(29)->startOfDay();

        $labels = [];
        $published = [];
        $scheduled = [];
        $draft = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');

            $labels[] = $date->format('M d');

            $published[] = Post::where('status', PostStatus::Published->value)
                ->whereDate('published_at', $dateStr)
                ->count();

            $scheduled[] = Post::where('status', PostStatus::Scheduled->value)
                ->whereDate('scheduled_at', $dateStr)
                ->count();

            $draft[] = Post::where('status', PostStatus::Draft->value)
                ->whereDate('updated_at', $dateStr)
                ->count();
        }

        return [
            'labels' => $labels,
            'published' => $published,
            'scheduled' => $scheduled,
            'draft' => $draft,
        ];
    }

    /** @return array{weekly:int, monthly:int, yearly:int, total:int} */
    #[Computed]
    public function publishingStats(): array
    {
        return [
            'weekly' => Post::where('status', PostStatus::Published->value)
                ->where('published_at', '>=', now()->subDays(7))
                ->count(),
            'monthly' => Post::where('status', PostStatus::Published->value)
                ->where('published_at', '>=', now()->startOfMonth())
                ->count(),
            'yearly' => Post::where('status', PostStatus::Published->value)
                ->where('published_at', '>=', now()->startOfYear())
                ->count(),
            'total' => Post::where('status', PostStatus::Published->value)->count(),
        ];
    }

    // -------------------------------------------------------------------------
    // SCHEDULED PUBLISHING WIDGET
    // -------------------------------------------------------------------------

    /** @return array{upcoming:Collection<int, Post>, today:int, next:?Post, missed:Collection<int, Post>} */
    #[Computed]
    public function scheduledWidget(): array
    {
        $upcoming = Post::with(['translations', 'author'])
            ->where('status', PostStatus::Scheduled->value)
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $today = Post::where('status', PostStatus::Scheduled->value)
            ->whereDate('scheduled_at', today())
            ->count();

        $next = Post::with(['translations', 'author'])
            ->where('status', PostStatus::Scheduled->value)
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->first();

        $missed = Post::with(['translations', 'author'])
            ->where('status', PostStatus::Scheduled->value)
            ->where('scheduled_at', '<', now())
            ->orderBy('scheduled_at')
            ->get();

        return [
            'upcoming' => $upcoming,
            'today' => $today,
            'next' => $next,
            'missed' => $missed,
        ];
    }

    // -------------------------------------------------------------------------
    // NOTIFICATIONS & ALERTS
    // -------------------------------------------------------------------------

    /** @return \Illuminate\Support\Collection<int, object> */
    #[Computed]
    public function alerts(): \Illuminate\Support\Collection
    {
        $alerts = collect();

        // Posts pending review
        $pendingCount = Post::whereIn('status', [
            PostStatus::PendingReview->value,
            PostStatus::InReview->value,
        ])->count();

        if ($pendingCount > 0) {
            $alerts->push((object) [
                'type' => 'warning',
                'icon' => 'hourglass',
                'title' => "{$pendingCount} posts awaiting approval",
                'message' => 'Please review these posts before they can be published.',
                'url' => route('admin.editorial.queue'),
                'severity' => $pendingCount > 5 ? 'high' : 'medium',
            ]);
        }

        // Missed scheduled posts
        $missedScheduled = Post::where('status', PostStatus::Scheduled->value)
            ->where('scheduled_at', '<', now())
            ->count();

        if ($missedScheduled > 0) {
            $alerts->push((object) [
                'type' => 'error',
                'icon' => 'alert-circle',
                'title' => "{$missedScheduled} scheduled posts missed",
                'message' => 'These posts were scheduled to publish but were missed.',
                'url' => route('admin.posts.index', ['status' => 'scheduled']),
                'severity' => 'high',
            ]);
        }

        // Posts without featured images
        $noImages = Post::whereDoesntHave('featuredImage')->count();
        if ($noImages > 0) {
            $alerts->push((object) [
                'type' => 'info',
                'icon' => 'image',
                'title' => "{$noImages} posts missing featured images",
                'message' => 'Add featured images to improve visual appeal and engagement.',
                'url' => route('admin.posts.index'),
                'severity' => 'low',
            ]);
        }

        // Comments pending moderation
        $pendingComments = Comment::where('status', Comment::STATUS_PENDING)->count();
        if ($pendingComments > 0) {
            $alerts->push((object) [
                'type' => 'warning',
                'icon' => 'message-square',
                'title' => "{$pendingComments} comments pending moderation",
                'message' => 'Review and approve these comments before they appear publicly.',
                'url' => route('admin.comments.index'),
                'severity' => $pendingComments > 10 ? 'high' : 'medium',
            ]);
        }

        return $alerts->sortByDesc(fn($alert) => match ($alert->severity) {
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        })->values();
    }

    // -------------------------------------------------------------------------
    // QUICK ACTIONS
    // -------------------------------------------------------------------------

    /** @return list<array{label:string, url:string, icon:string, color:string}> */
    #[Computed]
    public function quickActions(): array
    {
        return [
            ['label' => 'New Post', 'url' => route('admin.posts.create'), 'icon' => 'plus', 'color' => 'from-indigo-500 to-violet-500'],
            ['label' => 'Manage Posts', 'url' => route('admin.posts.index'), 'icon' => 'newspaper', 'color' => 'from-emerald-500 to-teal-500'],
            ['label' => 'Review Queue', 'url' => route('admin.editorial.queue'), 'icon' => 'check-square', 'color' => 'from-amber-500 to-orange-500'],
            ['label' => 'Manage Categories', 'url' => route('admin.categories.index'), 'icon' => 'folder', 'color' => 'from-pink-500 to-rose-500'],
            ['label' => 'Manage Tags', 'url' => route('admin.tags.index'), 'icon' => 'tag', 'color' => 'from-purple-500 to-fuchsia-500'],
            ['label' => 'Media Library', 'url' => route('admin.media.index'), 'icon' => 'image', 'color' => 'from-sky-500 to-cyan-500'],
            ['label' => 'Manage Staff', 'url' => route('admin.staff.index'), 'icon' => 'users', 'color' => 'from-slate-500 to-slate-700'],
            ['label' => 'Moderate Comments', 'url' => route('admin.comments.index'), 'icon' => 'message-square', 'color' => 'from-rose-500 to-pink-500'],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.dashboards.super-admin-dashboard');
    }
}
