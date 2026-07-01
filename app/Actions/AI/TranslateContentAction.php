<?php

declare(strict_types=1);

namespace App\Actions\AI;

use App\Services\AI\AIManager;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\PromptBuilder;
use App\Services\SettingService;

/**
 * Translate an article body from one language to another while preserving
 * tone, headings, and markdown structure.
 *
 * Uses the `translate.article` template. Temperature is intentionally
 * low (0.3) — translation should be literal, not creative.
 *
 * Used by:
 *   - Admin manual translation panel
 *   - TranslatePostJob queued translation
 *   - RSS importer when source language differs from site default
 */
class TranslateContentAction
{
    private const TEMPLATE_KEY = 'translate.article';

    private const FEATURE_KEY = 'translate';

    public function __construct(
        private AIManager $ai,
        private PromptBuilder $prompts,
        private SettingService $settings,
    ) {}

    public function handle(
        string $article,
        string $targetLanguage,
        string $sourceLocale = 'en',
        ?int $userId = null,
        ?string $model = null,
        ?string $preferredProvider = null,
    ): string {
        $rendered = $this->prompts->build(self::TEMPLATE_KEY, $sourceLocale, [
            'article' => $article,
            'target_language' => $targetLanguage,
        ]);

        $resolvedModel = $model
            ?? (string) $this->settings->get('ai.default_model', 'gpt-4o-mini');

        $response = $this->ai->complete(
            new CompletionRequest(
                model: $resolvedModel,
                systemPrompt: $rendered->systemPrompt,
                userPrompt: $rendered->userPrompt,
                temperature: 0.3,
                maxTokens: max(2000, mb_strlen($article) * 2),
                metadata: [
                    'template_version' => $rendered->templateVersion,
                    'source_locale' => $sourceLocale,
                    'target_language' => $targetLanguage,
                ],
                promptTemplateKey: $rendered->templateKey,
                featureKey: self::FEATURE_KEY,
                userId: $userId,
            ),
            preferredProvider: $preferredProvider,
        );

        return trim($response->content);
    }
}
