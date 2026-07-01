<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Models\Post;
use App\Models\WpPosts;
use App\Support\LocaleResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Public homepage — multi-section magazine layout.
 *
 * Sections (loaded as separate computed queries so each one stays
 * cacheable independently):
 *   - Breaking — newest is_breaking posts
 *   - Trending — newest is_trending posts
 *   - Featured — newest is_featured posts
 *   - Latest  — newest published posts
 *
 * Each section is locale-aware: posts must have a translation row in
 * the current locale (or fall back to the default locale).
 */
#[Layout('frontend.layouts.app')]
#[Title('Home')]
class Home extends Component
{
    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function breaking(): Collection
    {
        return $this->basePublishedQuery()
            ->where('is_breaking', true)
            ->where(function ($q): void {
                $q->whereNull('breaking_expires_at')
                    ->orWhere('breaking_expires_at', '>', now());
            })
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function trending(): Collection
    {
        return $this->basePublishedQuery()
            ->where('is_trending', true)
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function featured(): Collection
    {
        return $this->basePublishedQuery()
            ->where('is_featured', true)
            ->limit(4)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function editorsPick(): Collection
    {
        return $this->basePublishedQuery()
            ->where('is_editors_pick', true)
            ->limit(3)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function latest(): Collection
    {
        return $this->basePublishedQuery()
            ->limit(12)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Post>
     */
    private function basePublishedQuery()
    {
        return Post::query()
            ->with([
                'translations',
                'author:id,name',
                'featuredImage:id,disk,path,mime_type,alt_text',
                'category:id,icon',
                'category.translations',
            ])
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function render(): View
    {
        return view('livewire.frontend.home');
    }


        // #[Computed]
        // public function latestWordPressPost()
        // {
        //     return WpPosts::published()
        //         ->latest('post_date')
        //         ->first();
        // }
}
