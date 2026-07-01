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
     * Per-locale fields for Page. Note `is_published` here is *per locale*
     * — distinct from `pages.status`. A page can be `status=published`
     * overall, with the English translation `is_published=true` and the
     * Bangla translation still `is_published=false` (translation draft).
     */
    public function up(): void
    {
        Schema::create('page_translations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('page_id')
                ->constrained('pages')
                ->cascadeOnDelete();

            $table->foreignId('language_id')
                ->constrained('languages')
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->longText('content')->nullable();

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();

            // Per-locale publishing gate. Together with `pages.status` this
            // controls whether the localized page is served on the frontend:
            //   pages.status = 'published' AND translation.is_published = true
            $table->boolean('is_published')->default(false);

            $table->timestamps();

            $table->unique(['page_id', 'language_id'], 'page_trans_unique');
            $table->unique(['language_id', 'slug'], 'page_trans_slug_per_lang');

            $table->index('language_id');
            $table->index(['language_id', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_translations');
    }
};
