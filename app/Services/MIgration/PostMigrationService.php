<?php

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;

/**
 * PostMigrationService
 *
 * WHAT IT DOES:
 * This is the heart of the migration. It reads every published WordPress post
 * and inserts it into NewsPilot's `posts` table AND `post_translations` table.
 *
 * WHY THIS IS LAST:
 * Posts have four foreign keys that must already exist:
 * - author_id       → references users.id         (Step 1)
 * - category_id     → references categories.id    (Step 2)
 * - featured_image_id → references media.id       (Step 3)
 * - default_language_id → references languages.id (always exists)
 * Running this last guarantees all those records are already in place.
 *
 * HOW WORDPRESS STORES POST DATA:
 * WordPress splits post data across two tables:
 * - wp_posts     → core data: title, content, slug, status, date, author
 * - wp_postmeta  → extra data: featured image ID, SEO data, custom fields
 *
 * The featured image is stored in wp_postmeta with meta_key = '_thumbnail_id'.
 * The category is fetched via wp_term_relationships + wp_term_taxonomy.
 *
 * NOTE ON ELEMENTOR CONTENT:
 * If your WordPress site uses Elementor, the actual readable content is NOT in
 * wp_posts.post_content (it's raw Elementor JSON). For those posts, post_content
 * will be empty or contain JSON. We store whatever is there and flag it in the
 * comments. Phase 3 will handle Elementor content extraction.
 *
 * WHAT 'post_translations' IS:
 * Same pattern as category_translations. The post's title, slug, excerpt, and
 * content are translatable, so they live in post_translations. The post row in
 * `posts` only stores the structural/metadata fields.
 *
 * FIELD MAPPING:
 * wp_posts.ID           → posts.id
 * wp_posts.post_author  → posts.author_id
 * wp_posts.post_date    → posts.published_at
 * wp_posts.post_status  → posts.status ('publish' → 'published', 'draft' → 'draft')
 * wp_posts.comment_status → posts.allow_comments
 * wp_posts.comment_count → posts.comment_count
 * wp_postmeta._thumbnail_id → posts.featured_image_id
 * wp_term_relationships → posts.category_id (first category assigned)
 *
 * post_translations:
 * wp_posts.post_title   → post_translations.title
 * wp_posts.post_name    → post_translations.slug
 * wp_posts.post_excerpt → post_translations.excerpt
 * wp_posts.post_content → post_translations.content
 */
class PostMigrationService extends BaseMigrationService

{
    private int $defaultLanguageId = 1;

    // Batch size — WHY: Loading 10,000 posts into memory at once can exhaust PHP's
    // memory limit. Processing in chunks of 100 keeps memory usage flat.
    private int $chunkSize = 100;

    public function run(): void
    {
        // Resolve language ID
        $lang = DB::table('languages')->where('code', 'en')->first();
        if ($lang) {
            $this->defaultLanguageId = $lang->id;
        }

        // Count total posts for the progress display
        $total = $this->wp()
            ->table('wp_posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->count();

        $this->info("Found {$total} published WordPress posts.");
        $this->info("Processing in chunks of {$this->chunkSize}...");

        $migrated = 0;
        $skipped = 0;

        // WHY CHUNK: chunk() fetches 100 rows at a time from the DB,
        // processes them, then fetches the next 100. This keeps memory usage
        // constant regardless of how many posts your WordPress site has.
        $this->wp()
            ->table('wp_posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->chunk($this->chunkSize, function ($wpPosts) use (&$migrated, &$skipped) {
                foreach ($wpPosts as $wpPost) {
                    $result = $this->migratePost($wpPost);
                    if ($result === 'migrated')
                        $migrated++;
                    if ($result === 'skipped')
                        $skipped++;
                }
                $this->info("  Progress: {$migrated} migrated, {$skipped} skipped...");
            });

        $this->info("  Done. Migrated: {$migrated}, Skipped: {$skipped}");
    }

    private function migratePost(object $wpPost): string
    {
        // Skip if already migrated (safe re-run)
        if (DB::table('posts')->where('id', $wpPost->ID)->exists()) {
            return 'skipped';
        }

        // ── Featured Image ───────────────────────────────────────────────
        // WHY POSTMETA: WordPress doesn't store featured_image in wp_posts.
        // It stores the attachment post ID in wp_postmeta under '_thumbnail_id'.
        $thumbnailId = $this->wp()
            ->table('wp_postmeta')
            ->where('post_id', $wpPost->ID)
            ->where('meta_key', '_thumbnail_id')
            ->value('meta_value');

        // Verify the media record actually exists in NewsPilot after our migration
        $featuredImageId = null;
        if ($thumbnailId) {
            $mediaExists = DB::table('media')->where('id', (int) $thumbnailId)->exists();
            $featuredImageId = $mediaExists ? (int) $thumbnailId : null;
        }

        // ── Category ─────────────────────────────────────────────────────
        // WHY THREE TABLES: WordPress category assignments live in
        // wp_term_relationships (post ↔ term join table), filtered by
        // wp_term_taxonomy where taxonomy = 'category'.
        $categoryId = $this->resolveCategory($wpPost->ID);

        // ── Author ───────────────────────────────────────────────────────
        // Verify the author exists in NewsPilot (was migrated in Step 1)
        $authorExists = DB::table('users')->where('id', $wpPost->post_author)->exists();
        if (!$authorExists) {
            // If author is missing (edge case), assign to the first admin
            $firstAdmin = DB::table('users')->where('portal_type', 'admin')->value('id');
            $authorId = $firstAdmin ?? 1;
        } else {
            $authorId = $wpPost->post_author;
        }

        // ── Status Mapping ───────────────────────────────────────────────
        // WHY MAP: WordPress uses 'publish', 'draft', 'pending', 'private'.
        // NewsPilot uses 'published', 'draft', 'pending_review', etc.
        $status = match ($wpPost->post_status) {
            'publish' => 'published',
            'draft' => 'draft',
            'pending' => 'pending_review',
            'future' => 'scheduled',
            default => 'draft',
        };

        // ── Insert Post Row ───────────────────────────────────────────────
        DB::table('posts')->insert([
            'id' => $wpPost->ID,
            'type' => 'post',
            'category_id' => $categoryId,
            'subcategory_id' => null,
            'author_id' => $authorId,
            'default_language_id' => $this->defaultLanguageId,
            'status' => $status,
            'visibility' => 'public',
            'is_featured' => false,
            'is_breaking' => false,
            'is_trending' => false,
            'is_editors_pick' => false,
            'is_sponsored' => false,
            'is_premium' => false,
            // WHY USE ORIGINAL COMMENT_STATUS:
            // WordPress tracks whether comments are open or closed per post.
            // We preserve that setting instead of enabling comments on everything.
            'allow_comments' => $wpPost->comment_status === 'open',
            'published_at' => $wpPost->post_date,
            'scheduled_at' => null,
            'breaking_expires_at' => null,
            'view_count' => 0,
            'like_count' => 0,
            'share_count' => 0,
            'comment_count' => (int) ($wpPost->comment_count ?? 0),
            'featured_image_id' => $featuredImageId,
            'source_name' => null,
            'source_url' => null,
            'rss_source_id' => null,
            'created_by' => $authorId,
            'updated_by' => $authorId,
            'created_at' => $wpPost->post_date,
            'updated_at' => $wpPost->post_modified,
            'deleted_at' => null,
        ]);

        // ── Insert Translation Row ────────────────────────────────────────
        // WHY: NewsPilot doesn't store title/content in `posts`. They live in
        // `post_translations` so each post can have versions in multiple languages.
        // We insert the English (WordPress) version as the default translation.
        if (!DB::table('post_translations')->where('post_id', $wpPost->ID)->exists()) {

            $translationData = [
                'post_id' => $wpPost->ID,
                'language_id' => $this->defaultLanguageId,
                'title' => $wpPost->post_title ?: '(Untitled)',
                'slug' => $wpPost->post_name ?: 'post-' . $wpPost->ID,
                'excerpt' => $this->cleanExcerpt($wpPost->post_excerpt),
                // WHY STORE CONTENT AS-IS:
                // If this site uses Elementor, post_content is JSON, not HTML.
                // We store it anyway — in Phase 3 we'll add Elementor rendering.
                // If the site uses the Classic editor, this is clean HTML already.
                'content' => $wpPost->post_content ?: '',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Add meta fields only if the columns exist
            // WHY: Not all NewsPilot versions have meta_title / meta_description
            // on post_translations. This check avoids a crash if they don't.
            $this->addSeoMeta($translationData, $wpPost->ID);

            DB::table('post_translations')->insert($translationData);
        }

        return 'migrated';
    }

    /**
     * Get the first WordPress category assigned to this post.
     *
     * WHY 'FIRST':
     * NewsPilot's posts table has a single category_id column (not a many-to-many).
     * WordPress allows multiple categories per post. We take the first/primary one.
     * If we want multi-category support in NewsPilot, that's a Phase 4 enhancement.
     */
    private function resolveCategory(int $postId): ?int
    {
        $termId = $this->wp()
            ->table('wp_term_relationships as tr')
            ->join('wp_term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', 'category')
            ->orderBy('tr.term_order')
            ->value('tt.term_id');

        if (!$termId) {
            return null;
        }

        // Verify this category exists in NewsPilot after our migration
        $exists = DB::table('categories')->where('id', $termId)->exists();
        return $exists ? (int) $termId : null;
    }

    /**
     * Clean WordPress auto-generated excerpts.
     *
     * WHY: WordPress sometimes leaves [&hellip;] or [...] in excerpts.
     * We strip those out so the excerpt reads cleanly in NewsPilot.
     */
    private function cleanExcerpt(?string $excerpt): ?string
    {
        if (!$excerpt)
            return null;
        $cleaned = str_replace(['[&hellip;]', '[...]', '&#8230;'], '', $excerpt);
        return trim(strip_tags($cleaned)) ?: null;
    }

    /**
     * Add SEO meta from wp_postmeta (Yoast or RankMath) if columns exist.
     *
     * WHY: Both Yoast SEO and RankMath store meta titles and descriptions in
     * wp_postmeta. We pull those in so the SEO value from WordPress is preserved.
     */
    private function addSeoMeta(array &$data, int $postId): void
    {
        $schema = DB::getSchemaBuilder();

        if ($schema->hasColumn('post_translations', 'meta_title')) {
            // Try Yoast first, then RankMath
            $metaTitle = $this->wp()->table('wp_postmeta')
                ->where('post_id', $postId)
                ->whereIn('meta_key', ['_yoast_wpseo_title', 'rank_math_title'])
                ->value('meta_value');
            $data['meta_title'] = $metaTitle ?: null;
        }

        if ($schema->hasColumn('post_translations', 'meta_description')) {
            $metaDesc = $this->wp()->table('wp_postmeta')
                ->where('post_id', $postId)
                ->whereIn('meta_key', ['_yoast_wpseo_metadesc', 'rank_math_description'])
                ->value('meta_value');
            $data['meta_description'] = $metaDesc ?: null;
        }
    }
}
