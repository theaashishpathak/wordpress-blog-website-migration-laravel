<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();

            // Subject of the comment
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();

            // Threading — null for top-level, foreign key to another comment for replies.
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();

            // Authorship — auth'd user OR guest pair
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_website')->nullable();

            // Content
            $table->text('body');

            // Moderation lifecycle
            $table->string('status', 20)->default('pending');   // pending | approved | spam | trash
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();

            // Spam provenance / anti-abuse
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Hot read path: post page asks for "approved comments of this post".
            $table->index(['post_id', 'status']);
            // Admin moderation queue: latest pending first.
            $table->index(['status', 'created_at']);
            // Replies tree under a parent.
            $table->index(['parent_id', 'status']);
            // Returning-commenter check ("has this email had a comment approved before?").
            $table->index(['guest_email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
