<?php

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;

/**
 * CategoryMigrationService
 *
 * WHAT IT DOES:
 * Reads WordPress categories from three joined tables and inserts them into
 * NewsPilot's `categories` table AND `category_translations` table.
 *
 * WHY THREE WORDPRESS TABLES:
 * WordPress splits category data across three tables:
 * - wp_terms          → stores the name and slug
 * - wp_term_taxonomy  → stores the type ('category' vs 'post_tag') and parent_id
 * - wp_termmeta       → stores extra meta like descriptions (we use this for description)
 *
 * WHY TWO NEWSPILOT TABLES:
 * NewsPilot is built for multilingual support. Non-translatable structural data
 * (parent, sort order, layout) lives in `categories`. The actual name and slug
 * that readers see lives in `category_translations`. So we must insert into BOTH.
 *
 * WHY WE NEED THIS BEFORE POSTS:
 * NewsPilot's posts table has `category_id` as a foreign key to categories.
 * If a post references category ID 3 but category 3 doesn't exist yet, MySQL
 * will reject the post insert. Categories must exist first.
 *
 * FIELD MAPPING:
 * wp_terms.term_id              → categories.id        (keep same ID, see UserMigration for why)
 * wp_terms.name                 → category_translations.name
 * wp_terms.slug                 → category_translations.slug
 * wp_term_taxonomy.parent       → categories.parent_id
 * wp_term_taxonomy.description  → category_translations.meta_description (if column exists)
 */
class CategoryMigrationService extends BaseMigrationService
{
    // WHY 'en':
    // NewsPilot's category_translations needs a language_id. We check what ID
    // corresponds to English in the languages table at runtime.
    private int $defaultLanguageId = 1;

    public function run(): void
    {
        // Resolve the English language ID from NewsPilot's languages table
        // WHY: The language_id FK must point to a real row in the languages table.
        $lang = DB::table('languages')->where('code', 'en')->first();
        if ($lang) {
            $this->defaultLanguageId = $lang->id;
        } else {
            // If no languages table exists yet or no 'en' row, use ID 1 as fallback
            $this->warn('  No "en" row found in languages table — using language_id = 1');
        }

        // Join wp_terms + wp_term_taxonomy to get categories only (not tags)
        // WHY WHERE taxonomy = 'category': WordPress uses the same tables for
        // categories, tags, and custom taxonomies. We only want actual categories.
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
                'tt.count',
            )
            ->get();

        $this->info("Found {$wpCategories->count()} WordPress categories.");

        $migrated = 0;
        $skipped = 0;

        foreach ($wpCategories as $wpCat) {

            // Skip if already exists (safe re-run)
            $exists = DB::table('categories')->where('id', $wpCat->term_id)->exists();
            if ($exists) {
                $this->warn("  Skipped (already exists): {$wpCat->name}");
                $skipped++;
                continue;
            }

            // Insert into NewsPilot `categories` table
            // WHY SHOW_IN_MENU = TRUE: Most WordPress categories are shown in menus.
            // You can change specific ones manually in the NewsPilot admin later.
            DB::table('categories')->insert([
                'id' => $wpCat->term_id,
                'parent_id' => $wpCat->parent > 0 ? $wpCat->parent : null,
                // WHY NULL FOR IMAGE: WordPress category images are stored in a
                // plugin (like WP Term Images). We skip images for now — you can
                // add them manually after migration.
                'image_id' => null,
                'icon' => null,
                'color' => null,
                'show_in_menu' => true,
                'show_on_homepage' => false,
                'is_featured' => false,
                'sort_order' => 0,
                'layout' => 'grid',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert into NewsPilot `category_translations` table
            // WHY SEPARATE TABLE: NewsPilot supports multiple languages. The name
            // and slug are translatable, so they live here. For now we insert the
            // English (default) version of the name and slug from WordPress.
            $translationExists = DB::table('category_translations')
                ->where('category_id', $wpCat->term_id)
                ->where('language_id', $this->defaultLanguageId)
                ->exists();

            if (!$translationExists) {
                $translationData = [
                    'category_id' => $wpCat->term_id,
                    'language_id' => $this->defaultLanguageId,
                    'name' => $wpCat->name,
                    'slug' => $wpCat->slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Add meta_description only if the column exists
                // WHY: Some NewsPilot versions may not have this column yet.
                if ($this->columnExists('category_translations', 'meta_description')) {
                    $translationData['meta_description'] = $wpCat->description ?: null;
                }

                DB::table('category_translations')->insert($translationData);
            }

            $this->info("  ✓ Migrated: {$wpCat->name} (slug: {$wpCat->slug})");
            $migrated++;
        }

        $this->info("  Done. Migrated: {$migrated}, Skipped: {$skipped}");
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
}
