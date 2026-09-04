<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Homepage;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Homepage Customizer')]
class Index extends Component
{
    public string $activeTab = 'hero';

    // ── Hero Section ──────────────────────────────────────────────────────────
    public bool $hero_enabled = true;
    public string $hero_title = '';
    public string $hero_subtitle = '';
    public string $trending_topics_label = '';
    public string $trending_mode = 'auto'; // 'auto' | 'manual'
    public array $selected_categories = [];

    // ── Story Stream Feed ─────────────────────────────────────────────────────
    public int $posts_per_page = 10;
    public bool $show_category_badge = true;
    public bool $show_featured_badge = true;
    public bool $show_author = true;
    public bool $show_date = true;
    public bool $show_excerpt = true;
    public string $discover_button_text = '';

    // ── Sidebar: About Widget ─────────────────────────────────────────────────
    public bool $about_enabled = true;
    public string $about_title = 'ABOUT';
    public string $about_mode = 'author'; // 'author' | 'custom'
    public ?int $about_author_id = null;
    public string $about_custom_name = '';
    public string $about_custom_badge = '';
    public string $about_custom_bio = '';
    public string $about_custom_location = '';
    public ?string $about_custom_avatar = null;
    public string $about_twitter = '';
    public string $about_facebook = '';
    public string $about_instagram = '';
    public string $about_linkedin = '';

    // ── Sidebar: Featured Post Widget ─────────────────────────────────────────
    public bool $featured_enabled = true;
    public string $featured_title = 'FEATURED POSTS';
    public string $featured_mode = 'auto'; // 'auto' | 'manual'
    public ?int $featured_post_id = null;

    // ── Sidebar: Top Categories Widget ────────────────────────────────────────
    public bool $categories_enabled = true;
    public string $categories_title = 'TOP CATEGORIES';
    public int $categories_count = 5;

    // ── Sidebar: Popular Topics / Tags ────────────────────────────────────────
    public bool $tags_enabled = true;
    public string $tags_title = 'POPULAR TOPICS';
    public int $tags_count = 10;

    // ── Sidebar: Editorial Picks Widget ───────────────────────────────────────
    public bool $editorial_enabled = true;
    public string $editorial_title = 'EDITORIAL PICKS';
    public string $editorial_mode = 'trending'; // 'trending' | 'latest'
    public int $editorial_count = 3;

    // ── Newsletter Section ────────────────────────────────────────────────────
    public bool $newsletter_enabled = true;
    public string $newsletter_title = '';
    public string $newsletter_subtitle = '';
    public string $newsletter_button_text = '';
    public string $newsletter_badge_text = '';

    public function mount(SettingService $settings): void
    {
        // Hero
        $this->hero_enabled = (bool) $settings->get('homepage.hero_enabled', true);
        $this->hero_title = (string) $settings->get('homepage.hero_title', 'Heartfelt Reflections: Stories of Love, Loss, and Growth');
        $this->hero_subtitle = (string) $settings->get('homepage.hero_subtitle', 'Welcome to ultimate source for fresh perspectives! Explore curated content to enlighten, entertain and engage global readers.');
        $this->trending_topics_label = (string) $settings->get('homepage.trending_topics_label', 'EXPLORE TRENDING TOPICS');
        $this->trending_mode = (string) $settings->get('homepage.trending_mode', 'auto');
        $this->selected_categories = (array) $settings->get('homepage.selected_categories', []);

        // Feed
        $this->posts_per_page = (int) $settings->get('homepage.posts_per_page', 10);
        $this->show_category_badge = (bool) $settings->get('homepage.show_category_badge', true);
        $this->show_featured_badge = (bool) $settings->get('homepage.show_featured_badge', true);
        $this->show_author = (bool) $settings->get('homepage.show_author', true);
        $this->show_date = (bool) $settings->get('homepage.show_date', true);
        $this->show_excerpt = (bool) $settings->get('homepage.show_excerpt', true);
        $this->discover_button_text = (string) $settings->get('homepage.discover_button_text', 'Discover More');

        // About Widget
        $this->about_enabled = (bool) $settings->get('homepage.about_enabled', true);
        $this->about_title = (string) $settings->get('homepage.about_title', 'ABOUT');
        $this->about_mode = (string) $settings->get('homepage.about_mode', 'author');
        $this->about_author_id = $settings->get('homepage.about_author_id') ? (int) $settings->get('homepage.about_author_id') : null;
        $this->about_custom_name = (string) $settings->get('homepage.about_custom_name', 'Ethan Caldwell');
        $this->about_custom_badge = (string) $settings->get('homepage.about_custom_badge', 'REFLECTIVE BLOGGER');
        $this->about_custom_bio = (string) $settings->get('homepage.about_custom_bio', 'Sharing thoughtful insights and reflections on technology, culture, and personal growth. Exploring intersections of creativity and experience.');
        $this->about_custom_location = (string) $settings->get('homepage.about_custom_location', 'Paris, France');
        $this->about_custom_avatar = $settings->get('homepage.about_custom_avatar');
        $this->about_twitter = (string) $settings->get('homepage.about_twitter', '#');
        $this->about_facebook = (string) $settings->get('homepage.about_facebook', '#');
        $this->about_instagram = (string) $settings->get('homepage.about_instagram', '#');
        $this->about_linkedin = (string) $settings->get('homepage.about_linkedin', '#');

        // Featured Posts Widget
        $this->featured_enabled = (bool) $settings->get('homepage.featured_enabled', true);
        $this->featured_title = (string) $settings->get('homepage.featured_title', 'FEATURED POSTS');
        $this->featured_mode = (string) $settings->get('homepage.featured_mode', 'auto');
        $this->featured_post_id = $settings->get('homepage.featured_post_id') ? (int) $settings->get('homepage.featured_post_id') : null;

        // Categories Widget
        $this->categories_enabled = (bool) $settings->get('homepage.categories_enabled', true);
        $this->categories_title = (string) $settings->get('homepage.categories_title', 'TOP CATEGORIES');
        $this->categories_count = (int) $settings->get('homepage.categories_count', 5);

        // Tags Widget
        $this->tags_enabled = (bool) $settings->get('homepage.tags_enabled', true);
        $this->tags_title = (string) $settings->get('homepage.tags_title', 'POPULAR TOPICS');
        $this->tags_count = (int) $settings->get('homepage.tags_count', 10);

        // Editorial Widget
        $this->editorial_enabled = (bool) $settings->get('homepage.editorial_enabled', true);
        $this->editorial_title = (string) $settings->get('homepage.editorial_title', 'EDITORIAL PICKS');
        $this->editorial_mode = (string) $settings->get('homepage.editorial_mode', 'trending');
        $this->editorial_count = (int) $settings->get('homepage.editorial_count', 3);

        // Newsletter Section
        $this->newsletter_enabled = (bool) $settings->get('homepage.newsletter_enabled', true);
        $this->newsletter_title = (string) $settings->get('homepage.newsletter_title', 'Subscribe to our Newsletter');
        $this->newsletter_subtitle = (string) $settings->get('homepage.newsletter_subtitle', 'Subscribe to our email newsletter to get the latest posts delivered right to your email.');
        $this->newsletter_button_text = (string) $settings->get('homepage.newsletter_button_text', 'Subscribe');
        $this->newsletter_badge_text = (string) $settings->get('homepage.newsletter_badge_text', 'Pure inspiration, zero spam ✨');
    }

    public function save(SettingService $settings): void
    {
        $group = 'homepage-settings';

        // Hero
        $settings->set('homepage.hero_enabled', $this->hero_enabled, $group, 'boolean', false);
        $settings->set('homepage.hero_title', $this->hero_title, $group, 'string', false);
        $settings->set('homepage.hero_subtitle', $this->hero_subtitle, $group, 'string', false);
        $settings->set('homepage.trending_topics_label', $this->trending_topics_label, $group, 'string', false);
        $settings->set('homepage.trending_mode', $this->trending_mode, $group, 'string', false);
        $settings->set('homepage.selected_categories', $this->selected_categories, $group, 'json', false);

        // Feed
        $settings->set('homepage.posts_per_page', $this->posts_per_page, $group, 'integer', false);
        $settings->set('homepage.show_category_badge', $this->show_category_badge, $group, 'boolean', false);
        $settings->set('homepage.show_featured_badge', $this->show_featured_badge, $group, 'boolean', false);
        $settings->set('homepage.show_author', $this->show_author, $group, 'boolean', false);
        $settings->set('homepage.show_date', $this->show_date, $group, 'boolean', false);
        $settings->set('homepage.show_excerpt', $this->show_excerpt, $group, 'boolean', false);
        $settings->set('homepage.discover_button_text', $this->discover_button_text, $group, 'string', false);

        // About Widget
        $settings->set('homepage.about_enabled', $this->about_enabled, $group, 'boolean', false);
        $settings->set('homepage.about_title', $this->about_title, $group, 'string', false);
        $settings->set('homepage.about_mode', $this->about_mode, $group, 'string', false);
        $settings->set('homepage.about_author_id', $this->about_author_id, $group, 'integer', false);
        $settings->set('homepage.about_custom_name', $this->about_custom_name, $group, 'string', false);
        $settings->set('homepage.about_custom_badge', $this->about_custom_badge, $group, 'string', false);
        $settings->set('homepage.about_custom_bio', $this->about_custom_bio, $group, 'string', false);
        $settings->set('homepage.about_custom_location', $this->about_custom_location, $group, 'string', false);
        $settings->set('homepage.about_custom_avatar', $this->about_custom_avatar, $group, 'string', false);
        $settings->set('homepage.about_twitter', $this->about_twitter, $group, 'string', false);
        $settings->set('homepage.about_facebook', $this->about_facebook, $group, 'string', false);
        $settings->set('homepage.about_instagram', $this->about_instagram, $group, 'string', false);
        $settings->set('homepage.about_linkedin', $this->about_linkedin, $group, 'string', false);

        // Featured Widget
        $settings->set('homepage.featured_enabled', $this->featured_enabled, $group, 'boolean', false);
        $settings->set('homepage.featured_title', $this->featured_title, $group, 'string', false);
        $settings->set('homepage.featured_mode', $this->featured_mode, $group, 'string', false);
        $settings->set('homepage.featured_post_id', $this->featured_post_id, $group, 'integer', false);

        // Categories Widget
        $settings->set('homepage.categories_enabled', $this->categories_enabled, $group, 'boolean', false);
        $settings->set('homepage.categories_title', $this->categories_title, $group, 'string', false);
        $settings->set('homepage.categories_count', $this->categories_count, $group, 'integer', false);

        // Tags Widget
        $settings->set('homepage.tags_enabled', $this->tags_enabled, $group, 'boolean', false);
        $settings->set('homepage.tags_title', $this->tags_title, $group, 'string', false);
        $settings->set('homepage.tags_count', $this->tags_count, $group, 'integer', false);

        // Editorial Widget
        $settings->set('homepage.editorial_enabled', $this->editorial_enabled, $group, 'boolean', false);
        $settings->set('homepage.editorial_title', $this->editorial_title, $group, 'string', false);
        $settings->set('homepage.editorial_mode', $this->editorial_mode, $group, 'string', false);
        $settings->set('homepage.editorial_count', $this->editorial_count, $group, 'integer', false);

        // Newsletter
        $settings->set('homepage.newsletter_enabled', $this->newsletter_enabled, $group, 'boolean', false);
        $settings->set('homepage.newsletter_title', $this->newsletter_title, $group, 'string', false);
        $settings->set('homepage.newsletter_subtitle', $this->newsletter_subtitle, $group, 'string', false);
        $settings->set('homepage.newsletter_button_text', $this->newsletter_button_text, $group, 'string', false);
        $settings->set('homepage.newsletter_badge_text', $this->newsletter_badge_text, $group, 'string', false);

        // Flush cache so changes take effect immediately
        $settings->reloadCache();

        $this->dispatch('toast.success', message: 'Homepage customizer settings saved successfully.');
        session()->flash('success', 'Homepage customizer settings saved successfully.');
    }

    public function render(): View
    {
        $categories = Category::query()
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->get();

        $authors = User::query()
            ->whereHas('posts')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $featuredPosts = Post::query()
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->where('is_featured', true)
            ->with('translations')
            ->latest('published_at')
            ->take(20)
            ->get();

        return view('livewire.admin.homepage.index', [
            'allCategories' => $categories,
            'authors' => $authors,
            'featuredPosts' => $featuredPosts,
        ]);
    }
}
