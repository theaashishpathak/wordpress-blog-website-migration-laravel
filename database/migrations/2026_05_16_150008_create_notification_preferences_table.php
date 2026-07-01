<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notification Preferences — per-user matrix of (event × channel).
 * Adding a new event type later doesn't need a migration, just new rows.
 *
 * Example keys: comment_reply, comment_approved, author_published,
 * weekly_digest, newsletter_daily, follower_new, mention_in_comment.
 * Channels: email, in_app, push.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            $table->enum('channel', ['email', 'in_app', 'push']);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'key', 'channel'], 'notif_prefs_user_key_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
