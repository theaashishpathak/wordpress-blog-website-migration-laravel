<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Topic Follows — polymorphic follow table that covers both Tags and
 * Categories under a single relation. follower_id = user_id (kept short
 * to match other follow tables). notify_on_post toggles new-post pings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('followable'); // followable_type + followable_id (Tag | Category)
            $table->boolean('notify_on_post')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'followable_type', 'followable_id'], 'topic_follows_user_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_follows');
    }
};
