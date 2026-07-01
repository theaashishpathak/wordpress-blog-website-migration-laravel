<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Highlights — Medium-style text annotations on articles. context_hash lets us
 * find the highlighted passage again if the article content changes (we hash
 * the surrounding paragraph, not just the selection).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('highlights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->text('selected_text');
            $table->text('note')->nullable();
            $table->unsignedInteger('start_offset')->nullable();
            $table->unsignedInteger('end_offset')->nullable();
            $table->char('context_hash', 40)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'post_id']);
            $table->index(['post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('highlights');
    }
};
