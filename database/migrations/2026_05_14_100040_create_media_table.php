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
     * Lightweight media library — single table referenced by FK from
     * categories.image_id, posts.featured_image_id, etc. Intentionally
     * minimal so CodeCanyon shared-hosting buyers don't have to install
     * Imagick or run heavyweight image processing pipelines on day 1.
     *
     * `conversions` JSON column reserves space for future WebP / responsive
     * variants. When Phase 3 adds image processing it can populate this
     * column with `{ 'webp_800': 'media/abc/webp_800.webp', ... }` without
     * a schema change.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();

            $table->string('disk', 50)->default('public');
            $table->string('path');                          // relative path on disk
            $table->string('filename');                      // stored filename
            $table->string('original_filename')->nullable(); // user-supplied name

            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);  // bytes

            // Image-only — nullable for documents / videos / audio.
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // Editorial metadata.
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->string('credit')->nullable();
            $table->string('source_url')->nullable();

            // Reserved for Phase 3 conversion variants.
            $table->json('conversions')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['uploaded_by', 'created_at']);
            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
