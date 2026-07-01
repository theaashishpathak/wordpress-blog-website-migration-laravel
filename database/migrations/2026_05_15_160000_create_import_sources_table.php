<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_sources', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('feed_url', 1000);

            // Target taxonomy + locale for posts created from this feed.
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('default_language_id')->constrained('languages')->cascadeOnDelete();

            // Lifecycle
            $table->string('status', 20)->default('active');     // active | paused | error
            $table->boolean('auto_publish')->default(false);

            // Post-type hint: most newsroom integrations want 'news', others 'post'.
            $table->string('default_post_type', 20)->default('news');

            // Scheduling — the rss:import command skips sources whose
            // last_fetched_at is within this many minutes.
            $table->unsignedInteger('fetch_interval_minutes')->default(60);
            $table->timestamp('last_fetched_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('item_count')->default(0);   // running tally

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'last_fetched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_sources');
    }
};
