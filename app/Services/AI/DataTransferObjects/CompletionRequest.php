<?php

declare(strict_types=1);

namespace App\Services\AI\DataTransferObjects;

/**
 * Immutable AI completion request.
 *
 * All provider calls go through this DTO so AIManager can attach
 * usage-tracking metadata, route to the correct provider, and serialize
 * the request for logging without leaking PII.
 */
final readonly class CompletionRequest
{
    /**
     * @param  list<string>|null  $stopSequences
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $model,
        public string $systemPrompt,
        public string $userPrompt,
        public float $temperature = 0.7,
        public int $maxTokens = 2000,
        public ?float $topP = null,
        public ?array $stopSequences = null,
        public array $metadata = [],
        public ?string $promptTemplateKey = null,
        public ?string $featureKey = null,
        public ?int $userId = null,
    ) {}
}
