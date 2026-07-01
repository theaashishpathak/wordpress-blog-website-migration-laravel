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
     * Versioned prompts power the AI Action library — each generation
     * records the (template_key, template_version) it used so any output
     * can be reproduced months later by replaying the canonical prompt.
     *
     * Authoritative spec: docs/AI Provider Contract.txt Section 9.
     */
    public function up(): void
    {
        Schema::create('ai_prompt_templates', function (Blueprint $table): void {
            $table->id();

            // Logical name: "article_writer.long_form", "seo_meta.default", etc.
            $table->string('key', 100);

            $table->unsignedSmallInteger('version')->default(1);

            // BCP-47 locale code matching languages.code.
            $table->string('locale', 10);

            // System prompt — sets the model's role / tone / constraints.
            $table->text('system_prompt');

            // User prompt with {{variable}} placeholders interpolated by
            // PromptBuilder at runtime.
            $table->text('user_prompt_template');

            // Expected variables — list of names PromptBuilder must receive.
            $table->json('variables')->nullable();

            // Recommended model + temperature for this template (UI hints,
            // not enforced — admin can override at call site).
            $table->string('model_hint')->nullable();
            $table->decimal('temperature_hint', 4, 2)->nullable();

            // Only one active version per (key, locale) at a time.
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['key', 'locale', 'version'], 'ai_prompts_unique');
            $table->index(['key', 'locale', 'is_active']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_templates');
    }
};
