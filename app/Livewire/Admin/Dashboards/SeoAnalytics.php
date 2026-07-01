<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboards;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Redirect as RedirectModel;
use App\Models\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * SEO Analytics — site-wide on-page SEO health, sitemap status, and
 * redirect performance. Reads from `post_translations` (basic meta),
 * `seo_metas` (polymorphic advanced overrides), `redirects`.
 */
#[Layout('layouts.app')]
#[Title('Dashboard · SEO Analytics')]
class SeoAnalytics extends Component
{
    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('seo.view') ?? false,
            403,
            'You do not have access to SEO analytics.',
        );
    }

    /**
     * @return array{
     *   total_published:int,
     *   missing_meta_title:int,
     *   missing_meta_description:int,
     *   missing_focus_keyword:int,
     *   noindex:int,
     *   short_titles:int,
     *   over_long_descriptions:int,
     *   avg_seo_score:float,
     * }
     */
    #[Computed]
    public function audit(): array
    {
        $publishedIds = Post::query()
            ->where('status', PostStatus::Published->value)
            ->pluck('id');

        $total = $publishedIds->count();

        $missingTitle = PostTranslation::query()
            ->whereIn('post_id', $publishedIds)
            ->where(function ($q): void {
                $q->whereNull('meta_title')->orWhere('meta_title', '');
            })
            ->count();

        $missingDesc = PostTranslation::query()
            ->whereIn('post_id', $publishedIds)
            ->where(function ($q): void {
                $q->whereNull('meta_description')->orWhere('meta_description', '');
            })
            ->count();

        $missingFocus = PostTranslation::query()
            ->whereIn('post_id', $publishedIds)
            ->where(function ($q): void {
                $q->whereNull('focus_keyword')->orWhere('focus_keyword', '');
            })
            ->count();

        $shortTitles = PostTranslation::query()
            ->whereIn('post_id', $publishedIds)
            ->whereRaw('LENGTH(COALESCE(meta_title, title)) < 30')
            ->count();

        $overLongDescs = PostTranslation::query()
            ->whereIn('post_id', $publishedIds)
            ->whereNotNull('meta_description')
            ->whereRaw('LENGTH(meta_description) > 170')
            ->count();

        $noindex = SeoMeta::query()
            ->where('robots', 'like', '%noindex%')
            ->count();

        $avgScore = (float) PostTranslation::query()
            ->whereIn('post_id', $publishedIds)
            ->whereNotNull('seo_score')
            ->avg('seo_score');

        return [
            'total_published' => $total,
            'missing_meta_title' => $missingTitle,
            'missing_meta_description' => $missingDesc,
            'missing_focus_keyword' => $missingFocus,
            'noindex' => $noindex,
            'short_titles' => $shortTitles,
            'over_long_descriptions' => $overLongDescs,
            'avg_seo_score' => round($avgScore, 1),
        ];
    }

    /**
     * @return array{labels:list<string>, counts:list<int>}
     */
    #[Computed]
    public function scoreDistribution(): array
    {
        $publishedIds = Post::query()
            ->where('status', PostStatus::Published->value)
            ->pluck('id');

        $buckets = ['0–25' => 0, '26–50' => 0, '51–75' => 0, '76–90' => 0, '91–100' => 0];

        PostTranslation::query()
            ->whereIn('post_id', $publishedIds)
            ->whereNotNull('seo_score')
            ->pluck('seo_score')
            ->each(function (int $score) use (&$buckets): void {
                if ($score <= 25) $buckets['0–25']++;
                elseif ($score <= 50) $buckets['26–50']++;
                elseif ($score <= 75) $buckets['51–75']++;
                elseif ($score <= 90) $buckets['76–90']++;
                else $buckets['91–100']++;
            });

        return [
            'labels' => array_keys($buckets),
            'counts' => array_values($buckets),
        ];
    }

    /**
     * @return Collection<int, object{post_id:int, title:string, score:int, slug:string}>
     */
    #[Computed]
    public function worstScoringPosts(): Collection
    {
        $publishedIds = Post::query()
            ->where('status', PostStatus::Published->value)
            ->pluck('id');

        return PostTranslation::query()
            ->whereIn('post_id', $publishedIds)
            ->whereNotNull('seo_score')
            ->orderBy('seo_score')
            ->limit(10)
            ->get(['post_id', 'title', 'slug', 'seo_score as score'])
            ->map(fn ($row) => (object) [
                'post_id' => $row->post_id,
                'title' => $row->title,
                'slug' => $row->slug,
                'score' => (int) $row->score,
            ]);
    }

    /**
     * @return Collection<int, RedirectModel>
     */
    #[Computed]
    public function topRedirects(): Collection
    {
        return RedirectModel::query()
            ->orderByDesc('hit_count')
            ->limit(10)
            ->get();
    }

    /**
     * @return array{total:int, active:int, hits_total:int, hits_30d:int}
     */
    #[Computed]
    public function redirectsSummary(): array
    {
        return [
            'total' => RedirectModel::query()->count(),
            'active' => RedirectModel::query()->where('is_active', true)->count(),
            'hits_total' => (int) RedirectModel::query()->sum('hit_count'),
            'hits_30d' => (int) RedirectModel::query()
                ->where('last_hit_at', '>=', now()->subDays(30))
                ->sum('hit_count'),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.dashboards.seo-analytics');
    }
}
