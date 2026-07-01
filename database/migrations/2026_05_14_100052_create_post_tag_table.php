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
     * Standard many-to-many pivot between posts and tags. Composite PK
     * + cascade deletes — when either side is hard-deleted the pivot
     * row vanishes, no orphans.
     *
     * Required by MergeTagsAction::remapPivotRows() — its Schema::hasTable()
     * check noticed the absence earlier; this migration fulfils it.
     */
    public function up(): void
    {
        Schema::create('post_tag', function (Blueprint $table): void {
            $table->foreignId('post_id')
                ->constrained('posts')
                ->cascadeOnDelete();

            $table->foreignId('tag_id')
                ->constrained('tags')
                ->cascadeOnDelete();

            $table->timestamp('created_at')->nullable();

            $table->primary(['post_id', 'tag_id']);
            $table->index('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_tag');
    }
};
