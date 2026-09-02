<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * UserMigrationService
 *
 * Migrates WordPress users from wp_users and wp_usermeta into Rupantrix's `users` table,
 * preserving IDs, roles, bios, and registration dates.
 */
class UserMigrationService extends BaseMigrationService
{
    public function run(): void
    {
        $wpUsers = $this->wp()->table('wp_users')->orderBy('ID')->get();

        $this->info("Found {$wpUsers->count()} WordPress users.");

        $migrated = 0;

        foreach ($wpUsers as $wpUser) {
            $portalType = $this->resolvePortalType((int) $wpUser->ID);
            $bio = $this->resolveBio((int) $wpUser->ID);
            $publicSlug = Str::slug($wpUser->user_nicename ?: $wpUser->display_name ?: $wpUser->user_login);

            // Upsert user by ID or Email
            DB::table('users')->updateOrInsert(
                ['id' => $wpUser->ID],
                [
                    'name' => $wpUser->display_name ?: $wpUser->user_login,
                    'email' => $wpUser->user_email,
                    'password' => $wpUser->user_pass,
                    'bio' => $bio,
                    'public_slug' => $publicSlug,
                    'portal_type' => $portalType,
                    'status' => 'active',
                    'timezone' => 'UTC',
                    'locale' => 'en',
                    'email_verified_at' => now(),
                    'created_at' => $wpUser->user_registered,
                    'updated_at' => now(),
                ]
            );

            $migrated++;
        }

        $this->info("  Done. Users migrated/updated: {$migrated}");
    }

    private function resolvePortalType(int $wpUserId): string
    {
        $meta = $this->wp()
            ->table('wp_usermeta')
            ->where('user_id', $wpUserId)
            ->where('meta_key', 'wp_capabilities')
            ->value('meta_value');

        if (!$meta) {
            return 'visitor';
        }

        if (str_contains($meta, 'administrator')) {
            return 'admin';
        }

        if (
            str_contains($meta, 'editor') ||
            str_contains($meta, 'author') ||
            str_contains($meta, 'contributor')
        ) {
            return 'author';
        }

        return 'visitor';
    }

    private function resolveBio(int $wpUserId): ?string
    {
        $bio = $this->wp()
            ->table('wp_usermeta')
            ->where('user_id', $wpUserId)
            ->where('meta_key', 'description')
            ->value('meta_value');

        return $bio ? trim((string) $bio) : null;
    }
}
