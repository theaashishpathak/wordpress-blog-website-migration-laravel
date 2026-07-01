<?php

namespace Database\Seeders;

use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionGroupSeeder extends Seeder
{
    /**
     * Human-friendly names for each top-level key in config('permissions').
     * Unknown keys auto-fall back to a humanized version of the slug.
     *
     * @var array<string, string>
     */
    public const DISPLAY_NAMES = [
        // Existing HR / admin foundation
        'profile'        => 'Profile',
        'staff'          => 'Staff',
        'departments'    => 'Departments',
        'notifications'  => 'Notifications',
        'settings'       => 'Settings',
        'logs'           => 'Audit Logs',

        // NewsPilot AI domain groups (Phase 1)
        'content'        => 'Content (Posts)',
        'news'           => 'News',
        'taxonomy'       => 'Categories & Tags',
        'pages'          => 'Pages',
        'media'          => 'Media Library',
        'comments'       => 'Comments',
        'editorial'      => 'Editorial Workflow',
        'seo'            => 'SEO Tools',
        'ai'             => 'AI Studio',
        'rss'            => 'RSS Importer',
        'newsletter'     => 'Newsletter',
        'subscribers'    => 'Subscribers',
        'monetization'   => 'Monetization & Ads',
        'languages'      => 'Languages',
        'translations'   => 'Translations',
        'appearance'     => 'Appearance & Theme',
        'frontend'       => 'Frontend Visitor',
        'reports'        => 'Reports',
    ];

    public function run(): void
    {
        /** @var array<string, array<int, string>> $groups */
        $groups = config('permissions', []);

        foreach (array_keys($groups) as $slug) {
            if (! is_string($slug) || $slug === '') {
                continue;
            }

            $name = self::DISPLAY_NAMES[$slug]
                ?? Str::of($slug)->replace(['-', '_'], ' ')->headline()->toString();

            PermissionGroup::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name],
            );
        }
    }
}
