<?php

declare(strict_types=1);

namespace App\Actions\AI;

use App\Services\AI\AIContentNormalizer;
use App\Services\AI\AIManager;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\PromptBuilder;
use App\Services\SettingService;

/**
 * Generate a long-form article body via the AI layer.
 *
 * Uses the `article_writer.long_form` prompt template. The output is raw
 * markdown content (no title prepended — that's a separate generation).
 * Caller is responsible for piping the result into a Post translation row.
 *
 * Variables in the template:
 *   word_count, tone, topic, audience, focus_keyword
 */
class GenerateArticleAction
{
    private const TEMPLATE_KEY = 'article_writer.long_form';

    private const FEATURE_KEY = 'article_writer';

    public function __construct(
        private AIManager $ai,
        private PromptBuilder $prompts,
        private SettingService $settings,
        private AIContentNormalizer $normalizer,
    ) {}

    public function handle(
        string $topic,
        string $locale = 'en',
        string $tone = 'professional',
        int $wordCount = 800,
        string $audience = 'general readers',
        string $focusKeyword = '',
        ?int $userId = null,
        ?string $model = null,
        ?string $preferredProvider = null,
    ): string {
        $rendered = $this->prompts->build(self::TEMPLATE_KEY, $locale, [
            'topic' => $topic,
            'tone' => $tone,
            'word_count' => $wordCount,
            'audience' => $audience,
            'focus_keyword' => $focusKeyword,
        ]);

        $resolvedModel = $model
            ?? (string) $this->settings->get('ai.default_model', 'gpt-4o-mini');

        $temperature = (float) $this->settings->get('ai.default_temperature', 0.7);
        $maxTokens = max((int) ceil($wordCount * 2.5), 1500);

        $response = $this->ai->complete(
            new CompletionRequest(
                model: $resolvedModel,
                systemPrompt: $rendered->systemPrompt,
                userPrompt: $rendered->userPrompt,
                temperature: $temperature,
                maxTokens: $maxTokens,
                metadata: ['template_version' => $rendered->templateVersion],
                promptTemplateKey: $rendered->templateKey,
                featureKey: self::FEATURE_KEY,
                userId: $userId,
            ),
            preferredProvider: $preferredProvider,
        );

        // Normalize markdown → TipTap-ready HTML and run through the
        // sanitizer so what we store matches what the editor expects.
        return $this->normalizer->normalize($response->content);
    }
}
