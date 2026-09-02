<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TagMigrationService
 *
 * High-performance batch migration for WordPress tags into Rupantrix's `tags`
 * and `tag_translations` tables, and maps all post ↔ tag relationships into `post_tag`.
 */
class TagMigrationService extends BaseMigrationService
{
    private int $defaultLanguageId = 1;

    public function run(): void
    {
        $lang = DB::table('languages')->where('code', 'en')->first();
        if ($lang) {
            $this->defaultLanguageId = $lang->id;
        }

        $this->info('Starting Tag migration...');

        // 1. Fetch all WordPress post_tags
        $wpTags = $this->wp()
            ->table('wp_terms as t')
            ->join('wp_term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
            ->where('tt.taxonomy', 'post_tag')
            ->select('t.term_id', 't.name', 't.slug', 'tt.description', 'tt.term_taxonomy_id')
            ->get();

        $this->info("Found {$wpTags->count()} WordPress tags.");

        // In-memory slug lookup to prevent collisions in O(1)
        $existingTagSlugs = DB::table('tag_translations')
            ->where('language_id', $this->defaultLanguageId)
            ->pluck('tag_id', 'slug')
            ->toArray();

        $tagsBatch = [];
        $translationsBatch = [];
        $migrated = 0;

        foreach ($wpTags as $wpTag) {
            $tagId = (int) $wpTag->term_id;
            $slug = $wpTag->slug ?: 'tag-' . $tagId;

            // Check collision with other tags
            if (isset($existingTagSlugs[$slug]) && $existingTagSlugs[$slug] !== $tagId) {
                $slug = $slug . '-' . $tagId;
            }
            $existingTagSlugs[$slug] = $tagId;

            $tagsBatch[] = [
                'id' => $tagId,
                'code' => $slug,
                'name' => $wpTag->name,
                'slug' => $slug,
                'color' => '#64748b',
                'type' => 'general',
                'status' => 'published',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $translationsBatch[] = [
                'tag_id' => $tagId,
                'language_id' => $this->defaultLanguageId,
                'name' => $wpTag->name,
                'slug' => $slug,
                'description' => $wpTag->description ?: null,
                'meta_title' => $wpTag->name,
                'meta_description' => $wpTag->description ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($tagsBatch) >= 300) {
                DB::table('tags')->upsert($tagsBatch, ['id'], ['code', 'name', 'slug', 'color', 'type', 'status', 'updated_at']);
                if (Schema::hasTable('tag_translations')) {
                    DB::table('tag_translations')->upsert($translationsBatch, ['tag_id', 'language_id'], ['name', 'slug', 'description', 'meta_title', 'meta_description', 'updated_at']);
                }
                $migrated += count($tagsBatch);
                $tagsBatch = [];
                $translationsBatch = [];
            }
        }

        if (!empty($tagsBatch)) {
            DB::table('tags')->upsert($tagsBatch, ['id'], ['code', 'name', 'slug', 'color', 'type', 'status', 'updated_at']);
            if (Schema::hasTable('tag_translations')) {
                DB::table('tag_translations')->upsert($translationsBatch, ['tag_id', 'language_id'], ['name', 'slug', 'description', 'meta_title', 'meta_description', 'updated_at']);
            }
            $migrated += count($tagsBatch);
        }

        $this->info("  Done. Tags Migrated/Upserted: {$migrated}");

        // 2. Migrate Post ↔ Tag relationships
        $this->migratePostTagRelationships();
    }

    /**
     * Map all wp_term_relationships (for post_tags) into Rupantrix's post_tag table.
     */
    private function migratePostTagRelationships(): void
    {
        $this->info('Migrating Post ↔ Tag relationships...');

        if (!Schema::hasTable('post_tag')) {
            $this->warn('  post_tag table does not exist.');
            return;
        }

        $wpRelations = $this->wp()
            ->table('wp_term_relationships as tr')
            ->join('wp_term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
            ->where('tt.taxonomy', 'post_tag')
            ->select('tr.object_id as post_id', 'tt.term_id as tag_id')
            ->get();

        $this->info("Found {$wpRelations->count()} WordPress post-tag associations.");

        $inserted = 0;
        $skipped = 0;

        $validPostIds = DB::table('posts')->pluck('id')->flip()->toArray();
        $validTagIds = DB::table('tags')->pluck('id')->flip()->toArray();

        $recordsToInsert = [];

        foreach ($wpRelations as $rel) {
            $postId = (int) $rel->post_id;
            $tagId = (int) $rel->tag_id;

            if (!isset($validPostIds[$postId]) || !isset($validTagIds[$tagId])) {
                $skipped++;
                continue;
            }

            $recordsToInsert[] = [
                'post_id' => $postId,
                'tag_id' => $tagId,
                'created_at' => now(),
            ];

            if (count($recordsToInsert) >= 500) {
                DB::table('post_tag')->insertOrIgnore($recordsToInsert);
                $inserted += count($recordsToInsert);
                $recordsToInsert = [];
            }
        }

        if (!empty($recordsToInsert)) {
            DB::table('post_tag')->insertOrIgnore($recordsToInsert);
            $inserted += count($recordsToInsert);
        }

        $this->info("  Done. Post-Tag relations synced: {$inserted}, Skipped: {$skipped}");
    }
}
