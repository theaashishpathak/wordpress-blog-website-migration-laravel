<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Support\LocaleResolver;
use Illuminate\Http\Response;

/**
 * XML sitemap generator for search engines.
 *
 * Emits a single combined sitemap covering every published post,
 * category, and page for the default locale plus alternate hreflang
 * links for other active locales.
 */
class SitemapController
{
    public function __invoke(LocaleResolver $resolver): Response
    {
        $defaultLocale = $resolver->default();
        $activeLocales = $resolver->activeLanguages();

        $urls = [];

        // Homepage
        $urls[] = $this->makeUrl(
            loc: url('/'),
            lastmod: now()->toIso8601String(),
            changefreq: 'hourly',
            priority: '1.0',
        );

        // Categories
        Category::query()->with('translations')->ordered()->limit(500)->chunk(100, function ($cats) use (&$urls, $defaultLocale, $activeLocales): void {
            foreach ($cats as $cat) {
                $slug = $cat->translate('slug', $defaultLocale?->code);
                if (! $slug) {
                    continue;
                }
                $urls[] = $this->makeUrl(
                    loc: url("/{$defaultLocale?->code}/{$slug}"),
                    lastmod: ($cat->updated_at ?? now())->toIso8601String(),
                    changefreq: 'daily',
                    priority: '0.7',
                    alternates: $this->collectAlternates($cat, $activeLocales),
                );
            }
        });

        // Published pages
        Page::query()->with('translations')->where('status', 'published')->chunk(100, function ($pages) use (&$urls, $defaultLocale, $activeLocales): void {
            foreach ($pages as $page) {
                $slug = $page->translate('slug', $defaultLocale?->code);
                if (! $slug) {
                    continue;
                }
                $urls[] = $this->makeUrl(
                    loc: url("/{$defaultLocale?->code}/{$slug}"),
                    lastmod: ($page->updated_at ?? now())->toIso8601String(),
                    changefreq: 'monthly',
                    priority: '0.5',
                    alternates: $this->collectAlternates($page, $activeLocales),
                );
            }
        });

        // Published posts
        Post::query()->with('translations')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit(5000)
            ->chunk(200, function ($posts) use (&$urls, $defaultLocale, $activeLocales): void {
                foreach ($posts as $post) {
                    $slug = $post->translate('slug', $defaultLocale?->code);
                    if (! $slug) {
                        continue;
                    }
                    $urls[] = $this->makeUrl(
                        loc: url("/{$defaultLocale?->code}/{$slug}"),
                        lastmod: ($post->updated_at ?? $post->published_at ?? now())->toIso8601String(),
                        changefreq: 'weekly',
                        priority: '0.8',
                        alternates: $this->collectAlternates($post, $activeLocales),
                    );
                }
            });

        $xml = $this->renderXml($urls);

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * @param  list<\App\Models\Language>  $locales
     * @return array<string, string>
     */
    private function collectAlternates(\Illuminate\Database\Eloquent\Model $entity, array $locales): array
    {
        $out = [];

        foreach ($locales as $loc) {
            $slug = $entity->translate('slug', $loc->code);
            if ($slug) {
                $out[$loc->code] = url("/{$loc->code}/{$slug}");
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $alternates
     */
    private function makeUrl(string $loc, string $lastmod, string $changefreq, string $priority, array $alternates = []): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
            'alternates' => $alternates,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $urls
     */
    private function renderXml(array $urls): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xhtml=\"http://www.w3.org/1999/xhtml\">\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($url['loc']).'</loc>'."\n";
            $xml .= '    <lastmod>'.htmlspecialchars($url['lastmod']).'</lastmod>'."\n";
            $xml .= '    <changefreq>'.htmlspecialchars($url['changefreq']).'</changefreq>'."\n";
            $xml .= '    <priority>'.htmlspecialchars($url['priority']).'</priority>'."\n";
            foreach (($url['alternates'] ?? []) as $code => $altUrl) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="'.htmlspecialchars($code).'" href="'.htmlspecialchars($altUrl).'" />'."\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";

        return $xml;
    }
}
