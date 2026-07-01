<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename portal_type values to match the three real portals NewsPilot ships:
 *   - 'staff'  → 'author'  (writers / editors with admin access)
 *   - 'client' → 'visitor' (frontend readers, subscribers, commenters)
 *   - 'admin'  unchanged
 *
 * The migration relaxes the ENUM to VARCHAR, rewrites the values, then
 * re-applies the ENUM constraint with the new set. Works on both MySQL
 * (real ENUM type) and SQLite (CHECK constraint).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Phase 1 — relax the column so existing ENUM check doesn't reject
        // the upcoming UPDATE statements.
        Schema::table('users', function (Blueprint $table): void {
            $table->string('portal_type', 20)->default('author')->change();
        });

        // Phase 2 — migrate values.
        DB::table('users')->where('portal_type', 'staff')->update(['portal_type' => 'author']);
        DB::table('users')->where('portal_type', 'client')->update(['portal_type' => 'visitor']);

        // Phase 3 — re-apply ENUM with the new value set.
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('portal_type', ['admin', 'author', 'visitor'])->default('author')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('portal_type', 20)->default('staff')->change();
        });

        DB::table('users')->where('portal_type', 'author')->update(['portal_type' => 'staff']);
        DB::table('users')->where('portal_type', 'visitor')->update(['portal_type' => 'client']);

        Schema::table('users', function (Blueprint $table): void {
            $table->enum('portal_type', ['admin', 'staff', 'client'])->default('staff')->change();
        });
    }
};
