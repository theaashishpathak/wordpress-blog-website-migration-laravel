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
     * Per-locale fields for Category: name, slug (unique per language),
     * description, meta_title, meta_description. One row per
     * (category, language) pair.
     */
    public function up(): void
    {
        Schema::create('category_translations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->foreignId('language_id')
                ->constrained('languages')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');

            $table->text('description')->nullable();

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();

            // One translation per (category, language) — and slug uniqueness
            // scoped per language so /en/technology and /bn/technology can coexist.
            $table->unique(['category_id', 'language_id'], 'cat_trans_unique');
            $table->unique(['language_id', 'slug'], 'cat_trans_slug_per_lang');

            $table->index('language_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_translations');
    }
};
