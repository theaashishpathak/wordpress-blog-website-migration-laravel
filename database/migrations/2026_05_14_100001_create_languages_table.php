<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `languages` table that drives multi-language URL routing,
     * per-locale slug uniqueness, RTL support, and AI translation workflow.
     * Authoritative spec: docs/Multilanguage Schema.txt Section 2.
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();

            // ISO 639-1 / BCP 47 locale code — drives /{locale}/... URL prefix.
            $table->string('code', 10)->unique();

            // English display name (used in admin UI dropdowns).
            $table->string('name', 100);

            // Native script name (used on frontend language switcher).
            $table->string('native_name', 100);

            // Optional flag emoji and image path for UI presentation.
            $table->string('flag_emoji', 10)->nullable();
            $table->string('flag_icon')->nullable();

            // Text direction — drives RTL frontend layouts.
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr');

            // Exactly one language should have is_default=true at any time
            // (enforced at application/seeder layer, not DB constraint).
            $table->boolean('is_default')->default(false);

            // Public-facing toggle; inactive languages hide from frontend
            // switcher but their existing translations remain in DB.
            $table->boolean('is_active')->default(true);

            // Whether this locale shows up as an admin/author UI language.
            $table->boolean('is_admin_locale')->default(false);

            // Manual ordering for switcher dropdown.
            $table->integer('sort_order')->default(0);

            // Carbon/locale formatting identifier (e.g., "bn_BD", "en_US").
            $table->string('locale_php')->nullable();

            // Per-locale date/number formats (json) for future UI helpers.
            $table->json('date_format')->nullable();
            $table->json('number_format')->nullable();

            $table->timestamps();

            // Hot path: active-language lookups on every public request.
            $table->index('is_active');
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
