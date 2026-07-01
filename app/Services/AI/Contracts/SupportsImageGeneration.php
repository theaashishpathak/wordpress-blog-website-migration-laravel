<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\Exceptions\AIProviderException;

/**
 * Capability interface — providers that can generate images from text prompts
 * (e.g., OpenAI DALL-E, Gemini Imagen, Stability AI).
 *
 * Implementations return either a URL (preferred) or a path to a binary
 * stored on the configured disk. Caller is responsible for transferring
 * the image into Spatie MediaLibrary if persistence is required.
 */
interface SupportsImageGeneration
{
    /**
     * Generate an image and return its URL or storage path.
     *
     * @param  array<string, mixed>  $options  size, style, quality, etc.
     *
     * @throws AIProviderException
     */
    public function generateImage(string $prompt, array $options = []): string;
}
