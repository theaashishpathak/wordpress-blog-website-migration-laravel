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
     * The original categories migration declared image_id as a plain
     * `unsignedBigInteger` because the `media` table didn't exist yet.
     * Now that media is here, formalize the relationship — nullOnDelete
     * so deleting a media row resets the category's image_id to null
     * (frontend just renders the placeholder).
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->foreign('image_id')
                ->references('id')
                ->on('media')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropForeign(['image_id']);
        });
    }
};
