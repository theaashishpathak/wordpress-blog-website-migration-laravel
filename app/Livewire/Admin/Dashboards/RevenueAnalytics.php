<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboards;

use App\Models\AdCreative;
use App\Models\AdZone;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Revenue Analytics — ad performance + newsletter growth funnel.
 *
 * The current schema records impressions + clicks on the creative
 * itself; there's no separate event log, so all numbers come from the
 * denormalised counters. Newsletter growth is computed from subscriber
 * timestamps.
 */
#[Layout('layouts.app')]
#[Title('Dashboard · Revenue Analytics')]
class RevenueAnalytics extends Component
{
    public function mount(): void
    {
        abort_unless(
            auth()->user()?->canAny(['ads.view', 'newsletter.view']) ?? false,
            403,
            'You do not have access to revenue analytics.',
        );
    }

    /**
     * @return array{
     *   impressions:int,
     *   clicks:int,
     *   ctr:float,
     *   active_creatives:int,
     *   active_zones:int,
     *   subscribers:int,
     * }
     */
    #[Computed]
    public function tiles(): array
    {
        $impressions = (int) AdCreative::query()->sum('impression_count');
        $clicks = (int) AdCreative::query()->sum('click_count');
        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0;

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $ctr,
            'active_creatives' => AdCreative::query()->where('status', AdCreative::STATUS_ACTIVE)->count(),
            'active_zones' => AdZone::query()->where('is_active', true)->count(),
            'subscribers' => NewsletterSubscriber::query()->where('status', NewsletterSubscriber::STATUS_CONFIRMED)->count(),
        ];
    }

    /**
     * @return Collection<int, object{label:string, impressions:int, clicks:int, ctr:float}>
     */
    #[Computed]
    public function byZone(): Collection
    {
        return AdZone::query()
            ->leftJoin('ad_creatives', 'ad_zones.id', '=', 'ad_creatives.zone_id')
            ->selectRaw('ad_zones.name as label, COALESCE(SUM(ad_creatives.impression_count), 0) as impressions, COALESCE(SUM(ad_creatives.click_count), 0) as clicks')
            ->groupBy('ad_zones.id', 'ad_zones.name')
            ->orderByDesc('impressions')
            ->limit(10)
            ->get()
            ->map(fn ($row) => (object) [
                'label' => $row->label,
                'impressions' => (int) $row->impressions,
                'clicks' => (int) $row->clicks,
                'ctr' => $row->impressions > 0
                    ? round(($row->clicks / $row->impressions) * 100, 2)
                    : 0.0,
            ]);
    }

    /**
     * @return Collection<int, object{name:string, impressions:int, clicks:int, ctr:float, zone:?string}>
     */
    #[Computed]
    public function topCreatives(): Collection
    {
        return AdCreative::query()
            ->with('zone:id,name')
            ->orderByDesc('click_count')
            ->limit(10)
            ->get()
            ->map(fn (AdCreative $c) => (object) [
                'id' => $c->id,
                'name' => $c->name,
                'zone' => $c->zone?->name,
                'impressions' => (int) $c->impression_count,
                'clicks' => (int) $c->click_count,
                'ctr' => $c->impression_count > 0
                    ? round(($c->click_count / $c->impression_count) * 100, 2)
                    : 0.0,
            ]);
    }

    /**
     * Newsletter growth over the last 30 days, day-by-day cumulative
     * confirmed subscribers.
     *
     * @return array{labels:list<string>, total:list<int>, new_today:list<int>}
     */
    #[Computed]
    public function newsletterGrowth(): array
    {
        $start = now()->subDays(29)->startOfDay();

        // Per-day new confirmations.
        $perDay = NewsletterSubscriber::query()
            ->where('status', NewsletterSubscriber::STATUS_CONFIRMED)
            ->whereNotNull('confirmed_at')
            ->where('confirmed_at', '>=', $start)
            ->selectRaw('DATE(confirmed_at) as day, COUNT(*) as c')
            ->groupBy('day')
            ->pluck('c', 'day');

        // Baseline before the chart window so cumulative starts correctly.
        $baseline = (int) NewsletterSubscriber::query()
            ->where('status', NewsletterSubscriber::STATUS_CONFIRMED)
            ->whereNotNull('confirmed_at')
            ->where('confirmed_at', '<', $start)
            ->count();

        $labels = [];
        $cumulative = [];
        $newToday = [];
        $running = $baseline;

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $day = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $today = (int) ($perDay[$day] ?? 0);
            $newToday[] = $today;
            $running += $today;
            $cumulative[] = $running;
        }

        return ['labels' => $labels, 'total' => $cumulative, 'new_today' => $newToday];
    }

    /**
     * @return array{total:int, confirmed:int, pending:int, unsubscribed:int, growth_30d:int}
     */
    #[Computed]
    public function subscriberMix(): array
    {
        $rows = NewsletterSubscriber::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $growth30d = NewsletterSubscriber::query()
            ->where('status', NewsletterSubscriber::STATUS_CONFIRMED)
            ->where('confirmed_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total' => (int) array_sum($rows->toArray()),
            'confirmed' => (int) ($rows[NewsletterSubscriber::STATUS_CONFIRMED] ?? 0),
            'pending' => (int) ($rows[NewsletterSubscriber::STATUS_PENDING] ?? 0),
            'unsubscribed' => (int) ($rows[NewsletterSubscriber::STATUS_UNSUBSCRIBED] ?? 0),
            'growth_30d' => $growth30d,
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.dashboards.revenue-analytics');
    }
}
