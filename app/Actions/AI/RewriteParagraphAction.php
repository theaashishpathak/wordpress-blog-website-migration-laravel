<?php

declare(strict_types=1);

namespace App\Actions\AI;

use App\Services\AI\AIContentNormalizer;
use App\Services\AI\AIManager;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\PromptBuilder;
use App\Services\SettingService;

/**
 * Rewrite a single paragraph in a different tone while preserving meaning.
 *
 * Used by the in-editor "rewrite selection" feature. Returns the rewritten
 * text only (no quotes or commentary — see rewrite.paragraph template).
 */
class RewriteParagraphAction
{
    private const TEMPLATE_KEY = 'rewrite.paragraph';

    private const FEATURE_KEY = 'rewrite';

    public function __construct(
        private AIManager $ai,
        private PromptBuilder $prompts,
        private SettingService $settings,
        private AIContentNormalizer $normalizer,
    ) {}

    public function handle(
        string $paragraph,
        string $tone = 'professional',
        string $locale = 'en',
        ?int $userId = null,
        ?string $model = null,
        ?string $preferredProvider = null,
    ): string {
        $rendered = $this->prompts->build(self::TEMPLATE_KEY, $locale, [
            'paragraph' => $paragraph,
            'tone' => $tone,
        ]);

        $resolvedModel = $model
            ?? (string) $this->settings->get('ai.default_model', 'gpt-4o-mini');

        $response = $this->ai->complete(
            new CompletionRequest(
                model: $resolvedModel,
                systemPrompt: $rendered->systemPrompt,
                userPrompt: $rendered->userPrompt,
                temperature: 0.6,
                maxTokens: max(400, mb_strlen($paragraph) * 2),
                metadata: ['template_version' => $rendered->templateVersion],
                promptTemplateKey: $rendered->templateKey,
                featureKey: self::FEATURE_KEY,
                userId: $userId,
            ),
            preferredProvider: $preferredProvider,
        );

        return $this->normalizer->normalize($response->content);
    }
}
