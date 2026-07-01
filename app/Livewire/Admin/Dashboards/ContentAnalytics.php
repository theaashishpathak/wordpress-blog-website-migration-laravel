<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboards;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Content Analytics — newsroom-side performance dashboard. Shows the
 * shape of the catalogue (status mix, locale coverage), throughput
 * (posts per day / per author), and per-post performance (top by
 * views, top by comments).
 */
#[Layout('layouts.app')]
#[Title('Dashboard · Content Analytics')]
class ContentAnalytics extends Component
{
    public function mount(): void
    {
        abort_unless(
            auth()->user()?->canAny(['posts.view', 'posts.view_any']) ?? false,
            403,
            'You do not have access to content analytics.',
        );
    }

    /**
     * @return array{total:int, published:int, draft:int, pending:int, scheduled:int, archived:int}
     */
    #[Computed]
    public function statusCounts(): array
    {
        $rows = Post::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return [
            'total' => (int) Post::query()->count(),
            'published' => (int) ($rows[PostStatus::Published->value] ?? 0),
            'draft' => (int) ($rows[PostStatus::Draft->value] ?? 0),
            'pending' => (int) ($rows[PostStatus::PendingReview->value] ?? 0),
            'scheduled' => (int) ($rows[PostStatus::Scheduled->value] ?? 0),
            'archived' => (int) ($rows[PostStatus::Archived->value] ?? 0),
        ];
    }

    /**
     * 30-day publishing throughput, day-by-day.
     *
     * @return array{labels:list<string>, counts:list<int>}
     */
    #[Computed]
    public function publishingChart(): array
    {
        $start = now()->subDays(29)->startOfDay();

        $rows = Post::query()
            ->where('status', PostStatus::Published->value)
            ->where('published_at', '>=', $start)
            ->selectRaw('DATE(published_at) as day, COUNT(*) as c')
            ->groupBy('day')
            ->pluck('c', 'day');

        $labels = [];
        $counts = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M j');
            $counts[] = (int) ($rows[$day] ?? 0);
        }

        return ['labels' => $labels, 'counts' => $counts];
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function topByViews(): Collection
    {
        return Post::query()
            ->with(['translations', 'category.translations'])
            ->where('status', PostStatus::Published->value)
            ->orderByDesc('view_count')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function topByComments(): Collection
    {
        return Post::query()
            ->with(['translations', 'category.translations'])
            ->where('status', PostStatus::Published->value)
            ->orderByDesc('comment_count')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, object{name:string, c:int}>
     */
    #[Computed]
    public function authorLeaderboard(): Collection
    {
        return Post::query()
            ->join('users', 'posts.author_id', '=', 'users.id')
            ->whereNotNull('author_id')
            ->where('posts.created_at', '>=', now()->subDays(30))
            ->selectRaw('users.name as name, COUNT(posts.id) as c')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('c')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, object{label:string, c:int}>
     */
    #[Computed]
    public function categoryBreakdown(): Collection
    {
        return DB::table('posts')
            ->leftJoin('category_translations', function ($join): void {
                $join->on('posts.category_id', '=', 'category_translations.category_id');
                if ($lang = Language::query()->default()->first()) {
                    $join->where('category_translations.language_id', $lang->id);
                }
            })
            ->whereNotNull('posts.category_id')
            ->whereNull('posts.deleted_at')
            ->selectRaw('COALESCE(category_translations.name, "Uncategorised") as label, COUNT(posts.id) as c')
            ->groupBy('label')
            ->orderByDesc('c')
            ->limit(10)
            ->get();
    }

    /**
     * Translation coverage — what % of published posts have a translation
     * row for each active language.
     *
     * @return list<array{language:Language, percent:int, count:int}>
     */
    #[Computed]
    public function translationCoverage(): array
    {
        $published = Post::query()->where('status', PostStatus::Published->value)->pluck('id');
        $totalPublished = $published->count();

        if ($totalPublished === 0) {
            return [];
        }

        $languages = Language::query()->active()->ordered()->get();
        $out = [];

        foreach ($languages as $lang) {
            $count = PostTranslation::query()
                ->whereIn('post_id', $published)
                ->where('language_id', $lang->id)
                ->where(function ($q): void {
                    $q->whereNotNull('title')->where('title', '!=', '');
                })
                ->count();

            $out[] = [
                'language' => $lang,
                'count' => $count,
                'percent' => (int) round(($count / max(1, $totalPublished)) * 100),
            ];
        }

        return $out;
    }

    /**
     * @return array{total:int, posts_per_tag_avg:float, most_used:Collection<int, object{name:string, c:int}>}
     */
    #[Computed]
    public function tagStats(): array
    {
        $mostUsed = DB::table('post_tag')
            ->join('tags', 'post_tag.tag_id', '=', 'tags.id')
            ->selectRaw('tags.name as name, COUNT(post_tag.post_id) as c')
            ->groupBy('tags.id', 'tags.name')
            ->orderByDesc('c')
            ->limit(15)
            ->get();

        return [
            'total' => Tag::query()->count(),
            'posts_per_tag_avg' => round((float) DB::table('post_tag')->count() / max(1, Tag::query()->count()), 1),
            'most_used' => $mostUsed,
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.dashboards.content-analytics');
    }
}
