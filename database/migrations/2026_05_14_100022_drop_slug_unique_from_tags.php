<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The original tags table declared `slug` as globally unique. With
     * per-language tag_translations, slugs are now unique per language
     * (different locales can each have their own slug). Drop the global
     * uniqueness so admins can add e.g. an English "tech" tag and a
     * future Bangla "প্রযুক্তি" (slug: "projukti") that map to different
     * tag IDs without collisions on the legacy `tags.slug` column.
     *
     * `tags.slug` itself stays — it mirrors the default-language
     * translation for legacy UI compat.
     */
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table): void {
            $table->dropUnique('tags_slug_unique');

            // Add a non-unique index so existing search/lookup queries on
            // tags.slug stay fast.
            $table->index('slug', 'tags_slug_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Recreate the global unique constraint. NOTE: this can fail if the
     * application has by then created multiple translation rows that
     * share a slug across languages.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table): void {
            $table->dropIndex('tags_slug_index');
            $table->unique('slug', 'tags_slug_unique');
        });
    }
};
