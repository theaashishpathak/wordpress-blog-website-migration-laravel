<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reading History — auto-tracked when a logged-in visitor reads a post.
 * One row per user/post pair; subsequent reads bump last_read_at + read_count.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->timestamp('first_read_at')->useCurrent();
            $table->timestamp('last_read_at')->useCurrent();
            $table->unsignedInteger('read_count')->default(1);
            $table->unsignedInteger('read_duration_seconds')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'post_id']);
            $table->index(['user_id', 'last_read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_history');
    }
};
