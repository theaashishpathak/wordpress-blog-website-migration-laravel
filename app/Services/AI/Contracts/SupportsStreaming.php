<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\Exceptions\AIProviderException;
use Generator;

/**
 * Capability interface — implemented by providers that support
 * server-sent-event style streamed completions (e.g., OpenAI Chat API,
 * Gemini streamGenerateContent).
 *
 * AIManager::stream() checks `instanceof SupportsStreaming` before
 * attempting to stream; non-streaming providers are emulated by a
 * single-chunk pseudo-stream.
 */
interface SupportsStreaming
{
    /**
     * Yield incremental StreamChunk DTOs.
     *
     * The final chunk must have `isFinal=true` and contain TokenUsage so
     * AIUsageTracker can record the call.
     *
     * @return Generator<int, \App\Services\AI\DataTransferObjects\StreamChunk>
     *
     * @throws AIProviderException
     */
    public function stream(CompletionRequest $request): Generator;
}
