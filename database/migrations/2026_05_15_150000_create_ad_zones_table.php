<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_zones', function (Blueprint $table): void {
            $table->id();

            // Stable identifier used in blade templates as a slot key.
            // e.g. 'homepage_top', 'sidebar_300x250', 'in_article_inline'.
            $table->string('key')->unique();

            $table->string('name');
            $table->text('description')->nullable();

            // Suggested rendering geometry — used to size placeholders
            // when an ad zone is empty.
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();

            // Conceptual placement — populated as a hint for editors.
            $table->string('position')->nullable();   // top | sidebar | inline | footer | popup

            $table->boolean('is_active')->default(true);

            // Cap how many creatives can render simultaneously in this
            // zone (1 = single-banner, >1 = rotation cap).
            $table->unsignedTinyInteger('max_creatives')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_zones');
    }
};
