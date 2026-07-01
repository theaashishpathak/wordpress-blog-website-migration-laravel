<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account Deletion Requests — GDPR right-to-erasure. Soft-scheduled with a
 * 30-day grace period (scheduled_for). User can cancel anytime before the
 * grace expires. A scheduled job runs daily and hard-deletes due requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 60)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('scheduled_for');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scheduled_for'], 'adr_user_scheduled_idx');
            $table->index(
                ['scheduled_for', 'cancelled_at', 'processed_at'],
                'adr_pending_due_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
    }
};
