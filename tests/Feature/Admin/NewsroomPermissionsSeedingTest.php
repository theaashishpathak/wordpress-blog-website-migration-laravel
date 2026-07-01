<?php

use App\Models\PermissionGroup;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionSeeder::class)->run();
});

test('newsroom permission groups are seeded', function (): void {
    $expectedGroups = [
        'profile', 'staff', 'departments', 'notifications', 'settings', 'logs',
        'content', 'news', 'taxonomy', 'pages', 'media', 'comments',
        'editorial', 'seo', 'ai', 'rss', 'newsletter', 'subscribers',
        'monetization', 'languages', 'translations', 'appearance',
        'frontend', 'reports',
    ];

    foreach ($expectedGroups as $slug) {
        expect(PermissionGroup::query()->where('slug', $slug)->exists())
            ->toBeTrue("Permission group [{$slug}] is missing.");
    }
});

test('every config permission is seeded with web guard and attached to its group', function (): void {
    /** @var array<string, array<int, string>> $config */
    $config = config('permissions', []);

    foreach ($config as $groupSlug => $permissionNames) {
        $group = PermissionGroup::query()->where('slug', $groupSlug)->first();

        expect($group)->not->toBeNull("Group [{$groupSlug}] missing.");

        foreach ($permissionNames as $name) {
            $permission = Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            expect($permission)->not->toBeNull("Permission [{$name}] missing.");
            expect((int) $permission->permission_group_id)
                ->toBe((int) $group->id, "Permission [{$name}] attached to wrong group.");
        }
    }
});

test('all newsroom roles exist', function (): void {
    $expected = [
        'Super Admin',
        'Admin',
        'Editor',
        'Author',
        'Contributor',
        'SEO Manager',
        'Ad Manager',
        'Subscriber',
        'Employee',
    ];

    foreach ($expected as $roleName) {
        expect(Role::query()->where('name', $roleName)->where('guard_name', 'web')->exists())
            ->toBeTrue("Role [{$roleName}] not seeded.");
    }
});

test('super admin has every seeded permission', function (): void {
    $allPermissionCount = Permission::query()->count();
    $superAdmin = Role::query()->where('name', 'Super Admin')->firstOrFail();

    expect($superAdmin->permissions)->toHaveCount($allPermissionCount);
});

test('editor can review and publish but not change settings', function (): void {
    $editor = Role::query()->where('name', 'Editor')->firstOrFail();
    $names = $editor->permissions->pluck('name')->all();

    expect($names)->toContain('editorial.approve');
    expect($names)->toContain('editorial.request_changes');
    expect($names)->toContain('posts.publish');
    expect($names)->toContain('posts.view_any');
    expect($names)->toContain('seo.manage');
    expect($names)->toContain('ai.use_writer');
    expect($names)->toContain('translations.publish');

    expect($names)->not->toContain('settings.update');
    expect($names)->not->toContain('settings.permissions');
    expect($names)->not->toContain('ai.settings');
    expect($names)->not->toContain('staff.create');
    expect($names)->not->toContain('ads.create');
});

test('author can create and edit own posts but not publish or edit others', function (): void {
    $author = Role::query()->where('name', 'Author')->firstOrFail();
    $names = $author->permissions->pluck('name')->all();

    expect($names)->toContain('posts.create');
    expect($names)->toContain('posts.edit_own');
    expect($names)->toContain('posts.delete_own');
    expect($names)->toContain('ai.use_writer');
    expect($names)->toContain('media.upload');

    expect($names)->not->toContain('posts.publish');
    expect($names)->not->toContain('posts.view_any');
    expect($names)->not->toContain('posts.edit');
    expect($names)->not->toContain('posts.delete');
    expect($names)->not->toContain('editorial.approve');
    expect($names)->not->toContain('settings.view');
});

test('contributor can draft but not delete and only minimal AI access', function (): void {
    $contributor = Role::query()->where('name', 'Contributor')->firstOrFail();
    $names = $contributor->permissions->pluck('name')->all();

    expect($names)->toContain('posts.create');
    expect($names)->toContain('posts.edit_own');
    expect($names)->toContain('ai.use_writer');

    expect($names)->not->toContain('posts.delete_own');
    expect($names)->not->toContain('ai.use_translate');
    expect($names)->not->toContain('ai.bulk');
    expect($names)->not->toContain('posts.publish');
});

test('seo manager has seo module but not monetization', function (): void {
    $seo = Role::query()->where('name', 'SEO Manager')->firstOrFail();
    $names = $seo->permissions->pluck('name')->all();

    expect($names)->toContain('seo.manage');
    expect($names)->toContain('seo.audit');
    expect($names)->toContain('seo.schema');
    expect($names)->toContain('seo.sitemap');
    expect($names)->toContain('seo.redirects');
    expect($names)->toContain('ai.use_seo');
    expect($names)->toContain('reports.seo');

    expect($names)->not->toContain('ads.create');
    expect($names)->not->toContain('newsletter.send');
    expect($names)->not->toContain('settings.update');
});

test('ad manager has monetization but not content editing', function (): void {
    $ad = Role::query()->where('name', 'Ad Manager')->firstOrFail();
    $names = $ad->permissions->pluck('name')->all();

    expect($names)->toContain('ads.create');
    expect($names)->toContain('ads.positions');
    expect($names)->toContain('sponsored.manage');
    expect($names)->toContain('subscriptions.manage');
    expect($names)->toContain('payments.view');
    expect($names)->toContain('reports.monetization');

    expect($names)->not->toContain('posts.edit');
    expect($names)->not->toContain('posts.create');
    expect($names)->not->toContain('seo.manage');
    expect($names)->not->toContain('editorial.approve');
});

test('subscriber can only interact on the frontend', function (): void {
    $sub = Role::query()->where('name', 'Subscriber')->firstOrFail();
    $names = $sub->permissions->pluck('name')->all();

    expect($names)->toContain('frontend.bookmark');
    expect($names)->toContain('frontend.comment');
    expect($names)->toContain('frontend.like');
    expect($names)->toContain('frontend.subscribe');
    expect($names)->toContain('profile.view');

    expect($names)->not->toContain('posts.create');
    expect($names)->not->toContain('posts.view_any');
    expect($names)->not->toContain('ai.use');
    expect($names)->not->toContain('comments.moderate');
});

test('admin role has settings but cannot manage roles or permissions', function (): void {
    $admin = Role::query()->where('name', 'Admin')->firstOrFail();
    $names = $admin->permissions->pluck('name')->all();

    expect($names)->toContain('settings.view');
    expect($names)->toContain('settings.update');
    expect($names)->toContain('ai.settings');
    expect($names)->toContain('staff.create');
    expect($names)->toContain('subscribers.export');

    expect($names)->not->toContain('settings.roles');
    expect($names)->not->toContain('settings.permissions');
});

test('reseeding is idempotent and does not duplicate role permissions', function (): void {
    $editorBefore = Role::query()->where('name', 'Editor')->firstOrFail();
    $countBefore = $editorBefore->permissions->count();

    // Re-run the seeder.
    app(PermissionSeeder::class)->run();

    $editorAfter = Role::query()->where('name', 'Editor')->firstOrFail();
    $countAfter = $editorAfter->permissions->count();

    expect($countAfter)->toBe($countBefore);
    expect(Role::query()->where('name', 'Editor')->count())->toBe(1);
});
