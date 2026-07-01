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
     * Polymorphic, locale-aware SEO meta storage. Used by Post, Page,
     * Category, Tag, Author when an admin needs to override the defaults
     * that come from inline post_translations/page_translations columns
     * — schema markup type, OG/Twitter card image overrides, etc.
     *
     * language_id is nullable so non-translatable seoables (e.g., an
     * Author profile that has only a single global SEO record) can share
     * a single row.
     *
     * Authoritative spec: docs/Multilanguage Schema.txt Section 8.
     */
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table): void {
            $table->id();

            // seoable_type + seoable_id polymorphic FK.
            $table->morphs('seoable');

            $table->foreignId('language_id')
                ->nullable()
                ->constrained('languages')
                ->cascadeOnDelete();

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('focus_keyword')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->nullable();          // index,follow | noindex,nofollow

            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();

            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();

            $table->string('schema_type')->nullable();     // Article | NewsArticle | FAQPage | ...
            $table->json('schema_data')->nullable();

            $table->unsignedTinyInteger('seo_score')->nullable();

            $table->timestamps();

            $table->unique(
                ['seoable_type', 'seoable_id', 'language_id'],
                'seo_metas_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
