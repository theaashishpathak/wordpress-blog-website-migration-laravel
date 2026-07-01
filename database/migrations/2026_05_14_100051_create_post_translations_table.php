<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per-locale fields for Post. Inline SEO meta keeps simple cases fast
     * (no extra JOIN to seo_metas needed). Translation lifecycle column
     * tracks AI-generated → human-reviewed → published progression.
     */
    public function up(): void
    {
        Schema::create('post_translations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('post_id')
                ->constrained('posts')
                ->cascadeOnDelete();

            $table->foreignId('language_id')
                ->constrained('languages')
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('reading_time')->nullable();    // e.g. "5 min read"

            // Inline SEO — per-locale critical fields. Advanced overrides
            // (schema, twitter card override) live in seo_metas.
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('focus_keyword')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
            $table->unsignedTinyInteger('seo_score')->nullable();

            // Translation pipeline.
            $table->enum('translation_status', [
                'manual', 'ai_generated', 'ai_reviewed', 'human_reviewed', 'published',
            ])->default('manual');

            $table->boolean('is_published')->default(false);

            $table->timestamp('translated_at')->nullable();

            $table->foreignId('translated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ai_translation_provider')->nullable();  // openai | gemini | claude

            $table->timestamps();

            $table->unique(['post_id', 'language_id'], 'post_trans_unique');
            $table->unique(['language_id', 'slug'], 'post_trans_slug_per_lang');
            $table->index(['language_id', 'is_published']);
            $table->index('focus_keyword');

            // Full-text search on title + content for Scout database driver.
            // SQLite doesn't support FULLTEXT, so guard it.
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->fullText(['title', 'content']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_translations');
    }
};
