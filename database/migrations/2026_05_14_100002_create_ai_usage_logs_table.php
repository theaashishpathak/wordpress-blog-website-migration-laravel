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
     * Every AIManager::complete() / stream() call writes one row here,
     * even on failure (status=failed). Powers the admin AI cost dashboard,
     * per-user quota enforcement, and feature-level usage breakdown.
     *
     * Authoritative spec: docs/AI Provider Contract.txt Section 8.
     */
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table): void {
            $table->id();

            // Author/admin who triggered the call. Null for system jobs
            // (RSS importer, scheduled translation, etc.).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Provider canonical name (openai | gemini | claude | openrouter | null).
            $table->string('provider', 50);

            // Model identifier (gpt-4o-mini, gemini-1.5-pro, claude-3-5-sonnet, etc.).
            $table->string('model', 100);

            // Feature classification (article_writer | seo_meta | rewrite | translate |
            // rss_rewrite | social_caption | faq | image_alt | ...).
            $table->string('feature_key', 80);

            // Optional reference to ai_prompt_templates.key + version for reproducibility.
            $table->string('prompt_template_key', 100)->nullable();
            $table->unsignedSmallInteger('prompt_template_version')->nullable();

            // Token counters.
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);

            // Cost in USD with 6 decimal places (1e-6 = $0.000001 precision; enough
            // for per-token billing on cheap models like gpt-4o-mini).
            $table->decimal('estimated_cost_usd', 12, 6)->default(0);

            // Round-trip latency in ms.
            $table->unsignedInteger('duration_ms')->nullable();

            $table->enum('status', ['success', 'failed', 'filtered', 'rate_limited', 'quota_exceeded'])
                ->default('success');

            $table->text('error_message')->nullable();

            // Redacted request metadata (model, temperature, feature_key, template version).
            // NEVER store the prompt body here — that's reproducible from the template.
            $table->json('request_metadata')->nullable();

            $table->timestamps();

            // Hot-path indexes for the AI cost dashboard.
            $table->index(['user_id', 'created_at']);
            $table->index(['provider', 'created_at']);
            $table->index(['feature_key', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
