<?php

use App\Http\Controllers\Auth\ProfileAvatarController;
use App\Http\Controllers\Frontend\FeedController;
use App\Http\Controllers\Frontend\RobotsController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Livewire\Admin\Dashboards\MyDashboard;
use App\Livewire\Admin\Settings\SettingsIndex;
use App\Livewire\Frontend\AuthorShow;
use App\Livewire\Frontend\CategoryShow;
use App\Livewire\Frontend\Home;
use App\Livewire\Frontend\PageShow;
use App\Livewire\Frontend\PostShow;
use App\Livewire\Frontend\Search;
use App\Livewire\Frontend\TagShow;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

// -------------------------------------------------------------------------
// NewsPilot AI — Frontend (Phase 5)
// -------------------------------------------------------------------------

// Sitemap + robots + feeds — always at root regardless of locale
Route::get('/sitemap.xml', SitemapController::class)->name('frontend.sitemap');
Route::get('/robots.txt', RobotsController::class)->name('frontend.robots');
Route::get('/feed.xml', [FeedController::class, 'global'])->name('frontend.feed.rss');
Route::get('/category/{slug}.rss', [FeedController::class, 'category'])->name('frontend.feed.category');

// Newsletter confirm + unsubscribe — locale-agnostic since tokens are unique
Route::get('/newsletter/confirm/{token}', [\App\Http\Controllers\Frontend\NewsletterController::class, 'confirm'])
    ->where('token', '[A-Za-z0-9]{20,80}')
    ->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [\App\Http\Controllers\Frontend\NewsletterController::class, 'unsubscribe'])
    ->where('token', '[A-Za-z0-9]{20,80}')
    ->name('newsletter.unsubscribe');

// Ad click tracking — short flat URL outside the locale group so the
// regex on /{slug} doesn't accidentally catch /ads/click/123.
Route::get('/ads/click/{creative}', \App\Http\Controllers\Frontend\AdClickController::class)
    ->whereNumber('creative')
    ->name('ads.click');

// Frontend group with optional /{locale} prefix. The SetLocale middleware
// already picks up the `locale` route parameter; the regex constraint
// ensures a 2–5 char code matches "en" / "bn" / "en-US" but not "post" etc.
Route::group([
    'prefix' => '{locale?}',
    'where' => ['locale' => '[a-z]{2}(-[A-Z]{2})?'],
], function (): void {
    Route::get('/', Home::class)->name('frontend.home');
    Route::get('/search', Search::class)->name('frontend.search');

    // Locale-prefixed RSS feeds. The root-level /feed.xml + /category/{slug}.rss
    // routes above remain for backward-compat with consumers that don't pass
    // a locale; here we expose the same handlers under the locale prefix so
    // /en/feed.xml etc. resolve too. The SetLocale middleware picks up the
    // {locale} param so the FeedController already gets the right language.
    Route::get('/feed.xml', [FeedController::class, 'global'])->name('frontend.feed.rss.localized');
    Route::get('/category/{slug}.rss', [FeedController::class, 'category'])->name('frontend.feed.category.localized');

    // Author profile — /author/{id}
    Route::get('/author/{user}', AuthorShow::class)
        ->whereNumber('user')
        ->name('frontend.author');

    // Tag via Livewire
    Route::get('/tags/{tag:slug}', TagShow::class)->name('frontend.tag');

    // Category, Page, and Post are routed directly to the component class
    // so Livewire renders them as full-page responses (layout + scripts).
    //
    // Previously these used `Livewire::mount(...)` inside a closure which
    // emits ONLY the component fragment — no `<html>`, no `<head>`, no
    // CSS link. The slug-to-model lookup now lives in each component's
    // mount() method, keeping routes thin and the layout applied.
    Route::get('/category/{slug}', CategoryShow::class)->name('frontend.category');

    Route::get('/page/{slug}', PageShow::class)->name('frontend.page');

    Route::get('/{slug}', PostShow::class)
        ->where('slug', '[a-z0-9][a-z0-9-]*')
        ->name('frontend.post.show');
});

// -------------------------------------------------------------------------
// Authenticated app routes (admin/dashboard)
// -------------------------------------------------------------------------

Route::middleware('auth')->group(function (): void {
    Route::redirect('/profile', '/user/profile');
    Route::get('/user/profile', function (): View {
        $user = auth()->user();

        abort_unless($user !== null, 403);

        return view('auth.profile', [
            'activityLogs' => $user->profileActivityLogs()->latest()->paginate(10),
        ]);
    })->name('profile');
    Route::post('/user/profile/avatar', [ProfileAvatarController::class, 'update'])->name('profile.avatar.update');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    // /dashboard dispatches:
    //   - Visitor\Dashboard → for frontend readers (portal_type=visitor)
    //   - Author\Dashboard  → for content creators (has posts.create, not platform admin)
    //   - MyDashboard       → for everyone else (admin/staff)
    Route::get('/dashboard', function () {
        $user = auth()->user();

        // Visitors get redirected to their dedicated portal so the URL bar
        // makes the context clear (and we can switch layout/middleware later).
        if ($user?->portal_type === 'visitor') {
            return redirect()->route('visitor.dashboard');
        }

        $isAuthorOnly = $user?->can('posts.create')
            && ! ($user->hasRole(['Super Admin', 'Admin']));

        $class = $isAuthorOnly
            ? \App\Livewire\Author\Dashboard::class
            : MyDashboard::class;

        // Calling __invoke on the resolved Livewire component renders it as
        // a full-page route response (layout + scripts), which is what
        // Route::get($livewireClass) does under the hood. Using
        // Livewire::mount() here would only emit the component fragment.
        return app($class)();
    })->name('dashboard');
    Route::get('/dashboard/my', MyDashboard::class)->name('dashboard.my');
    Route::get('/dashboard/author', \App\Livewire\Author\Dashboard::class)
        ->middleware('permission:posts.create')
        ->name('dashboard.author');

    // Author profile editor
    Route::get('/author/profile', \App\Livewire\Author\Profile::class)
        ->middleware('permission:posts.create')
        ->name('author.profile');

    Route::get('/settings', SettingsIndex::class)->middleware('permission:settings.view')->name('settings');

    Route::get('/notifications', \App\Livewire\NotificationsIndex::class)
        ->middleware('permission:notifications.view')
        ->name('notifications.index');
});

// -------------------------------------------------------------------------
// Visitor portal — logged-in reader area (separate from admin/author).
// Locked to users with portal_type='visitor' via the `visitor` middleware
// alias declared in bootstrap/app.php. Staff get bounced back to /dashboard.
// -------------------------------------------------------------------------
Route::middleware(['auth', 'verified', 'visitor'])
    ->prefix('visitor')
    ->name('visitor.')
    ->group(function (): void {
        Route::get('/dashboard', \App\Livewire\Visitor\Dashboard::class)->name('dashboard');

        // ── My Library ───────────────────────────────────────────────────
        Route::get('/bookmarks',       \App\Livewire\Visitor\Bookmarks\Index::class)->name('bookmarks');
        Route::get('/reading-list',    \App\Livewire\Visitor\ReadingList\Index::class)->name('reading-list');
        Route::get('/reading-history', \App\Livewire\Visitor\ReadingHistory\Index::class)->name('reading-history');
        Route::get('/highlights',      \App\Livewire\Visitor\Highlights\Index::class)->name('highlights');

        // ── Engagement ───────────────────────────────────────────────────
        Route::get('/comments',  \App\Livewire\Visitor\Comments\Index::class)->name('comments');
        Route::get('/reactions', \App\Livewire\Visitor\Reactions\Index::class)->name('reactions');
        Route::get('/for-you',   \App\Livewire\Visitor\Recommendations\Index::class)->name('recommendations');

        // ── Following ────────────────────────────────────────────────────
        Route::get('/following/topics',  \App\Livewire\Visitor\Following\Topics::class)->name('following.topics');
        Route::get('/following/authors', \App\Livewire\Visitor\Following\Authors::class)->name('following.authors');
        Route::get('/following/users',   \App\Livewire\Visitor\Following\Users::class)->name('following.users');

        // ── Notifications ────────────────────────────────────────────────
        Route::get('/notifications', \App\Livewire\Visitor\Notifications\Index::class)->name('notifications');

        // ── Email & Newsletter ───────────────────────────────────────────
        Route::get('/email/preferences',   \App\Livewire\Visitor\Email\Preferences::class)->name('email.preferences');
        Route::get('/email/subscriptions', \App\Livewire\Visitor\Email\Subscriptions::class)->name('email.subscriptions');

        // ── Settings ─────────────────────────────────────────────────────
        Route::get('/settings/profile',    \App\Livewire\Visitor\Settings\Profile::class)->name('settings.profile');
        Route::get('/settings/security',   \App\Livewire\Visitor\Settings\Security::class)->name('settings.security');
        Route::get('/settings/sessions',   \App\Livewire\Visitor\Settings\Sessions::class)->name('settings.sessions');
        Route::get('/settings/activity',   \App\Livewire\Visitor\Settings\ActivityIndex::class)->name('settings.activity');
        Route::get('/settings/privacy',    \App\Livewire\Visitor\Settings\Privacy::class)->name('settings.privacy');
        Route::get('/settings/appearance', \App\Livewire\Visitor\Settings\Appearance::class)->name('settings.appearance');

        // ── Data & Privacy (GDPR) ────────────────────────────────────────
        Route::get('/data/export', \App\Livewire\Visitor\Data\Export::class)->name('data.export');
        Route::get('/data/delete', \App\Livewire\Visitor\Data\Delete::class)->name('data.delete');

        Route::get('/data/export/{export}/download',
            \App\Http\Controllers\Visitor\DataExportDownloadController::class
        )->name('data.export.download');
    });


require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
