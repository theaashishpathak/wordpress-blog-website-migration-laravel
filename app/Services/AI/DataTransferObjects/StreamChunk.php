<?php

declare(strict_types=1);

namespace App\Services\AI\DataTransferObjects;

/**
 * One incremental piece of a streamed completion.
 *
 * Yielded by providers implementing SupportsStreaming. The final chunk
 * MUST have isFinal=true and (when known) usage populated, so
 * AIUsageTracker can record token counts.
 */
final readonly class StreamChunk
{
    public function __construct(
        public string $delta,
        public bool $isFinal = false,
        public ?TokenUsage $usage = null,
    ) {}

    public static function final(?TokenUsage $usage = null): self
    {
        return new self(delta: '', isFinal: true, usage: $usage);
    }
}
