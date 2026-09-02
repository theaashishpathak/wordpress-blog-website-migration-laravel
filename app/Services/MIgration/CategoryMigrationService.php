<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CategoryMigrationService
 *
 * Migrates WordPress categories into Rupantrix's `categories` and `category_translations` tables,
 * preserving parent/child hierarchy, slugs, descriptions, and SEO metadata.
 */
class CategoryMigrationService extends BaseMigrationService
{
    private int $defaultLanguageId = 1;
    private SeoMigrationService $seoMigrator;

    public function __construct(SeoMigrationService $seoMigrator)
    {
        $this->seoMigrator = $seoMigrator;
    }

    public function run(): void
    {
        $lang = DB::table('languages')->where('code', 'en')->first();
        if ($lang) {
            $this->defaultLanguageId = $lang->id;
        }

        $this->info('Starting Category migration...');

        // Fetch WordPress categories
        $wpCategories = $this->wp()
            ->table('wp_terms as t')
            ->join('wp_term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
            ->where('tt.taxonomy', 'category')
            ->select(
                't.term_id',
                't.name',
                't.slug',
                'tt.parent',
                'tt.description',
                'tt.count'
            )
            ->get();

        $this->info("Found {$wpCategories->count()} WordPress categories.");

        $migrated = 0;

        foreach ($wpCategories as $wpCat) {
            $parentId = (int) $wpCat->parent > 0 ? (int) $wpCat->parent : null;

            // 1. Insert or update category
            DB::table('categories')->updateOrInsert(
                ['id' => $wpCat->term_id],
                [
                    'parent_id' => $parentId,
                    'image_id' => null,
                    'icon' => null,
                    'color' => null,
                    'show_in_menu' => true,
                    'show_on_homepage' => false,
                    'is_featured' => false,
                    'sort_order' => 0,
                    'layout' => 'grid',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // 2. Insert or update category translation
            $translationData = [
                'name' => $wpCat->name,
                'slug' => $wpCat->slug,
                'description' => $wpCat->description ?: null,
                'meta_title' => $wpCat->name,
                'meta_description' => $wpCat->description ?: null,
                'updated_at' => now(),
                'created_at' => now(),
            ];

            DB::table('category_translations')->updateOrInsert(
                [
                    'category_id' => $wpCat->term_id,
                    'language_id' => $this->defaultLanguageId,
                ],
                $translationData
            );

            // 3. Migrate SEO metadata for this category
            $this->seoMigrator->migrateTermSeo('App\\Models\\Category', (int) $wpCat->term_id);

            $this->info("  ✓ Migrated: {$wpCat->name} (slug: {$wpCat->slug})");
            $migrated++;
        }

        $this->info("  Done. Categories migrated/updated: {$migrated}");
    }
}
