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
     * Adds per-locale tag_translations alongside the existing tags table.
     * The two will coexist: legacy `tags.name` / `tags.slug` columns stay
     * so the current TagFormModal UI keeps working, while new Action-based
     * code and the upcoming public frontend read from translations.
     *
     * Authoritative spec: docs/Multilanguage Schema.txt Section 6.
     */
    public function up(): void
    {
        Schema::create('tag_translations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tag_id')
                ->constrained('tags')
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

            $table->unique(['tag_id', 'language_id'], 'tag_trans_unique');
            $table->unique(['language_id', 'slug'], 'tag_trans_slug_per_lang');

            $table->index('language_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_translations');
    }
};
