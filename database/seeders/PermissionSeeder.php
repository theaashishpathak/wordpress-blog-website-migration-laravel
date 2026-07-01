<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Ensure all permission groups exist FIRST so we can attach permission_group_id below.
        $this->call(PermissionGroupSeeder::class);

        $allPermissionNames = [];

        // 2. Walk the config grouped (preserves group → permission mapping).
        /** @var array<string, array<int, string>> $config */
        $config = config('permissions', []);

        foreach ($config as $groupSlug => $permissionList) {
            if (! is_string($groupSlug) || ! is_array($permissionList)) {
                continue;
            }

            $group = PermissionGroup::query()->where('slug', $groupSlug)->first();

            foreach ($permissionList as $permissionName) {
                if (! is_string($permissionName) || $permissionName === '') {
                    continue;
                }

                $permission = Permission::query()->firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                );

                // Attach (or correct) the group on both newly-created and pre-existing rows.
                if ($group !== null && (int) $permission->permission_group_id !== (int) $group->id) {
                    $permission->permission_group_id = $group->id;
                    $permission->save();
                }

                $allPermissionNames[] = $permissionName;
            }
        }

        $allPermissionNames = array_values(array_unique($allPermissionNames));

        // 3. Super Admin gets every permission.
        $superAdminRole = Role::query()->firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);
        $superAdminRole->syncPermissions($allPermissionNames);

        // 4. NewsPilot newsroom roles — assigned only the permissions they need.
        foreach ($this->newsroomRoles() as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            // Only sync permissions that actually exist (defensive — config may shrink later).
            $existing = array_values(array_intersect($permissions, $allPermissionNames));
            $role->syncPermissions($existing);
        }

        // 5. Legacy Employee role kept for backwards compat (self-service only).
        $employeeRole = Role::query()->firstOrCreate([
            'name' => 'Employee',
            'guard_name' => 'web',
        ]);
        $employeeRole->syncPermissions([
            'profile.view',
            'profile.update',
            'notifications.view',
        ]);

        // Refresh cache so newly-attached group_ids are immediately visible to the app.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Default newsroom role → permission mapping for NewsPilot AI.
     *
     * @return array<string, list<string>>
     */
    private function newsroomRoles(): array
    {
        return [
            'Admin' => $this->adminPermissions(),
            'Editor' => $this->editorPermissions(),
            'Author' => $this->authorPermissions(),
            'Contributor' => $this->contributorPermissions(),
            'SEO Manager' => $this->seoManagerPermissions(),
            'Ad Manager' => $this->adManagerPermissions(),
            'Subscriber' => $this->subscriberPermissions(),
        ];
    }

    /**
     * Admin — everything except role/permission management (Super Admin only).
     *
     * @return list<string>
     */
    private function adminPermissions(): array
    {
        return [
            // self
            'profile.view', 'profile.update',
            'notifications.view', 'notifications.manage',

            // settings — view/update yes, role+permission no
            'settings.view', 'settings.update',

            // logs
            'logs.login.view', 'logs.activity.view',

            // staff / departments (HR optional, but admin can manage authors)
            'staff.view', 'staff.create', 'staff.edit', 'staff.deactivate', 'staff.assign_role',
            'departments.view', 'departments.create', 'departments.edit',

            // content full
            'posts.view', 'posts.view_any', 'posts.create', 'posts.edit', 'posts.edit_own',
            'posts.delete', 'posts.delete_own', 'posts.duplicate', 'posts.publish',
            'posts.schedule', 'posts.feature', 'posts.mark_breaking', 'posts.archive',
            'posts.bulk_action', 'posts.preview',

            // news full
            'news.view', 'news.create', 'news.edit', 'news.publish',
            'news.mark_breaking', 'news.ticker', 'news.bulk_action',

            // taxonomy / pages / media full
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'tags.view', 'tags.create', 'tags.edit', 'tags.delete', 'tags.merge',
            'pages.view', 'pages.create', 'pages.edit', 'pages.delete', 'pages.publish',
            'media.view', 'media.upload', 'media.edit', 'media.delete', 'media.bulk_delete', 'media.folders',

            // comments full
            'comments.view', 'comments.moderate', 'comments.approve', 'comments.reject',
            'comments.spam', 'comments.delete', 'comments.blacklist', 'comments.reply',

            // editorial full
            'editorial.review_queue', 'editorial.review_open', 'editorial.approve',
            'editorial.reject', 'editorial.request_changes', 'editorial.assign_reviewer',
            'editorial.calendar', 'editorial.notes', 'editorial.revisions',

            // SEO full
            'seo.view', 'seo.manage', 'seo.audit', 'seo.schema', 'seo.sitemap',
            'seo.robots', 'seo.redirects', 'seo.broken_links', 'seo.internal_links',

            // AI full
            'ai.use', 'ai.use_writer', 'ai.use_seo', 'ai.use_rewrite', 'ai.use_translate',
            'ai.use_image', 'ai.bulk', 'ai.templates', 'ai.settings', 'ai.reports',
            'ai.set_provider', 'ai.set_quota',

            // RSS full
            'rss.view', 'rss.create', 'rss.edit', 'rss.delete',
            'rss.import_now', 'rss.toggle', 'rss.logs',

            // newsletter + subscribers
            'newsletter.view', 'newsletter.campaigns', 'newsletter.templates',
            'newsletter.send', 'newsletter.schedule', 'newsletter.reports',
            'subscribers.view', 'subscribers.create', 'subscribers.edit',
            'subscribers.delete', 'subscribers.export', 'subscribers.import',

            // monetization
            'ads.view', 'ads.create', 'ads.edit', 'ads.delete', 'ads.positions', 'ads.reports',
            'sponsored.manage', 'sponsored.reports', 'premium.manage',
            'subscriptions.manage', 'payments.view', 'payments.refund',

            // languages + translations
            'languages.view', 'languages.create', 'languages.edit', 'languages.delete', 'languages.toggle',
            'translations.view', 'translations.create', 'translations.edit',
            'translations.delete', 'translations.publish', 'translations.ai_translate',

            // appearance
            'homepage.builder', 'homepage.sections', 'menus.view', 'menus.manage',
            'widgets.manage', 'theme.settings',

            // reports
            'reports.content', 'reports.seo', 'reports.ai',
            'reports.monetization', 'reports.subscribers', 'reports.export',
        ];
    }

    /**
     * Editor — review, approve, publish; no settings / user management.
     *
     * @return list<string>
     */
    private function editorPermissions(): array
    {
        return [
            'profile.view', 'profile.update',
            'notifications.view',

            // content (cannot delete others; can edit/publish)
            'posts.view', 'posts.view_any', 'posts.edit', 'posts.publish', 'posts.schedule',
            'posts.feature', 'posts.mark_breaking', 'posts.archive', 'posts.bulk_action',
            'posts.preview', 'posts.duplicate',

            // news
            'news.view', 'news.create', 'news.edit', 'news.publish',
            'news.mark_breaking', 'news.ticker', 'news.bulk_action',

            // taxonomy
            'categories.view', 'categories.create', 'categories.edit',
            'tags.view', 'tags.create', 'tags.edit', 'tags.merge',

            // pages (edit / publish only — no create / delete)
            'pages.view', 'pages.edit', 'pages.publish',

            // media
            'media.view', 'media.upload', 'media.edit',

            // comments
            'comments.view', 'comments.moderate', 'comments.approve',
            'comments.reject', 'comments.spam', 'comments.reply',

            // editorial (primary user)
            'editorial.review_queue', 'editorial.review_open', 'editorial.approve',
            'editorial.reject', 'editorial.request_changes', 'editorial.assign_reviewer',
            'editorial.calendar', 'editorial.notes', 'editorial.revisions',

            // SEO (use only — not settings)
            'seo.view', 'seo.manage', 'seo.audit',

            // AI (use only)
            'ai.use', 'ai.use_writer', 'ai.use_seo', 'ai.use_rewrite',
            'ai.use_translate', 'ai.use_image',

            // RSS (trigger import)
            'rss.view', 'rss.import_now',

            // newsletter
            'newsletter.view', 'newsletter.campaigns',
            'newsletter.send', 'newsletter.schedule',

            // translations
            'translations.view', 'translations.create', 'translations.edit',
            'translations.publish', 'translations.ai_translate',

            // appearance (curate homepage / featured)
            'homepage.builder', 'homepage.sections',

            // reports
            'reports.content', 'reports.seo',
        ];
    }

    /**
     * Author — create + edit own posts, submit for review, use AI (limited).
     *
     * @return list<string>
     */
    private function authorPermissions(): array
    {
        return [
            'profile.view', 'profile.update',
            'notifications.view',

            // posts (own only)
            'posts.view', 'posts.create', 'posts.edit_own', 'posts.delete_own',
            'posts.preview', 'posts.duplicate',

            // tags (suggest)
            'tags.view', 'tags.create',
            'categories.view',

            // media (own upload)
            'media.view', 'media.upload', 'media.edit',

            // AI (limited — no bulk / settings)
            'ai.use', 'ai.use_writer', 'ai.use_seo', 'ai.use_rewrite', 'ai.use_translate',

            // editorial (see feedback only)
            'editorial.notes', 'editorial.revisions',

            // translations (own)
            'translations.view', 'translations.create', 'translations.edit',

            // reports (own — model scope handles)
            'reports.content',
        ];
    }

    /**
     * Contributor — draft only, very limited AI usage.
     *
     * @return list<string>
     */
    private function contributorPermissions(): array
    {
        return [
            'profile.view', 'profile.update',
            'notifications.view',

            'posts.view', 'posts.create', 'posts.edit_own',
            'posts.preview',

            'tags.view',
            'categories.view',

            'media.view', 'media.upload',

            'ai.use', 'ai.use_writer',

            'editorial.notes',
        ];
    }

    /**
     * SEO Manager — SEO module + content edit for SEO fixes.
     *
     * @return list<string>
     */
    private function seoManagerPermissions(): array
    {
        return [
            'profile.view', 'profile.update',
            'notifications.view',

            'posts.view', 'posts.view_any', 'posts.edit', 'posts.preview',
            'pages.view', 'pages.edit',

            'tags.view', 'tags.create', 'tags.edit', 'tags.merge',
            'categories.view', 'categories.edit',

            'media.view', 'media.upload', 'media.edit',

            // SEO module full
            'seo.view', 'seo.manage', 'seo.audit', 'seo.schema', 'seo.sitemap',
            'seo.robots', 'seo.redirects', 'seo.broken_links', 'seo.internal_links',

            // AI for SEO fixes
            'ai.use', 'ai.use_seo', 'ai.use_translate',

            'translations.view', 'translations.edit',

            'reports.seo', 'reports.content',
        ];
    }

    /**
     * Ad Manager — monetization only.
     *
     * @return list<string>
     */
    private function adManagerPermissions(): array
    {
        return [
            'profile.view', 'profile.update',
            'notifications.view',

            'ads.view', 'ads.create', 'ads.edit', 'ads.delete',
            'ads.positions', 'ads.reports',
            'sponsored.manage', 'sponsored.reports',
            'premium.manage',
            'subscriptions.manage',
            'payments.view',

            'subscribers.view', 'subscribers.export',

            'reports.monetization', 'reports.subscribers',
        ];
    }

    /**
     * Subscriber — frontend interaction only.
     *
     * @return list<string>
     */
    private function subscriberPermissions(): array
    {
        return [
            'profile.view', 'profile.update',
            'frontend.bookmark', 'frontend.comment', 'frontend.like', 'frontend.subscribe',
        ];
    }
}
