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
     * Categories carry non-translatable structure (parent, sort_order,
     * image, icon, color, layout type). Per-locale name + slug + meta
     * live in `category_translations`. Authoritative spec:
     * docs/Multilanguage Schema.txt Section 5.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();

            // Self-referential tree — null = top-level.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            // Featured image FK — `media` table arrives in Phase 2D
            // (Spatie MediaLibrary). Nullable + no constraint for now;
            // a later migration can add the FK once `media` exists.
            $table->unsignedBigInteger('image_id')->nullable();

            $table->string('icon')->nullable();            // lucide icon name
            $table->string('color', 16)->nullable();       // hex code e.g. #4f46e5

            $table->boolean('show_in_menu')->default(true);
            $table->boolean('show_on_homepage')->default(false);
            $table->boolean('is_featured')->default(false);

            $table->integer('sort_order')->default(0);

            $table->enum('layout', ['grid', 'list', 'magazine', 'sidebar', 'full'])
                ->default('grid');

            $table->timestamps();
            $table->softDeletes();

            // Hot-path indexes.
            $table->index(['parent_id', 'sort_order']);
            $table->index(['show_in_menu', 'sort_order']);
            $table->index(['show_on_homepage', 'sort_order']);
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
