<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * User Follows — visitor↔visitor social follows. Separate from author_follows
 * so we can enforce different visibility/privacy rules (public/private follow
 * lists, mutual-only DMs later, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('followed_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_id', 'followed_id']);
            $table->index('followed_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_follows');
    }
};
