<?php

use App\Http\Controllers\Admin\SettingsSaveController;
use App\Livewire\Admin\AssignPermission\Edit as AssignPermissionEdit;
use App\Livewire\Admin\AssignPermission\Index as AssignPermissionIndex;
use App\Livewire\Admin\AssignRole\Index as AssignRoleIndex;
use App\Livewire\Admin\Permission\Index as PermissionIndex;
use App\Livewire\Admin\PermissionGroup\Index as PermissionGroupIndex;
use App\Livewire\Admin\Role\Create as RoleCreate;
use App\Livewire\Admin\Role\Edit as RoleEdit;
use App\Livewire\Admin\Role\Index as RoleIndex;
use App\Livewire\Admin\Settings\SettingsGroupEditor;
use App\Livewire\Admin\Settings\SettingsIndex;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function (): void {
    Route::prefix('settings')->name('admin.settings.')->group(function (): void {
        Route::get('/', SettingsIndex::class)
            ->middleware('permission:settings.view')
            ->name('index');

        Route::post('/save', SettingsSaveController::class)
            ->middleware('permission:settings.update')
            ->name('save');

        Route::get('/{group}', SettingsGroupEditor::class)
            ->middleware('permission:settings.view')
            ->name('group');
    });

    Route::get('/permission-groups', PermissionGroupIndex::class)->middleware('permission:settings.permissions')->name('admin.permission-groups.index');
    Route::get('/permissions', PermissionIndex::class)->middleware('permission:settings.permissions')->name('admin.permissions.index');
    Route::get('/roles', RoleIndex::class)->middleware('permission:settings.roles')->name('admin.roles.index');
    Route::get('/roles/create', RoleCreate::class)->middleware('permission:settings.roles')->name('admin.roles.create');
    Route::get('/roles/{id}/edit', RoleEdit::class)->middleware('permission:settings.roles')->name('admin.roles.edit');
    Route::get('/assign-role', AssignRoleIndex::class)->middleware('permission:settings.roles')->name('admin.assign-role.index');
    Route::get('/assign-user-permissions', AssignPermissionIndex::class)->middleware('permission:settings.permissions')->name('admin.assign-user-permissions.index');
    Route::get('/assign-user-permissions/{id}/edit', AssignPermissionEdit::class)->middleware('permission:settings.permissions')->name('admin.assign-user-permissions.edit');

    Route::prefix('logs')->name('admin.logs.')->group(function (): void {
        Route::get('/login', \App\Livewire\Admin\Logs\LoginLogIndex::class)
            ->middleware('permission:logs.login.view')->name('login.index');
        Route::get('/activity', \App\Livewire\Admin\Logs\ActivityLogIndex::class)
            ->middleware('permission:logs.activity.view')->name('activity.index');
    });

    Route::get('/clear-all-cache', function () {
        Artisan::call('optimize:clear');
        Artisan::call('storage:link');

        return redirect()->route('admin.staff.index')->with('success', 'Cache cleared successfully.');
    })->middleware('permission:settings.update')->name('settings.clear-cache');

    // -------------------------------------------------------------------------
    // Staff & HR module
    // -------------------------------------------------------------------------

    Route::prefix('staff')->name('admin.staff.')->group(function (): void {
        Route::get('/', \App\Livewire\Admin\Staff\Index::class)
            ->middleware('permission:staff.view')->name('index');
        Route::get('/create', \App\Livewire\Admin\Staff\Create::class)
            ->middleware('permission:staff.create')->name('create');
        Route::get('/{user}/edit', \App\Livewire\Admin\Staff\Edit::class)
            ->middleware('permission:staff.edit')->name('edit');
        Route::get('/{user}', \App\Livewire\Admin\Staff\Show::class)
            ->middleware('permission:staff.view')->name('show');
    });

    Route::prefix('departments')->name('admin.departments.')->group(function (): void {
        Route::get('/', \App\Livewire\Admin\Departments\Index::class)
            ->middleware('permission:departments.view')->name('index');
    });

    // -------------------------------------------------------------------------
    // NewsPilot AI — Content / Posts module (Phase 4A)
    // -------------------------------------------------------------------------

    Route::prefix('posts')->name('admin.posts.')->group(function (): void {
        Route::get('/', \App\Livewire\Admin\Posts\Index::class)
            ->middleware('permission:posts.view|posts.view_any')
            ->name('index');

        Route::get('/create', \App\Livewire\Admin\Posts\Create::class)
            ->middleware('permission:posts.create')
            ->name('create');

        Route::get('/{post}/edit', \App\Livewire\Admin\Posts\Edit::class)
            ->middleware('permission:posts.edit|posts.edit_own')
            ->name('edit');

        Route::get('/{post}', \App\Livewire\Admin\Posts\Show::class)
            ->middleware('permission:posts.view|posts.view_any')
            ->name('show');
    });

    // -------------------------------------------------------------------------
    // NewsPilot AI — Editorial Workflow (Phase 4C)
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // NewsPilot AI — Analytics dashboards (Phase 7-12)
    // -------------------------------------------------------------------------

    Route::prefix('dashboards')->name('admin.dashboards.')->group(function (): void {
        Route::get('/overview', \App\Livewire\Admin\Dashboards\Overview::class)
            ->name('overview');
        Route::get('/content', \App\Livewire\Admin\Dashboards\ContentAnalytics::class)
            ->middleware('permission:posts.view|posts.view_any')->name('content');
        Route::get('/seo', \App\Livewire\Admin\Dashboards\SeoAnalytics::class)
            ->middleware('permission:seo.view')->name('seo');
        Route::get('/revenue', \App\Livewire\Admin\Dashboards\RevenueAnalytics::class)
            ->middleware('permission:ads.view|newsletter.view')->name('revenue');
        Route::get('/users', \App\Livewire\Admin\Dashboards\UserActivity::class)
            ->middleware('permission:staff.view|logs.login.view|logs.activity.view')->name('users');
        Route::get('/ai', \App\Livewire\Admin\Dashboards\AiOverview::class)
            ->middleware('permission:ai.reports')->name('ai');
    });

    Route::prefix('editorial')->name('admin.editorial.')->group(function (): void {
        Route::get('/queue', \App\Livewire\Admin\Editorial\Kanban::class)
            ->middleware('permission:editorial.review_queue')
            ->name('queue');

        Route::get('/calendar', \App\Livewire\Admin\Editorial\Calendar::class)
            ->middleware('permission:editorial.calendar')
            ->name('calendar');
    });

    // -------------------------------------------------------------------------
    // NewsPilot AI — AI Studio (Phase 7-11)
    // -------------------------------------------------------------------------

    Route::prefix('ai')->name('admin.ai.')->group(function (): void {
        Route::get('/writer', \App\Livewire\Admin\AI\WriterStudio::class)
            ->middleware('permission:ai.use_writer')->name('writer');

        Route::get('/seo-generator', \App\Livewire\Admin\AI\SeoGeneratorStudio::class)
            ->middleware('permission:ai.use_seo')->name('seo-generator');

        Route::get('/prompt-templates', \App\Livewire\Admin\AI\PromptTemplates::class)
            ->middleware('permission:ai.templates')->name('prompt-templates');

        Route::get('/usage-reports', \App\Livewire\Admin\AI\UsageReports::class)
            ->middleware('permission:ai.reports')->name('usage-reports');
    });

    // -------------------------------------------------------------------------
    // NewsPilot AI — SEO Tools (Phase 7-11)
    // -------------------------------------------------------------------------

    Route::prefix('seo')->name('admin.seo.')->group(function (): void {
        Route::get('/tools', \App\Livewire\Admin\Seo\Tools::class)
            ->middleware('permission:seo.view')->name('tools');

        Route::get('/redirects', \App\Livewire\Admin\Seo\Redirects::class)
            ->middleware('permission:seo.redirects')->name('redirects');
    });

    // -------------------------------------------------------------------------
    // Per-post Revisions viewer (Phase 7-11)
    // -------------------------------------------------------------------------

    Route::get('/posts/{post}/revisions', \App\Livewire\Admin\Posts\Revisions::class)
        ->middleware('permission:editorial.revisions')
        ->name('admin.posts.revisions');

    // -------------------------------------------------------------------------
    // NewsPilot AI — Categories (Phase 4D)
    // -------------------------------------------------------------------------

    Route::prefix('categories')->name('admin.categories.')->group(function (): void {
        Route::get('/', \App\Livewire\Admin\Categories\Index::class)
            ->middleware('permission:categories.view')
            ->name('index');

        Route::get('/create', \App\Livewire\Admin\Categories\Create::class)
            ->middleware('permission:categories.create')
            ->name('create');

        Route::get('/{category}/edit', \App\Livewire\Admin\Categories\Edit::class)
            ->middleware('permission:categories.edit')
            ->name('edit');
    });

    // -------------------------------------------------------------------------
    // NewsPilot AI — Languages (Phase 4D)
    // -------------------------------------------------------------------------

    Route::get('/languages', \App\Livewire\Admin\Languages\Index::class)
        ->middleware('permission:languages.view')
        ->name('admin.languages.index');

    // -------------------------------------------------------------------------
    // NewsPilot AI — Tags (Phase 4D)
    // -------------------------------------------------------------------------

    Route::get('/tags', \App\Livewire\Admin\Tags\Index::class)
        ->middleware('permission:tags.view')
        ->name('admin.tags.index');

    // -------------------------------------------------------------------------
    // NewsPilot AI — Newsletter (Phase 6A)
    // -------------------------------------------------------------------------

    Route::get('/newsletter/subscribers', \App\Livewire\Admin\Newsletter\Subscribers::class)
        ->middleware('permission:newsletter.view')
        ->name('admin.newsletter.subscribers');

    // -------------------------------------------------------------------------
    // NewsPilot AI — Comments moderation (Phase 6B)
    // -------------------------------------------------------------------------

    Route::get('/comments', \App\Livewire\Admin\Comments\Index::class)
        ->middleware('permission:comments.moderate')
        ->name('admin.comments.index');

    // -------------------------------------------------------------------------
    // NewsPilot AI — Ad Manager (Phase 6C)
    // -------------------------------------------------------------------------

    Route::get('/ads', \App\Livewire\Admin\Ads\Index::class)
        ->middleware('permission:ads.view')
        ->name('admin.ads.index');

    // -------------------------------------------------------------------------
    // NewsPilot AI — RSS Importer (Phase 6D)
    // -------------------------------------------------------------------------

    Route::get('/imports/sources', \App\Livewire\Admin\Imports\Sources::class)
        ->middleware('permission:rss.view')
        ->name('admin.imports.sources');

    // -------------------------------------------------------------------------
    // NewsPilot AI — Media Library (Phase 4D)
    // -------------------------------------------------------------------------

    Route::get('/media', \App\Livewire\Admin\Media\Index::class)
        ->middleware('permission:media.view')
        ->name('admin.media.index');

    // -------------------------------------------------------------------------
    // NewsPilot AI — Pages (Phase 4D)
    // -------------------------------------------------------------------------

    Route::prefix('pages')->name('admin.pages.')->group(function (): void {
        Route::get('/', \App\Livewire\Admin\Pages\Index::class)
            ->middleware('permission:pages.view')
            ->name('index');

        Route::get('/create', \App\Livewire\Admin\Pages\Create::class)
            ->middleware('permission:pages.create')
            ->name('create');

        Route::get('/{page}/edit', \App\Livewire\Admin\Pages\Edit::class)
            ->middleware('permission:pages.edit')
            ->name('edit');
    });
});
