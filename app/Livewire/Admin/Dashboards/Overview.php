<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboards;

use App\Enums\PostStatus;
use App\Models\AdCreative;
use App\Models\Comment;
use App\Models\NewsletterSubscriber;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Newsroom Overview — the at-a-glance dashboard that admins / editors
 * land on. Mixes content health, audience signals, and the operational
 * queue (pending review, pending comments) into a single page.
 */
#[Layout('layouts.app')]
#[Title('Dashboard · Overview')]
class Overview extends Component
{
    public function mount(): void
    {
        // Every authenticated user can land here — the underlying tiles
        // skip queries the user doesn't have permission to run.
        abort_unless(auth()->check(), 403);
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function tiles(): array
    {
        $thisWeek = now()->startOfWeek();

        return [
            'published_this_week' => Post::query()
                ->where('status', PostStatus::Published->value)
                ->where('published_at', '>=', $thisWeek)
                ->count(),
            'pending_review' => Post::query()
                ->where('status', PostStatus::PendingReview->value)
                ->count(),
            'pending_comments' => Comment::query()
                ->where('status', Comment::STATUS_PENDING)
                ->count(),
            'unread_notifications' => (int) (auth()->user()?->unreadNotifications()->count() ?? 0),
            'newsletter_confirmed' => NewsletterSubscriber::query()
                ->where('status', NewsletterSubscriber::STATUS_CONFIRMED)
                ->count(),
            'active_ads' => AdCreative::query()
                ->where('status', AdCreative::STATUS_ACTIVE)
                ->count(),
        ];
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function recentPosts(): Collection
    {
        return Post::query()
            ->with(['translations', 'category.translations', 'author:id,name'])
            ->latest('updated_at')
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, Comment>
     */
    #[Computed]
    public function recentComments(): Collection
    {
        return Comment::query()
            ->with(['post.translations'])
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function trendingPosts(): Collection
    {
        return Post::query()
            ->with(['translations', 'category.translations'])
            ->where('status', PostStatus::Published->value)
            ->where('published_at', '>=', now()->subDays(30))
            ->orderByDesc('view_count')
            ->limit(5)
            ->get();
    }

    /**
     * Day-by-day published count for the past 14 days, used in the
     * sparkline at the top of the page.
     *
     * @return array{labels:list<string>, counts:list<int>}
     */
    #[Computed]
    public function publishingTrend(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = Post::query()
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

    public function render(): View
    {
        return view('livewire.admin.dashboards.overview');
    }
}
