<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imported_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained('import_sources')->cascadeOnDelete();

            // Either the feed's <guid> or a hash of <link>+<title> when
            // the feed doesn't supply a stable identifier.
            $table->string('guid', 500);

            $table->string('item_url', 1000)->nullable();
            $table->string('title', 500)->nullable();

            // Nullable because the post may have been deleted after import
            // — we keep the dedup entry so refetches don't re-create it.
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();

            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['source_id', 'guid']);
            $table->index('imported_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_items');
    }
};
