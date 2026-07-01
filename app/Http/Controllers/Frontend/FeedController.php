<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Models\Category;
use App\Models\Post;
use App\Services\SettingService;
use App\Support\LocaleResolver;
use Illuminate\Http\Response;

/**
 * RSS 2.0 feed generator.
 *
 * Routes:
 *   /{locale?}/feed.xml             → site-wide latest 30 posts
 *   /{locale?}/category/{slug}.rss  → category-specific feed
 */
class FeedController
{
    public function __construct(
        private SettingService $settings,
        private LocaleResolver $localeResolver,
    ) {}

    public function global(): Response
    {
        $locale = $this->localeResolver->current() ?? $this->localeResolver->default();

        $posts = Post::query()
            ->with(['translations', 'author:id,name'])
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit(30)
            ->get();

        return $this->render(
            channelTitle: (string) ($this->settings->get('site.name') ?? config('app.name')),
            channelDescription: (string) ($this->settings->get('site.description') ?? 'Latest articles'),
            channelLink: url('/'),
            posts: $posts,
            locale: $locale?->code,
        );
    }

    public function category(string $slug): Response
    {
        $languageId = $this->localeResolver->current()?->id;

        $categoryId = \DB::table('category_translations')
            ->where('slug', $slug)
            ->when($languageId !== null, fn ($q) => $q->where('language_id', $languageId))
            ->value('category_id');

        abort_if($categoryId === null, 404);

        $category = Category::query()->findOrFail($categoryId);

        $posts = Post::query()
            ->with(['translations', 'author:id,name'])
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->where('category_id', $category->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit(30)
            ->get();

        return $this->render(
            channelTitle: (string) ($category->translate('name') ?? '#'.$category->id),
            channelDescription: (string) ($category->translate('description') ?? ''),
            channelLink: url()->current(),
            posts: $posts,
            locale: $this->localeResolver->current()?->code,
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Post>  $posts
     */
    private function render(string $channelTitle, string $channelDescription, string $channelLink, $posts, ?string $locale): Response
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
        $xml .= "  <channel>\n";
        $xml .= '    <title>'.htmlspecialchars($channelTitle)."</title>\n";
        $xml .= '    <link>'.htmlspecialchars($channelLink)."</link>\n";
        $xml .= '    <description>'.htmlspecialchars($channelDescription)."</description>\n";
        $xml .= '    <language>'.htmlspecialchars((string) ($locale ?? 'en'))."</language>\n";
        $xml .= '    <lastBuildDate>'.now()->toRssString()."</lastBuildDate>\n";
        $xml .= '    <atom:link href="'.htmlspecialchars(url()->current()).'" rel="self" type="application/rss+xml" />'."\n";

        foreach ($posts as $post) {
            $translation = $post->translation($locale);
            if ($translation === null) {
                continue;
            }
            $url = url(($locale ? "/{$locale}" : '')."/{$translation->slug}");

            $xml .= "    <item>\n";
            $xml .= '      <title>'.htmlspecialchars($translation->title)."</title>\n";
            $xml .= '      <link>'.htmlspecialchars($url)."</link>\n";
            $xml .= '      <guid isPermaLink="true">'.htmlspecialchars($url)."</guid>\n";
            $xml .= '      <pubDate>'.($post->published_at?->toRssString() ?? now()->toRssString())."</pubDate>\n";

            if ($translation->excerpt) {
                $xml .= '      <description>'.htmlspecialchars((string) $translation->excerpt)."</description>\n";
            }

            if ($post->author?->name) {
                $xml .= '      <dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">'.htmlspecialchars($post->author->name)."</dc:creator>\n";
            }

            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n</rss>\n";

        return new Response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }
}
