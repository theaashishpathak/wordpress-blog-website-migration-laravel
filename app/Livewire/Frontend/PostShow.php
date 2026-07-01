<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\SeoMeta;
use App\Support\LocaleResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Public single-post page.
 *
 * Resolves the post via the translation's slug + active locale. View
 * count is incremented once per request (debounced via session token
 * so repeated reloads from the same visitor don't spam counter).
 */
#[Layout('frontend.layouts.app')]
class PostShow extends Component
{
    public Post $post;

    public PostTranslation $translation;

    /**
     * Resolve the post from URL slug + current locale. Slug is per-language
     * (stored on post_translations) so we look that up first, then load
     * the parent post. 404 if no published/translated match. Also bumps
     * the per-visitor view count via FrontendDispatcher.
     *
     * All params are optional so tests can construct the component with
     * $post / $translation already populated via Livewire::test(...).
     * In that case we skip the lookup + view count + history tracking.
     */
    public function mount(
        ?\Illuminate\Http\Request $request = null,
        ?string $locale = null,
        ?string $slug = null,
    ): void {
        if (isset($this->post, $this->translation)) {
            return;
        }

        abort_if($slug === null, 404);

        $request = $request ?? request();
        $languageId = app(\App\Support\LocaleResolver::class)->current()?->id;

        $translation = PostTranslation::query()
            ->with('post.translations', 'post.author', 'post.featuredImage', 'post.category', 'language')
            ->when($languageId !== null, fn ($q) => $q->where('language_id', $languageId))
            ->where('slug', $slug)
            ->first();

        if ($translation === null
            || $translation->post === null
            || $translation->post->status !== \App\Enums\PostStatus::Published
            || $translation->post->published_at === null
            || $translation->post->published_at->gt(now())
        ) {
            abort(404);
        }

        \App\Http\Controllers\Frontend\FrontendDispatcher::bumpViewCount($request, $translation->post);

        // Track per-user reading history when a visitor is logged in. We use
        // the Action layer so the same logic is reusable from API endpoints
        // or background jobs (e.g., "you finished reading" trackers).
        $user = $request->user();
        if ($user !== null && $user->portal_type === 'visitor') {
            app(\App\Actions\Visitor\ReadingHistory\RecordReadAction::class)->handle(
                user: $user,
                post: $translation->post,
            );
        }

        $this->post = $translation->post;
        $this->translation = $translation;
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function relatedPosts(): Collection
    {
        $categoryId = $this->post->category_id;

        return Post::query()
            ->with(['translations', 'featuredImage:id,disk,path,mime_type,alt_text', 'author:id,name'])
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->whereKeyNot($this->post->id)
            ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();
    }

    /**
     * @return Collection<int, \App\Models\Tag>
     */
    #[Computed]
    public function tags(): Collection
    {
        return $this->post->tags()->with('translations')->get();
    }

    /**
     * Premium paywall: returns true when the post is flagged premium
     * AND the current viewer doesn't have access (not logged in, or
     * logged in but without a `premium.access` permission).
     *
     * Subscription tiers + Stripe wiring land in Phase 7. For now,
     * `premium.access` is the manual gate admins can grant.
     */
    #[Computed]
    public function isPaywalled(): bool
    {
        if (! (bool) $this->post->is_premium) {
            return false;
        }

        $user = auth()->user();

        if ($user === null) {
            return true;
        }

        return ! $user->can('premium.access');
    }

    /**
     * Truncate content to N words when paywalled. Strips HTML so the
     * teaser is plain text — avoids leaking the rest of the article via
     * an unclosed tag.
     */
    #[Computed]
    public function paywallTeaser(): string
    {
        $plain = strip_tags((string) $this->translation->content);
        $words = preg_split('/\s+/', trim($plain), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_slice($words, 0, 80));
    }

    public function shareUrl(string $network): string
    {
        $url = urlencode(url()->current());
        $title = urlencode($this->translation->title);

        return match ($network) {
            'twitter' => "https://twitter.com/intent/tweet?url={$url}&text={$title}",
            'facebook' => "https://www.facebook.com/sharer/sharer.php?u={$url}",
            'linkedin' => "https://www.linkedin.com/sharing/share-offsite/?url={$url}",
            'whatsapp' => "https://wa.me/?text={$title}%20{$url}",
            default => '#',
        };
    }

    public function render(): View
    {
        $seo = SeoMeta::query()
            ->where('seoable_type', $this->post->getMorphClass())
            ->where('seoable_id', $this->post->id)
            ->forLocale($this->translation->language_id)
            ->first();

        $featured = $this->post->featuredImage;
        $author = $this->post->author;

        return view('livewire.frontend.post-show', [
            'metaTitle' => $this->translation->meta_title ?: $this->translation->title,
            'metaDescription' => $this->translation->meta_description
                ?: $this->translation->excerpt
                ?: \Illuminate\Support\Str::limit(strip_tags((string) $this->translation->content), 160),
            'ogImage' => $featured?->url(),
            'jsonLd' => $this->buildArticleJsonLd($seo, $featured, $author),
        ]);
    }

    private function buildArticleJsonLd(?SeoMeta $seo, ?\App\Models\Media $featured, ?\App\Models\User $author): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $seo?->schema_type ?: SeoMeta::SCHEMA_ARTICLE,
            'headline' => $this->translation->title,
            'datePublished' => $this->post->published_at?->toIso8601String(),
            'dateModified' => $this->post->updated_at?->toIso8601String(),
            'url' => url()->current(),
            'inLanguage' => $this->translation->language?->code,
        ];

        if ($this->translation->meta_description) {
            $schema['description'] = $this->translation->meta_description;
        }

        if ($author) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $author->name,
            ];
        }

        if ($featured) {
            $schema['image'] = $featured->url();
        }

        return $schema;
    }
}
