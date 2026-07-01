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
     * Pages carry non-translatable lifecycle + structure. Per-locale title,
     * slug, content and meta live in `page_translations`.
     *
     * Authoritative spec: docs/Multilanguage Schema.txt Section 4.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            // Blade view template selector: "default", "full-width", "landing", ...
            $table->string('template')->default('default');

            $table->boolean('show_in_menu')->default(false);

            $table->integer('sort_order')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Hot-path indexes.
            $table->index(['status', 'sort_order']);
            $table->index(['show_in_menu', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
