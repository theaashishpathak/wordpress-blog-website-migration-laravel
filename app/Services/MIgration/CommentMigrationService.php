<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CommentMigrationService
 *
 * Migrates WordPress comments into Rupantrix's `comments` table,
 * preserving approval status, post relations, author names, guest details, and nested parent hierarchies.
 */
class CommentMigrationService extends BaseMigrationService
{
    public function run(): void
    {
        if (!Schema::hasTable('comments')) {
            $this->warn('Comments table does not exist.');
            return;
        }

        $this->info('Starting Comments migration...');

        // Fetch WordPress comments
        $wpComments = $this->wp()
            ->table('wp_comments')
            ->orderBy('comment_ID')
            ->get();

        $this->info("Found {$wpComments->count()} WordPress comments.");

        $migrated = 0;
        $skipped = 0;

        $validPostIds = DB::table('posts')->pluck('id')->flip()->toArray();

        foreach ($wpComments as $c) {
            $postId = (int) $c->comment_post_ID;

            // Must reference an existing migrated post
            if (!isset($validPostIds[$postId])) {
                $skipped++;
                continue;
            }

            $userId = (int) $c->user_id;
            $userExists = $userId > 0 && DB::table('users')->where('id', $userId)->exists();

            $status = match ((string) $c->comment_approved) {
                '1' => 'approved',
                '0' => 'pending',
                'spam' => 'spam',
                'trash' => 'trash',
                default => 'pending',
            };

            $parentId = (int) $c->comment_parent > 0 ? (int) $c->comment_parent : null;

            DB::table('comments')->updateOrInsert(
                ['id' => $c->comment_ID],
                [
                    'post_id' => $postId,
                    'parent_id' => $parentId,
                    'user_id' => $userExists ? $userId : null,
                    'guest_name' => $c->comment_author ?: 'Guest',
                    'guest_email' => $c->comment_author_email ?: null,
                    'guest_website' => $c->comment_author_url ?: null,
                    'body' => $c->comment_content ?: '',
                    'status' => $status,
                    'approved_at' => $status === 'approved' ? $c->comment_date : null,
                    'moderated_by' => null,
                    'ip_address' => $c->comment_author_IP ?: null,
                    'user_agent' => $c->comment_agent ?: null,
                    'created_at' => $c->comment_date,
                    'updated_at' => $c->comment_date,
                ]
            );

            $migrated++;
        }

        $this->info("  Done. Comments Migrated: {$migrated}, Skipped (orphaned): {$skipped}");
    }
}
