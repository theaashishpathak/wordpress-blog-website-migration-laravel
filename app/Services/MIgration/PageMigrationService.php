<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PageMigrationService
 *
 * Migrates published WordPress pages into Rupantrix's `pages` and `page_translations` tables,
 * running content through ContentTransformationService and preserving slugs and SEO metadata.
 */
class PageMigrationService extends BaseMigrationService
{
    private int $defaultLanguageId = 1;
    private ContentTransformationService $contentTransformer;
    private SeoMigrationService $seoMigrator;

    public function __construct(
        ContentTransformationService $contentTransformer,
        SeoMigrationService $seoMigrator
    ) {
        $this->contentTransformer = $contentTransformer;
        $this->seoMigrator = $seoMigrator;
    }

    public function run(): void
    {
        $lang = DB::table('languages')->where('code', 'en')->first();
        if ($lang) {
            $this->defaultLanguageId = $lang->id;
        }

        $this->info('Starting Pages migration...');

        // Fetch published WordPress pages
        $wpPages = $this->wp()
            ->table('wp_posts')
            ->where('post_type', 'page')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get();

        $this->info("Found {$wpPages->count()} published WordPress pages.");

        $migrated = 0;
        $skipped = 0;

        foreach ($wpPages as $wpPage) {
            $authorExists = DB::table('users')->where('id', $wpPage->post_author)->exists();
            $authorId = $authorExists ? $wpPage->post_author : (DB::table('users')->where('portal_type', 'admin')->value('id') ?? 1);

            // 1. Insert or update page record
            DB::table('pages')->updateOrInsert(
                ['id' => $wpPage->ID],
                [
                    'status' => 'published',
                    'template' => 'default',
                    'show_in_menu' => true,
                    'sort_order' => (int) $wpPage->menu_order,
                    'created_by' => $authorId,
                    'updated_by' => $authorId,
                    'created_at' => $wpPage->post_date,
                    'updated_at' => $wpPage->post_modified,
                ]
            );

            // 2. Transform content
            $cleanContent = $this->contentTransformer->transform($wpPage->post_content);

            // 3. Insert or update page translation
            DB::table('page_translations')->updateOrInsert(
                [
                    'page_id' => $wpPage->ID,
                    'language_id' => $this->defaultLanguageId,
                ],
                [
                    'title' => $wpPage->post_title ?: 'Page ' . $wpPage->ID,
                    'slug' => $wpPage->post_name ?: 'page-' . $wpPage->ID,
                    'content' => $cleanContent,
                    'meta_title' => $wpPage->post_title,
                    'meta_description' => null,
                    'og_image' => null,
                    'is_published' => true,
                    'created_at' => $wpPage->post_date,
                    'updated_at' => $wpPage->post_modified,
                ]
            );

            // 4. Migrate SEO meta for this page
            $this->seoMigrator->migrateEntitySeo('App\\Models\\Page', (int) $wpPage->ID, 'page');

            $this->info("  ✓ Migrated page: {$wpPage->post_title} (slug: {$wpPage->post_name})");
            $migrated++;
        }

        $this->info("  Done. Pages Migrated/Updated: {$migrated}, Skipped: {$skipped}");
    }
}
