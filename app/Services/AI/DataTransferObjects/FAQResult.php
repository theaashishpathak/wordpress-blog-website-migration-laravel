<?php

declare(strict_types=1);

namespace App\Services\AI\DataTransferObjects;

/**
 * Structured output of GenerateFAQAction.
 */
final readonly class FAQResult
{
    /**
     * @param  list<FAQItem>  $faqs
     */
    public function __construct(
        public array $faqs,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $rawFaqs = $data['faqs'] ?? [];

        if (! is_array($rawFaqs)) {
            return new self([]);
        }

        $items = [];

        foreach ($rawFaqs as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $item = FAQItem::fromArray($raw);

            if ($item->question !== '' && $item->answer !== '') {
                $items[] = $item;
            }
        }

        return new self($items);
    }

    public function count(): int
    {
        return count($this->faqs);
    }

    /**
     * Shape compatible with schema.org FAQPage JSON-LD output:
     *   { "@type": "FAQPage", "mainEntity": [ ...question objects... ] }
     *
     * @return array<int, array<string, mixed>>
     */
    public function toSchemaOrgEntities(): array
    {
        return array_map(static fn (FAQItem $item): array => [
            '@type' => 'Question',
            'name' => $item->question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item->answer,
            ],
        ], $this->faqs);
    }

    /**
     * @return list<array{question:string, answer:string}>
     */
    public function toArray(): array
    {
        return array_map(static fn (FAQItem $item): array => $item->toArray(), $this->faqs);
    }
}
