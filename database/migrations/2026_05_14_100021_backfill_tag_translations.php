<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Copies every existing tags.name + tags.slug into a default-language
     * tag_translations row. Idempotent — skips tags that already have a
     * translation for the resolved default language. Safe to re-run on
     * top of partial migrations.
     */
    public function up(): void
    {
        $defaultLanguageId = DB::table('languages')
            ->where('is_default', true)
            ->value('id');

        // If LanguageSeeder hasn't run yet (fresh install), fall back to
        // the first active language; if none exists, abort gracefully so
        // the migration doesn't crash a brand-new install.
        if ($defaultLanguageId === null) {
            $defaultLanguageId = DB::table('languages')
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id');
        }

        if ($defaultLanguageId === null) {
            return;
        }

        DB::table('tags')->orderBy('id')->chunkById(200, function ($tags) use ($defaultLanguageId): void {
            foreach ($tags as $tag) {
                $exists = DB::table('tag_translations')
                    ->where('tag_id', $tag->id)
                    ->where('language_id', $defaultLanguageId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('tag_translations')->insert([
                    'tag_id' => $tag->id,
                    'language_id' => $defaultLanguageId,
                    'name' => (string) $tag->name,
                    'slug' => (string) $tag->slug,
                    'description' => null,
                    'meta_title' => null,
                    'meta_description' => null,
                    'created_at' => $tag->created_at ?? now(),
                    'updated_at' => $tag->updated_at ?? now(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Wipe the backfilled rows; the parent tags table is untouched so the
     * application keeps working from legacy `tags.name` / `tags.slug`.
     */
    public function down(): void
    {
        DB::table('tag_translations')->truncate();
    }
};
