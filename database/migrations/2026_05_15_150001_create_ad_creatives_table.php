<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_creatives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('zone_id')->constrained('ad_zones')->cascadeOnDelete();

            $table->string('name');

            // Rendering mode:
            //   image     — Media row + click-through URL
            //   html      — raw <script>/<iframe>/HTML snippet (e.g. AdSense)
            //   sponsored — link card pointing at a post or external article
            $table->string('type', 20)->default('image');

            // image | sponsored use these:
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('target_url', 1000)->nullable();
            $table->string('alt_text')->nullable();

            // html mode payload:
            $table->text('html_code')->nullable();

            // Lifecycle + scheduling
            $table->string('status', 20)->default('draft');     // draft | active | paused | expired
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            // Priority within the zone (lower = shown more often when
            // the zone has multiple eligible creatives).
            $table->unsignedSmallInteger('priority')->default(100);

            // Metrics — denormalised counters for hot reads. Detailed
            // event log (Phase 7 polish) would join against this for
            // time-series breakdowns.
            $table->unsignedBigInteger('impression_count')->default(0);
            $table->unsignedBigInteger('click_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['zone_id', 'status', 'start_at', 'end_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_creatives');
    }
};
