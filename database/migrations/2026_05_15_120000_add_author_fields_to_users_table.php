<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('bio')->nullable()->after('date_of_birth');
            $table->json('social_links')->nullable()->after('bio');
            $table->string('public_slug')->nullable()->unique()->after('social_links');
            $table->boolean('show_in_team')->default(false)->after('public_slug');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['bio', 'social_links', 'public_slug', 'show_in_team']);
        });
    }
};
