<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Append-only version history for posts. Every UpdatePostAction call
     * snapshots the pre-edit state here so editors can diff "what changed"
     * during review and admins can roll back a bad edit.
     *
     * Authoritative spec: docs/Architecture.txt Section "Editorial Workflow Module".
     */
    public function up(): void
    {
        Schema::create('post_revisions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('post_id')
                ->constrained('posts')
                ->cascadeOnDelete();

            // Sequential within a post — revision #1 is the pre-edit
            // snapshot of the first update, #2 the next, etc.
            $table->unsignedInteger('revision_number');

            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Full serialized Post + translations + tag IDs at snapshot time.
            // No structured columns — schema may evolve and snapshots must
            // survive untouched.
            $table->json('snapshot');

            // Optional human-readable summary of what changed.
            $table->text('summary')->nullable();

            // No updated_at — revisions are immutable.
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['post_id', 'revision_number'], 'post_rev_unique');
            $table->index(['author_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_revisions');
    }
};
