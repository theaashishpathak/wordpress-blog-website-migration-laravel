<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * A bank of plausible comment bodies — short, varied in tone, and
     * free of obvious "lorem ipsum" filler. Used by the default
     * definition + the `approved/pending/spam` states.
     *
     * @var list<string>
     */
    private const BODY_POOL = [
        'Really thoughtful piece — the analysis on the second half landed for me.',
        'Pushed back on the framing here but the data is genuinely solid. Saved to come back to.',
        'Excellent reporting. Best thing I\'ve read on this all week.',
        'Has anyone else noticed how this contradicts last month\'s coverage? Curious if anyone has reconciled the two.',
        'Bookmarked. Too much to absorb in one read — saving for the weekend.',
        'I appreciate that you actually named sources here. So rare.',
        'The chart in section three is doing a lot of heavy lifting. Would love to see the raw numbers.',
        'Disagree with the conclusion but the path to get there was fair.',
        'This is exactly the angle I was looking for. Thank you.',
        'Shared with my team — sparked a good thread internally already.',
        'Small typo in the third paragraph — otherwise great work.',
        'How does this hold up if we factor in the policy shift announced yesterday?',
        'Worth pairing this with the Bloomberg piece from earlier this week.',
        'Genuine question: are there any counter-examples that didn\'t make it into the piece?',
        'Best part is the closing paragraph. That reframe is going to stick with me.',
    ];

    public function definition(): array
    {
        $isGuest = fake()->boolean(50);

        return [
            'post_id' => Post::factory(),
            'parent_id' => null,
            'user_id' => $isGuest ? null : User::factory(),
            'guest_name' => $isGuest ? fake()->name() : null,
            'guest_email' => $isGuest ? fake()->safeEmail() : null,
            'guest_website' => $isGuest ? fake()->optional()->url() : null,
            'body' => self::BODY_POOL[array_rand(self::BODY_POOL)],
            'status' => Comment::STATUS_PENDING,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => Comment::STATUS_APPROVED,
            'approved_at' => now()->subMinutes(fake()->numberBetween(5, 60 * 24 * 30)),
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => Comment::STATUS_SPAM,
            // Spammy bodies — short and link-baity. Demo-only fixtures.
            'body' => fake()->randomElement([
                'Check out my new course on making $$$ online!!! Link in bio.',
                'Cheap watches, free shipping. Click here.',
                'Earn from home with this one weird trick.',
            ]),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => Comment::STATUS_PENDING,
        ]);
    }

    public function fromGuest(?string $email = null): static
    {
        return $this->state(fn (array $a): array => [
            'user_id' => null,
            'guest_name' => fake()->name(),
            'guest_email' => $email ?? fake()->safeEmail(),
        ]);
    }

    public function fromUser(int $userId): static
    {
        return $this->state(fn (array $a): array => [
            'user_id' => $userId,
            'guest_name' => null,
            'guest_email' => null,
        ]);
    }

    public function replyTo(int $parentId): static
    {
        return $this->state(fn (array $a): array => [
            'parent_id' => $parentId,
        ]);
    }
}
