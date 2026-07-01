<?php

declare(strict_types=1);

namespace App\Services\AI\DataTransferObjects;

/**
 * Result of PromptBuilder rendering a versioned template + variables.
 *
 * Stored alongside the AI generation in ai_usage_logs.request_metadata
 * (template key + version) so any output can be reproduced from the
 * canonical prompt that produced it.
 */
final readonly class RenderedPrompt
{
    public function __construct(
        public string $systemPrompt,
        public string $userPrompt,
        public string $templateKey,
        public int $templateVersion,
        public string $locale,
    ) {}
}
