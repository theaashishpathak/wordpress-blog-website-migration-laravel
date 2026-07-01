<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_logs', function (Blueprint $table): void {
            $table->string('country', 64)->nullable()->after('device_type');
            $table->string('country_code', 8)->nullable()->after('country');
            $table->string('city', 96)->nullable()->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('login_logs', function (Blueprint $table): void {
            $table->dropColumn(['country', 'country_code', 'city']);
        });
    }
};
