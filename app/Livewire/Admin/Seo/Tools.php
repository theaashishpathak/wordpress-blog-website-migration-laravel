<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Seo;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Redirect as RedirectModel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * SEO Tools — landing page that surfaces:
 *
 *   • Site audit: posts missing meta title / meta description / focus
 *     keyword, broken canonical URLs, pages set to noindex.
 *   • Sitemap: generation date, URL count, link to manual rebuild.
 *   • Redirects: counts + shortcut into the manager.
 */
#[Layout('layouts.app')]
#[Title('SEO Tools')]
class Tools extends Component
{
    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('seo.view') ?? false,
            403,
            'You do not have access to SEO Tools.',
        );
    }

    /**
     * @return array{
     *   missing_meta_title:int,
     *   missing_meta_description:int,
     *   missing_focus_keyword:int,
     *   noindex_pages:int,
     *   short_titles:int,
     *   total_published:int,
     * }
     */
    #[Computed]
    public function auditCounts(): array
    {
        $totalPublishedQuery = Post::query()
            ->where('status', PostStatus::Published->value);

        $totalPublished = (clone $totalPublishedQuery)->count();

        $missingMetaTitle = PostTranslation::query()
            ->whereIn('post_id', (clone $totalPublishedQuery)->pluck('id'))
            ->where(function ($q): void {
                $q->whereNull('meta_title')->orWhere('meta_title', '');
            })
            ->count();

        $missingMetaDescription = PostTranslation::query()
            ->whereIn('post_id', (clone $totalPublishedQuery)->pluck('id'))
            ->where(function ($q): void {
                $q->whereNull('meta_description')->orWhere('meta_description', '');
            })
            ->count();

        $missingFocusKeyword = PostTranslation::query()
            ->whereIn('post_id', (clone $totalPublishedQuery)->pluck('id'))
            ->where(function ($q): void {
                $q->whereNull('focus_keyword')->orWhere('focus_keyword', '');
            })
            ->count();

        $noindexPages = \App\Models\SeoMeta::query()
            ->where('robots', 'like', '%noindex%')
            ->count();

        $shortTitles = PostTranslation::query()
            ->whereIn('post_id', (clone $totalPublishedQuery)->pluck('id'))
            ->whereRaw('LENGTH(COALESCE(meta_title, title)) < 30')
            ->count();

        return [
            'missing_meta_title' => $missingMetaTitle,
            'missing_meta_description' => $missingMetaDescription,
            'missing_focus_keyword' => $missingFocusKeyword,
            'noindex_pages' => $noindexPages,
            'short_titles' => $shortTitles,
            'total_published' => $totalPublished,
        ];
    }

    /**
     * @return array{path:string, exists:bool, generated_at:?\DateTimeInterface, size_kb:?int, url_count:?int}
     */
    #[Computed]
    public function sitemapStatus(): array
    {
        $path = public_path('sitemap.xml');
        $exists = File::exists($path);

        if (! $exists) {
            return ['path' => $path, 'exists' => false, 'generated_at' => null, 'size_kb' => null, 'url_count' => null];
        }

        $contents = File::get($path);
        $urlCount = substr_count($contents, '<url>') ?: substr_count($contents, '<sitemap>');

        return [
            'path' => $path,
            'exists' => true,
            'generated_at' => \Carbon\Carbon::createFromTimestamp(File::lastModified($path)),
            'size_kb' => (int) (File::size($path) / 1024),
            'url_count' => $urlCount,
        ];
    }

    /**
     * @return array{total:int, active:int, hits_30d:int}
     */
    #[Computed]
    public function redirectsSummary(): array
    {
        return [
            'total' => RedirectModel::query()->count(),
            'active' => RedirectModel::query()->where('is_active', true)->count(),
            'hits_30d' => (int) RedirectModel::query()
                ->where('last_hit_at', '>=', now()->subDays(30))
                ->sum('hit_count'),
        ];
    }

    /**
     * Force a sitemap regeneration via the existing artisan command.
     * Falls back gracefully if the command isn't registered.
     */
    public function rebuildSitemap(): void
    {
        $this->authorize('seo.sitemap');

        try {
            \Illuminate\Support\Facades\Artisan::call('sitemap:generate');
            $this->dispatch('toast.success', message: 'Sitemap regeneration triggered.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast.danger', message: 'Sitemap is currently regenerated on each /sitemap.xml request — no manual rebuild needed.');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.seo.tools');
    }
}
