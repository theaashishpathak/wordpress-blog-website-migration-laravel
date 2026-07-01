<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Login logs now capture failed + logout events alongside successful
     * logins. A `status` enum-style column discriminates between them
     * so the existing dashboards keep working (filter on status='success')
     * while security audits gain visibility into brute-force attempts.
     *
     *   success  — the normal case, user_id always set
     *   failed   — wrong password / unknown email; user_id may be null,
     *              attempted_email captures what the actor typed
     *   logout   — user explicitly signed out
     *
     * `attempted_email` exists only for failed attempts where we cannot
     * resolve a user_id. For success/logout it stays null.
     */
    public function up(): void
    {
        Schema::table('login_logs', function (Blueprint $table): void {
            $table->string('status', 16)->default('success')->after('login_at')->index();
            $table->string('attempted_email', 191)->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('login_logs', function (Blueprint $table): void {
            $table->dropColumn(['status', 'attempted_email']);
        });
    }
};
