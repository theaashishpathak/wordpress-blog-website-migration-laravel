<?php

declare(strict_types=1);

namespace App\Services\AI\DataTransferObjects;

/**
 * Embedding vectors for a batch of input texts.
 */
final readonly class EmbeddingResponse
{
    /**
     * @param  list<list<float>>  $vectors  one vector per input text, in original order
     */
    public function __construct(
        public array $vectors,
        public string $model,
        public string $providerName,
        public TokenUsage $usage,
    ) {}
}
