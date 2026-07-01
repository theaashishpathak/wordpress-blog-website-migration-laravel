<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('name')->nullable();

            // Lifecycle
            $table->string('status')->default('pending');  // pending | confirmed | unsubscribed | bounced | complained
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            // Tokens — used for double-opt-in confirmation + one-click unsubscribe.
            // Indexed for fast O(1) lookup from the confirmation URL.
            $table->string('confirmation_token', 80)->unique();
            $table->string('unsubscribe_token', 80)->unique();

            // Provenance
            $table->string('source')->nullable();           // 'footer_form', 'inline_widget', 'api', etc.
            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();

            // Free-form tags / segments for future campaign targeting (Phase 6A+).
            $table->json('tags')->nullable();

            $table->timestamps();

            // Same email may appear twice IF the previous record was
            // unsubscribed — we soft-allow re-subscribe by inserting a
            // new row. Use a partial unique on (email, status) when
            // status != 'unsubscribed' would be ideal, but MySQL lacks
            // partial indexes. Application-level dedupe in the Action.
            $table->index('email');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
