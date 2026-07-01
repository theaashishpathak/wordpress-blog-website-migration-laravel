<?php

declare(strict_types=1);

namespace App\Services\AI\DataTransferObjects;

/**
 * Provider-agnostic response for a synchronous completion call.
 */
final readonly class CompletionResponse
{
    /**
     * @param  array<string, mixed>  $raw  provider-specific raw payload (for debugging only)
     */
    public function __construct(
        public string $content,
        public TokenUsage $usage,
        public string $providerName,
        public string $model,
        public ?string $finishReason = null,
        public array $raw = [],
    ) {}
}
