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
     * Editorial notes capture the "why" behind every editorial workflow
     * transition. Every Approve / Reject / RequestChanges action writes
     * a row here so the post's audit trail is complete.
     *
     * Authoritative spec: docs/Architecture.txt Section "Editorial Workflow Module".
     */
    public function up(): void
    {
        Schema::create('editorial_notes', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('post_id')
                ->constrained('posts')
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('type', [
                'approve', 'reject', 'request_changes',
                'internal_comment', 'ai_suggestion',
            ])->default('internal_comment');

            $table->text('body');

            // @mention support — IDs of users notified by this note.
            $table->json('mention_user_ids')->nullable();

            // false → visible to author; true → editor-only.
            $table->boolean('is_internal')->default(false);

            $table->timestamps();

            $table->index(['post_id', 'created_at']);
            $table->index(['author_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editorial_notes');
    }
};
