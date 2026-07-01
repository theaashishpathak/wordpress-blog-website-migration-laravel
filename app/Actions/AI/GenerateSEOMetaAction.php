<?php

declare(strict_types=1);

namespace App\Actions\AI;

use App\Services\AI\AIManager;
use App\Services\AI\AIResponseParser;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\SEOMetaResult;
use App\Services\AI\PromptBuilder;
use App\Services\SettingService;

/**
 * Generate SEO metadata (meta_title, meta_description, tags, slug) from
 * a draft article's title + excerpt.
 *
 * Uses the `seo_meta.default` template, which instructs the model to
 * return strict JSON. The response is parsed via AIResponseParser into
 * a SEOMetaResult DTO; defensive length truncation happens in the DTO
 * factory so downstream code can trust the values.
 */
class GenerateSEOMetaAction
{
    private const TEMPLATE_KEY = 'seo_meta.default';

    private const FEATURE_KEY = 'seo_meta';

    public function __construct(
        private AIManager $ai,
        private PromptBuilder $prompts,
        private SettingService $settings,
    ) {}

    public function handle(
        string $title,
        string $excerpt,
        string $focusKeyword,
        string $locale = 'en',
        ?int $userId = null,
        ?string $model = null,
        ?string $preferredProvider = null,
    ): SEOMetaResult {
        $rendered = $this->prompts->build(self::TEMPLATE_KEY, $locale, [
            'title' => $title,
            'excerpt' => $excerpt,
            'focus_keyword' => $focusKeyword,
        ]);

        $resolvedModel = $model
            ?? (string) $this->settings->get('ai.default_model', 'gpt-4o-mini');

        $response = $this->ai->complete(
            new CompletionRequest(
                model: $resolvedModel,
                systemPrompt: $rendered->systemPrompt,
                userPrompt: $rendered->userPrompt,
                temperature: 0.3,   // structured output → low temperature
                maxTokens: 600,
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

        return SEOMetaResult::fromArray($decoded);
    }
}
