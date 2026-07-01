<?php

/*
|--------------------------------------------------------------------------
| Application Permissions
|--------------------------------------------------------------------------
|
| Each top-level key is a permission group slug (must match a row in
| permission_groups.slug seeded by PermissionGroupSeeder). The array of
| strings under each key is the list of permission names that belong to
| that group. PermissionSeeder reads this file and writes everything to
| the permissions table on every db:seed.
|
| Convention: use `{domain}.{verb}` format, e.g., posts.create, ai.use.
| Use `{domain}.{verb}_own` for self-scoped variants where applicable.
|
*/

return [

    // -------------------------------------------------------------------------
    // Existing HR / admin foundation groups (DO NOT REMOVE)
    // -------------------------------------------------------------------------

    'profile' => [
        'profile.view',
        'profile.update',
    ],

    'staff' => [
        'staff.view',
        'staff.create',
        'staff.edit',
        'staff.delete',
        'staff.deactivate',
        'staff.assign_role',
    ],

    'departments' => [
        'departments.view',
        'departments.create',
        'departments.edit',
        'departments.delete',
    ],

    'notifications' => [
        'notifications.view',
        'notifications.manage',
    ],

    'settings' => [
        'settings.view',
        'settings.update',
        'settings.roles',
        'settings.permissions',
    ],

    'logs' => [
        'logs.login.view',
        'logs.activity.view',
    ],

    // -------------------------------------------------------------------------
    // NewsPilot AI domain permissions (Phase 1)
    // -------------------------------------------------------------------------

    'content' => [
        'posts.view',
        'posts.view_any',          // see posts authored by anyone
        'posts.create',
        'posts.edit',
        'posts.edit_own',          // limited to own draft / pending posts
        'posts.delete',
        'posts.delete_own',
        'posts.duplicate',
        'posts.publish',
        'posts.schedule',
        'posts.feature',           // mark featured / trending / editor's pick
        'posts.mark_breaking',
        'posts.archive',
        'posts.bulk_action',
        'posts.preview',
    ],

    'news' => [
        'news.view',
        'news.create',
        'news.edit',
        'news.publish',
        'news.mark_breaking',
        'news.ticker',
        'news.bulk_action',
    ],

    'taxonomy' => [
        'categories.view',
        'categories.create',
        'categories.edit',
        'categories.delete',
        'tags.view',
        'tags.create',
        'tags.edit',
        'tags.delete',
        'tags.merge',
    ],

    'pages' => [
        'pages.view',
        'pages.create',
        'pages.edit',
        'pages.delete',
        'pages.publish',
    ],

    'media' => [
        'media.view',
        'media.upload',
        'media.edit',
        'media.delete',
        'media.bulk_delete',
        'media.folders',
    ],

    'comments' => [
        'comments.view',
        'comments.moderate',
        'comments.approve',
        'comments.reject',
        'comments.spam',
        'comments.delete',
        'comments.blacklist',
        'comments.reply',
    ],

    'editorial' => [
        'editorial.review_queue',
        'editorial.review_open',
        'editorial.approve',
        'editorial.reject',
        'editorial.request_changes',
        'editorial.assign_reviewer',
        'editorial.calendar',
        'editorial.notes',
        'editorial.revisions',
    ],

    'seo' => [
        'seo.view',
        'seo.manage',
        'seo.audit',
        'seo.schema',
        'seo.sitemap',
        'seo.robots',
        'seo.redirects',
        'seo.broken_links',
        'seo.internal_links',
    ],

    'ai' => [
        'ai.use',
        'ai.use_writer',
        'ai.use_seo',
        'ai.use_rewrite',
        'ai.use_translate',
        'ai.use_image',
        'ai.bulk',
        'ai.templates',
        'ai.settings',
        'ai.reports',
        'ai.set_provider',
        'ai.set_quota',
    ],

    'rss' => [
        'rss.view',
        'rss.create',
        'rss.edit',
        'rss.delete',
        'rss.import_now',
        'rss.toggle',
        'rss.logs',
    ],

    'newsletter' => [
        'newsletter.view',
        'newsletter.campaigns',
        'newsletter.templates',
        'newsletter.send',
        'newsletter.schedule',
        'newsletter.reports',
    ],

    'subscribers' => [
        'subscribers.view',
        'subscribers.create',
        'subscribers.edit',
        'subscribers.delete',
        'subscribers.export',
        'subscribers.import',
    ],

    'monetization' => [
        'ads.view',
        'ads.create',
        'ads.edit',
        'ads.delete',
        'ads.positions',
        'ads.reports',
        'sponsored.manage',
        'sponsored.reports',
        'premium.manage',
        'premium.access',     // Granted to paying subscribers — unlocks paywalled posts.
        'subscriptions.manage',
        'payments.view',
        'payments.refund',
    ],

    'languages' => [
        'languages.view',
        'languages.create',
        'languages.edit',
        'languages.delete',
        'languages.toggle',
    ],

    'translations' => [
        'translations.view',
        'translations.create',
        'translations.edit',
        'translations.delete',
        'translations.publish',
        'translations.ai_translate',
    ],

    'appearance' => [
        'homepage.builder',
        'homepage.sections',
        'menus.view',
        'menus.manage',
        'widgets.manage',
        'theme.settings',
    ],

    'frontend' => [
        'frontend.bookmark',
        'frontend.comment',
        'frontend.like',
        'frontend.subscribe',
    ],

    'reports' => [
        'reports.content',
        'reports.seo',
        'reports.ai',
        'reports.monetization',
        'reports.subscribers',
        'reports.export',
    ],
];
