<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\DataTransferObjects\ModerationResult;
use App\Services\AI\Exceptions\AIProviderException;

/**
 * Capability interface — providers that can check content against their
 * safety policy (hate, self-harm, sexual, violence, PII).
 *
 * AIManager optionally routes generated content through moderation when
 * `setting('ai.moderation_enabled')` is true.
 */
interface SupportsModeration
{
    /**
     * @throws AIProviderException
     */
    public function moderate(string $text): ModerationResult;
}
