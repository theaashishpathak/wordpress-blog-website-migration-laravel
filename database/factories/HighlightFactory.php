<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Highlight;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Highlight>
 */
class HighlightFactory extends Factory
{
    protected $model = Highlight::class;

    /**
     * Believable pull-quotes a reader might actually highlight — long
     * enough to feel like a passage, short enough to render cleanly
     * inside the highlights card.
     *
     * @var list<string>
     */
    private const SNIPPETS = [
        'The new flagship model adds real-time vision, longer context windows, and tool-use that finally rivals human reasoning.',
        'A solid twelve-page report on what this means for working journalists in newsrooms still finding their footing.',
        'Bangladesh — a country of 170 million — leapfrogged direct to digital, skipping the legacy systems entirely.',
        'AI-assisted reporting still demands editorial judgement at every step. The tools are leverage, not replacement.',
        'Climate policy moves at the speed of public attention, and attention is finite.',
        'These regulatory shifts will compound through 2026 and beyond — the second-order effects matter more than the headlines.',
        'The most interesting line in the filing is buried halfway through page nineteen.',
        'In a market this concentrated, the marginal cost of distribution is doing almost all the strategic work.',
        'You can\'t legislate trust into existence, but you can build the infrastructure that earns it back over time.',
        'The pattern repeats across every emerging economy in the dataset — the only variable that really changed was the year.',
        'For the first time in a generation, the curve is bending in the right direction.',
        'There\'s no clean separation between technology policy and industrial policy any more.',
    ];

    /**
     * Optional notes the reader might leave on a highlight. Half the
     * time we leave the note blank — keeps the empty-vs-noted UI mix
     * believable.
     *
     * @var list<string>
     */
    private const NOTES = [
        'Worth revisiting later.',
        'Quote for the weekly digest.',
        'Counter-evidence to last month\'s piece.',
        'Strong opening line — file this away.',
        'Send to the design team.',
    ];

    public function definition(): array
    {
        $text = self::SNIPPETS[array_rand(self::SNIPPETS)];

        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'language_id' => null,
            'selected_text' => $text,
            'note' => fake()->boolean(45) ? self::NOTES[array_rand(self::NOTES)] : null,
            'start_offset' => fake()->numberBetween(0, 500),
            'end_offset' => fake()->numberBetween(500, 1200),
            'context_hash' => sha1($text),
        ];
    }

    /** Force a noted highlight regardless of the random roll. */
    public function withNote(?string $note = null): static
    {
        return $this->state(fn (array $a): array => [
            'note' => $note ?? self::NOTES[array_rand(self::NOTES)],
        ]);
    }
}
