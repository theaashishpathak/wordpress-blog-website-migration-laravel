<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reading List — a "read later" queue. Items can be dismissed (soft removed)
 * which keeps history of what was queued without polluting the active list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_list_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->timestamp('added_at')->useCurrent();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'post_id']);
            $table->index(['user_id', 'dismissed_at', 'added_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_list_items');
    }
};
