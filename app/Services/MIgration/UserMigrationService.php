<?php

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * UserMigrationService
 *
 * WHAT IT DOES:
 * Reads every user from WordPress's wp_users table and inserts them into
 * NewsPilot's users table.
 *
 * WHY WE NEED THIS FIRST:
 * NewsPilot's posts table has a column `author_id` that is a foreign key
 * pointing to `users.id`. If we try to insert a post whose author doesn't
 * exist yet in NewsPilot's users table, MySQL will reject it with:
 * "Cannot add or update a child row: a foreign key constraint fails"
 * So users MUST be migrated before posts.
 *
 * FIELD MAPPING (WordPress → NewsPilot):
 * wp_users.ID              → users.id           (same numeric ID, so post author_id still matches)
 * wp_users.display_name    → users.name
 * wp_users.user_email      → users.email
 * wp_users.user_pass       → users.password     (already hashed, we keep the hash)
 * wp_users.user_registered → users.created_at
 *
 * WHY WE KEEP THE SAME ID:
 * WordPress posts store the author as `post_author = 5` (a wp_users ID).
 * If we let NewsPilot auto-increment and user 5 in WordPress becomes user 8
 * in NewsPilot, then when we migrate posts and say author_id = 5, it now
 * points to the wrong person. Keeping the same ID avoids this mapping problem.
 *
 * PORTAL TYPE LOGIC:
 * WordPress has user roles stored in wp_usermeta (wp_capabilities).
 * - administrator → NewsPilot 'admin'
 * - editor, author, contributor → NewsPilot 'author'
 * - subscriber → NewsPilot 'visitor'
 */
class UserMigrationService extends BaseMigrationService

{
    public function run(): void
    {
        // Fetch all WordPress users
        $wpUsers = $this->wp()->table('wp_users')->get();

        $this->info("Found {$wpUsers->count()} WordPress users.");

        $migrated = 0;
        $skipped  = 0;

        foreach ($wpUsers as $wpUser) {

            // Skip if this email already exists in NewsPilot
            // WHY: Running the migration twice would crash on the unique email constraint.
            // Skipping duplicates makes the command safely re-runnable.
            $exists = DB::table('users')->where('email', $wpUser->user_email)->exists();
            if ($exists) {
                $this->warn("  Skipped (already exists): {$wpUser->user_email}");
                $skipped++;
                continue;
            }

            // Determine the user's WordPress role from wp_usermeta
            $portalType = $this->resolvePortalType($wpUser->ID);

            DB::table('users')->insert([
                'id'                => $wpUser->ID,
                'name'              => $wpUser->display_name ?: $wpUser->user_login,
                'email'             => $wpUser->user_email,
                // WHY KEEP THE WP HASH:
                // WordPress uses phpass hashing. The hash format is different from
                // Laravel's bcrypt. We store it as-is for now. The user can reset
                // their password after migration, or you can force a reset email.
                // DO NOT try to re-hash — you don't have the plaintext passwords.
                'password'          => $wpUser->user_pass,
                'avatar'            => null,
                'portal_type'       => $portalType,
                'status'            => 'active',
                'timezone'          => 'UTC',
                'locale'            => 'en',
                'email_verified_at' => now(),
                'created_at'        => $wpUser->user_registered,
                'updated_at'        => now(),
            ]);

            $this->info("  ✓ Migrated: {$wpUser->user_email} [{$portalType}]");
            $migrated++;
        }

        $this->info("  Done. Migrated: {$migrated}, Skipped: {$skipped}");
    }

    /**
     * Read the user's WordPress role from wp_usermeta and map to NewsPilot portal_type.
     *
     * WHY wp_usermeta:
     * WordPress doesn't store roles in wp_users directly. Roles are stored as a
     * serialized PHP array in wp_usermeta where meta_key = 'wp_capabilities'.
     * Example value: a:1:{s:13:"administrator";b:1;}
     * We check if the string contains 'administrator', 'editor', etc.
     */
    private function resolvePortalType(int $wpUserId): string
    {
        $meta = $this->wp()
            ->table('wp_usermeta')
            ->where('user_id', $wpUserId)
            ->where('meta_key', 'wp_capabilities')
            ->value('meta_value');

        if (! $meta) {
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
}
