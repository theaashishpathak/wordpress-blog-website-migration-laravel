<?php

declare(strict_types=1);

namespace App\Actions\AI;

use App\Services\AI\AIManager;
use App\Services\AI\AIResponseParser;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\FAQResult;
use App\Services\AI\PromptBuilder;
use App\Services\SettingService;

/**
 * Generate a FAQ block (question/answer pairs) from an article body.
 *
 * Uses the `faq_generator.default` template. The output is structured
 * JSON which is parsed via AIResponseParser → FAQResult DTO. Each FAQItem
 * also serialises to schema.org FAQPage JSON-LD for SEO embedding.
 */
class GenerateFAQAction
{
    private const TEMPLATE_KEY = 'faq_generator.default';

    private const FEATURE_KEY = 'faq';

    public function __construct(
        private AIManager $ai,
        private PromptBuilder $prompts,
        private SettingService $settings,
    ) {}

    public function handle(
        string $article,
        int $faqCount = 5,
        string $locale = 'en',
        ?int $userId = null,
        ?string $model = null,
        ?string $preferredProvider = null,
    ): FAQResult {
        $rendered = $this->prompts->build(self::TEMPLATE_KEY, $locale, [
            'article' => $article,
            'faq_count' => max(1, min(15, $faqCount)),
        ]);

        $resolvedModel = $model
            ?? (string) $this->settings->get('ai.default_model', 'gpt-4o-mini');

        $response = $this->ai->complete(
            new CompletionRequest(
                model: $resolvedModel,
                systemPrompt: $rendered->systemPrompt,
                userPrompt: $rendered->userPrompt,
                temperature: 0.4,
                maxTokens: 1500,
                metadata: ['template_version' => $rendered->templateVersion],
                promptTemplateKey: $rendered->templateKey,
                featureKey: self::FEATURE_KEY,
                userId: $userId,
            ),
            preferredProvider: $preferredProvider,
        );

        $decoded = AIResponseParser::extractJson(
            $response->content,
            providerName: $response->providerName,
        );

        return FAQResult::fromArray($decoded);
    }
}
