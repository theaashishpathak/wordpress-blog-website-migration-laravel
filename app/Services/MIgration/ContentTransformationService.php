<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ContentTransformationService
 *
 * Transforms legacy WordPress post/page HTML into clean, modern Laravel-compatible HTML.
 *
 * Responsibilities:
 * 1. Rewrite WordPress media URLs (src, srcset, data-src, href) from old domain to local Laravel media storage.
 * 2. Rewrite internal links (blogchowk.com/article-slug -> /en/article-slug or /{slug}).
 * 3. Convert WordPress shortcodes ([caption], [gallery], [audio], [video], [embed]) into semantic HTML.
 * 4. Safely strip or clean broken / orphaned WordPress plugin shortcodes without breaking layout.
 * 5. Preserve external domains and valid embeds completely.
 */
class ContentTransformationService
{
    private string $oldDomain = 'blogchowk.com';

    /**
     * Map of WP attachment GUID / path -> local Laravel media URL / relative path
     * Cached in memory for high-performance chunked transformations.
     *
     * @var array<string, string>
     */
    private array $mediaUrlMap = [];

    /**
     * Map of WP post slug / old path -> Laravel new slug / path
     *
     * @var array<string, string>
     */
    private array $postSlugMap = [];

    public function __construct()
    {
        $this->oldDomain = config('services.wordpress.domain', 'blogchowk.com');
    }

    /**
     * Initialize media and post URL lookup maps.
     */
    public function loadMaps(): void
    {
        if (empty($this->mediaUrlMap)) {
            $mediaRows = DB::table('media')
                ->select('id', 'path', 'source_url', 'disk')
                ->get();

            foreach ($mediaRows as $m) {
                $laravelUrl = Str::startsWith($m->path, ['http://', 'https://', '//'])
                    ? $m->path
                    : Storage::disk($m->disk ?: 'public')->url($m->path);

                if ($m->source_url) {
                    $this->mediaUrlMap[$m->source_url] = $laravelUrl;
                    // Also map scheme-agnostic and path-only variants
                    $parsedSource = parse_url($m->source_url, PHP_URL_PATH);
                    if ($parsedSource) {
                        $this->mediaUrlMap[$parsedSource] = $laravelUrl;
                    }
                }
            }
        }

        if (empty($this->postSlugMap)) {
            $postRows = DB::table('post_translations')
                ->select('slug')
                ->get();

            foreach ($postRows as $p) {
                $this->postSlugMap[$p->slug] = $p->slug;
            }
        }
    }

    /**
     * Main transformation method for post / page HTML content.
     */
    public function transform(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $this->loadMaps();

        // 1. Convert WordPress Shortcodes
        $html = $this->transformShortcodes($html);

        // 2. Transform Media URLs (src, srcset, data-src, background-image)
        $html = $this->transformMediaUrls($html);

        // 3. Transform Internal WordPress Post / Category Links
        $html = $this->transformInternalLinks($html);

        // 4. Clean up any remaining broken shortcodes / empty legacy tags
        $html = $this->cleanupLegacyMarkup($html);

        return trim($html);
    }

    /**
     * Transform WordPress shortcodes to semantic HTML.
     */
    public function transformShortcodes(string $html): string
    {
        // 1. [caption id="..." align="..." width="..." caption="..."]<img .../>Caption text[/caption]
        $html = preg_replace_callback(
            '/\[caption([^\]]*)\](.*?)\[\/caption\]/is',
            function ($matches) {
                $attrs = $matches[1];
                $inner = $matches[2];

                // Extract caption attribute or inner caption text
                $captionText = '';
                if (preg_match('/caption=["\'](.*?)["\']/i', $attrs, $capMatch)) {
                    $captionText = $capMatch[1];
                }

                // If inner contains an <img> tag followed by plain text
                if (preg_match('/(<img[^>]+>)(.*)/is', $inner, $imgParts)) {
                    $imgHtml = $imgParts[1];
                    $textAfter = trim($imgParts[2]);
                    if ($textAfter !== '') {
                        $captionText = $textAfter;
                    }
                    return '<figure class="wp-block-image my-6">' . $imgHtml . ($captionText !== '' ? '<figcaption class="mt-2 text-center text-sm italic text-slate-500">' . htmlspecialchars(strip_tags($captionText)) . '</figcaption>' : '') . '</figure>';
                }

                return '<figure class="wp-block-image my-6">' . $inner . '</figure>';
            },
            $html
        );

        // 2. [gallery ids="1,2,3"...]
        $html = preg_replace_callback(
            '/\[gallery([^\]]*)\]/is',
            function ($matches) {
                $attrs = $matches[1];
                if (preg_match('/ids=["\'](.*?)["\']/i', $attrs, $idMatch)) {
                    $ids = array_filter(array_map('intval', explode(',', $idMatch[1])));
                    if (!empty($ids)) {
                        $mediaItems = DB::table('media')->whereIn('id', $ids)->get();
                        $galleryHtml = '<div class="grid grid-cols-2 md:grid-cols-3 gap-4 my-6">';
                        foreach ($mediaItems as $media) {
                            $url = Str::startsWith($media->path, ['http://', 'https://', '//'])
                                ? $media->path
                                : Storage::disk($media->disk ?: 'public')->url($media->path);
                            $alt = htmlspecialchars($media->alt_text ?: ($media->original_filename ?: 'Gallery Image'));
                            $galleryHtml .= '<div class="overflow-hidden rounded-xl shadow-sm"><img src="' . $url . '" alt="' . $alt . '" class="w-full h-48 object-cover transition duration-300 hover:scale-105" loading="lazy"></div>';
                        }
                        $galleryHtml .= '</div>';
                        return $galleryHtml;
                    }
                }
                return '';
            },
            $html
        );

        // 3. [embed]https://...[/embed] or [video src="..."] / [audio src="..."]
        $html = preg_replace_callback(
            '/\[embed\](.*?)\[\/embed\]/is',
            function ($matches) {
                $url = trim($matches[1]);
                if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                    return '<div class="aspect-video my-6 overflow-hidden rounded-2xl"><iframe src="' . htmlspecialchars($url) . '" class="w-full h-full" frameborder="0" allowfullscreen></iframe></div>';
                }
                return '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener" class="text-emerald-600 underline">' . htmlspecialchars($url) . '</a>';
            },
            $html
        );

        // 4. Strip orphaned / unhandled shortcodes [shortcode_name ... /] or [shortcode]...[/shortcode]
        $html = preg_replace('/\[\/?([a-zA-Z0-9_\-]+)(?:\s+[^\]]*)?\]/s', '', $html);

        return $html;
    }

    /**
     * Transform all media URLs in HTML to local storage URLs.
     */
    public function transformMediaUrls(string $html): string
    {
        // 1. Replace full domain URLs pointing to wp-content/uploads/
        // Matches: https://blogchowk.com/wp-content/uploads/2023/05/image.jpg
        $domainRegex = '/https?:\/\/(?:www\.)?' . preg_quote($this->oldDomain, '/') . '(\/wp-content\/uploads\/[^\s"\'<>]+)/i';
        $html = preg_replace_callback($domainRegex, function ($matches) {
            $wpUploadPath = $matches[1];
            return $this->resolveLocalMediaUrl($wpUploadPath, $matches[0]);
        }, $html);

        // 2. Replace relative wp-content/uploads/ URLs
        $relativeRegex = '/(["\'])\/wp-content\/uploads\/([^\s"\'<>]+)(["\'])/i';
        $html = preg_replace_callback($relativeRegex, function ($matches) {
            $quote1 = $matches[1];
            $relativePath = '/wp-content/uploads/' . $matches[2];
            $quote2 = $matches[3];
            $localUrl = $this->resolveLocalMediaUrl($relativePath, $relativePath);
            return $quote1 . $localUrl . $quote2;
        }, $html);

        // 3. Handle srcset attributes specially to rewrite each URL candidate
        $html = preg_replace_callback('/(srcset=["\'])(.*?)(["\'])/is', function ($matches) {
            $prefix = $matches[1];
            $srcsetValue = $matches[2];
            $suffix = $matches[3];

            $entries = explode(',', $srcsetValue);
            $transformedEntries = [];

            foreach ($entries as $entry) {
                $entry = trim($entry);
                if ($entry === '') {
                    continue;
                }

                $parts = preg_split('/\s+/', $entry, 2);
                $url = $parts[0] ?? '';
                $descriptor = isset($parts[1]) ? ' ' . $parts[1] : '';

                // Transform URL
                if (str_contains($url, $this->oldDomain) || str_contains($url, '/wp-content/uploads/')) {
                    $url = $this->resolveLocalMediaUrl($url, $url);
                }

                $transformedEntries[] = $url . $descriptor;
            }

            return $prefix . implode(', ', $transformedEntries) . $suffix;
        }, $html);

        return $html;
    }

    /**
     * Resolve a WordPress image path or URL to the corresponding local Laravel storage URL.
     */
    private function resolveLocalMediaUrl(string $wpPathOrUrl, string $fallback): string
    {
        // Check direct map
        if (isset($this->mediaUrlMap[$wpPathOrUrl])) {
            return $this->mediaUrlMap[$wpPathOrUrl];
        }

        $parsedPath = parse_url($wpPathOrUrl, PHP_URL_PATH) ?? $wpPathOrUrl;
        if (isset($this->mediaUrlMap[$parsedPath])) {
            return $this->mediaUrlMap[$parsedPath];
        }

        // If it's a resized image (e.g. image-300x200.jpg), find the base image in map
        $basePath = preg_replace('/-\d+x\d+(\.[a-zA-Z0-9]+)$/', '$1', $parsedPath);
        if ($basePath && isset($this->mediaUrlMap[$basePath])) {
            return $this->mediaUrlMap[$basePath];
        }

        // Standard Laravel storage path if file was migrated to storage/media/
        $cleanFilename = basename($parsedPath);
        $standardPath = '/storage/media/' . $cleanFilename;

        return $standardPath;
    }

    /**
     * Transform internal WordPress links to Laravel routes.
     */
    public function transformInternalLinks(string $html): string
    {
        // 1. Root domain links e.g. <a href="https://blogchowk.com/" ...> or href="//blogchowk.com"
        $rootDomainRegex = '/<a\s+([^>]*?)href=["\'](?:https?:)?\/\/(?:www\.)?' . preg_quote($this->oldDomain, '/') . '\/?["\']([^>]*?)>/i';
        $html = preg_replace_callback($rootDomainRegex, function ($matches) {
            return '<a ' . $matches[1] . 'href="/"' . $matches[2] . '>';
        }, $html);

        // 2. Matches <a href="https://blogchowk.com/some-slug/"> or <a href="//blogchowk.com/category/tech/">
        $internalLinkRegex = '/<a\s+([^>]*?)href=["\'](?:(?:https?:)?\/\/(?:www\.)?' . preg_quote($this->oldDomain, '/') . ')?(\/[a-zA-Z0-9_\-\/]+(?:\/|\.html)?)(\?[^"\']*)?["\']([^>]*?)>/i';

        $html = preg_replace_callback($internalLinkRegex, function ($matches) {
            $beforeAttrs = $matches[1];
            $path = trim($matches[2], '/');
            $query = $matches[3] ?? '';
            $afterAttrs = $matches[4];

            // Ignore uploads or admin links
            if (str_starts_with($path, 'wp-content') || str_starts_with($path, 'wp-admin') || str_starts_with($path, 'wp-includes')) {
                return $matches[0];
            }

            if ($path === '' || $path === '/') {
                return '<a ' . $beforeAttrs . 'href="/' . $query . '"' . $afterAttrs . '>';
            }

            $segments = explode('/', $path);

            // Category link: /category/news/
            if (count($segments) >= 2 && $segments[0] === 'category') {
                $catSlug = end($segments);
                return '<a ' . $beforeAttrs . 'href="/category/' . $catSlug . $query . '"' . $afterAttrs . '>';
            }

            // Tag link: /tag/tech/
            if (count($segments) >= 2 && $segments[0] === 'tag') {
                $tagSlug = end($segments);
                return '<a ' . $beforeAttrs . 'href="/tags/' . $tagSlug . $query . '"' . $afterAttrs . '>';
            }

            // Author link: /author/john/
            if (count($segments) >= 2 && $segments[0] === 'author') {
                $authorSlug = end($segments);
                $authorId = DB::table('users')->where('public_slug', $authorSlug)->orWhere('name', $authorSlug)->value('id');
                if ($authorId) {
                    return '<a ' . $beforeAttrs . 'href="/author/' . $authorId . $query . '"' . $afterAttrs . '>';
                }
            }

            // Post or Page single slug (e.g. "chatgpt-prompts" or "about-us")
            $lastSlug = end($segments);
            $lastSlug = preg_replace('/\.html?$/i', '', $lastSlug);

            return '<a ' . $beforeAttrs . 'href="/' . $lastSlug . $query . '"' . $afterAttrs . '>';
        }, $html);

        return $html;
    }

    /**
     * Clean up any empty paragraphs or leftover artifacts from WordPress.
     */
    private function cleanupLegacyMarkup(string $html): string
    {
        // Remove empty paragraphs <p></p> or <p>&nbsp;</p>
        $html = preg_replace('/<p>\s*(?:&nbsp;|\s)*<\/p>/i', '', $html);

        // Remove wp:gutenberg comment blocks like <!-- wp:paragraph -->
        $html = preg_replace('/<!--\s*\/?wp:[a-zA-Z0-9_\-\/]+(?:\s+.*?)?\s*-->/s', '', $html);

        // Clean up any artifacts like ":- /" or ":- / " from prior transformations
        $html = preg_replace('/(:-\s*)\/(?!\w)/i', '$1' . config('app.name', 'BlogChowk'), $html);

        return $html;
    }
}
