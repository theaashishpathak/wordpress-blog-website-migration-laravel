<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\DataTransferObjects\EmbeddingResponse;
use App\Services\AI\Exceptions\AIProviderException;

/**
 * Capability interface — providers that can return vector embeddings for
 * semantic search, internal-link suggestion, and related-post discovery.
 */
interface SupportsEmbedding
{
    /**
     * Generate embeddings for one or more text inputs.
     *
     * @param  list<string>  $texts
     *
     * @throws AIProviderException
     */
    public function embed(array $texts, ?string $model = null): EmbeddingResponse;
}
