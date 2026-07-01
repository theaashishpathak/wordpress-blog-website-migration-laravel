<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AIPromptTemplate;
use App\Services\AI\DataTransferObjects\RenderedPrompt;
use App\Support\LocaleResolver;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Resolve a versioned prompt template and interpolate its variables into
 * a ready-to-send RenderedPrompt DTO.
 *
 * Resolution order for (key, locale):
 *   1. Active template for the exact locale
 *   2. Active template for the configured default language
 *   3. Throw — no usable template
 *
 * The template lookup is memoised per request lifecycle so repeated calls
 * (e.g., bulk AI generation) don't slam the DB.
 *
 * Spec: docs/AI Provider Contract.txt Section 9.
 */
class PromptBuilder
{
    /**
     * Per-request cache keyed by "{$key}|{$locale}".
     *
     * @var array<string, AIPromptTemplate|null>
     */
    private array $cache = [];

    public function __construct(private LocaleResolver $localeResolver) {}

    /**
     * @param  array<string, mixed>  $variables
     */
    public function build(string $key, string $locale, array $variables): RenderedPrompt
    {
        $template = $this->resolveTemplate($key, $locale);

        if ($template === null) {
            throw new RuntimeException(
                "No active prompt template found for key=[{$key}], locale=[{$locale}], "
                ."and no usable fallback. Run AIPromptTemplateSeeder."
            );
        }

        $this->assertAllVariablesProvided($template, $variables);

        $resolvedLocale = (string) $template->locale;

        return new RenderedPrompt(
            systemPrompt: $this->interpolate((string) $template->system_prompt, $variables),
            userPrompt: $this->interpolate((string) $template->user_prompt_template, $variables),
            templateKey: (string) $template->key,
            templateVersion: (int) $template->version,
            locale: $resolvedLocale,
        );
    }

    /**
     * Find the most appropriate active template for (key, locale).
     *
     * Used by build() but also useful on its own for admin preview
     * screens that want to inspect the template before generation.
     */
    public function resolveTemplate(string $key, string $locale): ?AIPromptTemplate
    {
        $cacheKey = $key.'|'.$locale;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        // 1. Exact locale match.
        $template = AIPromptTemplate::active($key, $locale);

        if ($template !== null) {
            return $this->cache[$cacheKey] = $template;
        }

        // 2. Fall back to default language.
        $defaultLocale = $this->localeResolver->default()?->code;

        if ($defaultLocale !== null && $defaultLocale !== $locale) {
            $fallback = AIPromptTemplate::active($key, $defaultLocale);

            if ($fallback !== null) {
                return $this->cache[$cacheKey] = $fallback;
            }
        }

        return $this->cache[$cacheKey] = null;
    }

    /**
     * Flush the per-request memoisation cache. Useful in tests when the
     * underlying template rows change mid-test.
     */
    public function flushCache(): void
    {
        $this->cache = [];
    }

    /**
     * Replace every `{{variable}}` token with the caller-supplied value.
     *
     * @param  array<string, mixed>  $variables
     */
    private function interpolate(string $text, array $variables): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/',
            function (array $match) use ($variables): string {
                $name = (string) $match[1];

                if (! array_key_exists($name, $variables)) {
                    return $match[0];   // leave untouched if no value supplied
                }

                $value = $variables[$name];

                if (is_array($value) || is_object($value)) {
                    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
                }

                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }

                return $value === null ? '' : (string) $value;
            },
            $text,
        );
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function assertAllVariablesProvided(AIPromptTemplate $template, array $variables): void
    {
        $missing = array_values(array_diff(
            $template->requiredVariables(),
            array_keys($variables),
        ));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'variables' => sprintf(
                    'Prompt template [%s] requires variables [%s] but none provided for [%s].',
                    $template->key,
                    implode(', ', $template->requiredVariables()),
                    implode(', ', $missing),
                ),
            ]);
        }
    }
}
