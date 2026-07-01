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
     * Single posts table with `type` discriminator handles every content
     * format (post, news, page_article, video, gallery, short). Per-locale
     * title/slug/content lives on post_translations.
     *
     * Authoritative spec: docs/Multilanguage Schema.txt Section 3.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();

            // Discriminator — see App\Enums\PostType.
            $table->enum('type', ['post', 'news', 'page_article', 'video', 'gallery', 'short'])
                ->default('post');

            // Primary + optional sub-category.
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('subcategory_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            // Author + per-post fallback language for translations.
            $table->foreignId('author_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('default_language_id')
                ->constrained('languages');

            // Editorial lifecycle — see App\Enums\PostStatus.
            $table->enum('status', [
                'draft', 'pending_review', 'in_review', 'changes_requested',
                'approved', 'scheduled', 'published', 'unpublished',
                'rejected', 'archived',
            ])->default('draft');

            $table->enum('visibility', ['public', 'private', 'password_protected', 'premium'])
                ->default('public');

            // Display flags.
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_breaking')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_editors_pick')->default(false);
            $table->boolean('is_sponsored')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->boolean('allow_comments')->default(true);

            // Timeline.
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('breaking_expires_at')->nullable();

            // Counters — cached aggregates updated by Observer / queued jobs.
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);

            // Media + attribution.
            $table->foreignId('featured_image_id')
                ->nullable()
                ->constrained('media')
                ->nullOnDelete();

            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();

            // rss_sources table lands in a later phase; keep as plain
            // unsigned for now and add the FK when the table exists.
            $table->unsignedBigInteger('rss_source_id')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Hot-path indexes.
            $table->index(['type', 'status', 'published_at']);
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['author_id', 'status']);
            $table->index(['is_featured', 'published_at']);
            $table->index(['is_breaking', 'breaking_expires_at']);
            $table->index(['scheduled_at']);
            $table->index('rss_source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
