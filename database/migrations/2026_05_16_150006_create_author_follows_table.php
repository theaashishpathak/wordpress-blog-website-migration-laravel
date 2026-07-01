<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Author Follows — visitor follows a writer/editor (author portal user).
 * notify_on_publish controls whether new published posts ping the follower.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('notify_on_publish')->default(true);
            $table->timestamps();

            $table->unique(['follower_id', 'author_id']);
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_follows');
    }
};
