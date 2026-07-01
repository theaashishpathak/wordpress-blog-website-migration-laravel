<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * A bank of editorial-feeling fragments. The `configure()` callback
     * stitches them into believable headlines + excerpts so factory-
     * generated posts don't read like Lorem Ipsum on the homepage.
     *
     * @var list<array{topic: string, verb: string, noun: string, excerpt: string}>
     */
    private const HEADLINE_FRAGMENTS = [
        ['topic' => 'AI startups',        'verb' => 'raise',     'noun' => 'a record Series C round',         'excerpt' => 'Investors poured fresh capital into model-builders despite a cooling broader tech market.'],
        ['topic' => 'EU regulators',      'verb' => 'unveil',    'noun' => 'sweeping new platform rules',    'excerpt' => 'The draft directive aims to harmonise content moderation, AI liability, and data portability across the bloc.'],
        ['topic' => 'Climate scientists', 'verb' => 'warn',      'noun' => 'of accelerating ocean warming',  'excerpt' => 'New satellite data shows surface temperatures rising at roughly twice the rate projected five years ago.'],
        ['topic' => 'Apple',              'verb' => 'previews',  'noun' => 'an on-device language model',    'excerpt' => 'The Cupertino giant says the model runs entirely on-chip, with privacy as the headline pitch.'],
        ['topic' => 'Bangladesh',         'verb' => 'opens',     'noun' => 'its first deep-water port',      'excerpt' => 'A decade in the making, Matarbari is set to halve container transit times for South Asian trade.'],
        ['topic' => 'Researchers',        'verb' => 'demonstrate', 'noun' => 'practical fusion ignition',    'excerpt' => 'A successful follow-up burn at Livermore puts net-positive fusion energy one step closer to scale.'],
        ['topic' => 'Spotify',            'verb' => 'launches',  'noun' => 'an AI music recommender',        'excerpt' => 'The redesigned home feed leans on real-time signals to surface tracks within seconds of release.'],
        ['topic' => 'The IMF',            'verb' => 'revises',   'noun' => 'global growth forecasts upward', 'excerpt' => 'Stronger consumer demand in emerging markets offset a softer outlook for the eurozone.'],
        ['topic' => 'Anthropic',          'verb' => 'releases',  'noun' => 'a smaller, faster model',        'excerpt' => 'Claude Haiku 4.5 is positioned as the cost-effective option for high-volume agentic workloads.'],
        ['topic' => 'India',              'verb' => 'inaugurates','noun' => 'a national semiconductor fab', 'excerpt' => 'The flagship facility marks the latest milestone in the country\'s push for chip self-reliance.'],
        ['topic' => 'NASA',               'verb' => 'confirms',  'noun' => 'water signatures on Europa',     'excerpt' => 'Clipper mission flyby data turns up the strongest evidence yet of a subsurface ocean.'],
        ['topic' => 'A federal court',    'verb' => 'rules on',  'noun' => 'AI training fair use',           'excerpt' => 'The decision narrows the window for training on copyrighted material without licensing.'],
        ['topic' => 'Tokyo',              'verb' => 'will host', 'noun' => 'a new tech expo',                'excerpt' => 'Organisers expect 80,000 attendees across robotics, mobility, and quantum-computing tracks.'],
        ['topic' => 'Tesla',              'verb' => 'recalls',   'noun' => 'a million vehicles',             'excerpt' => 'A driver-assist software update closes the gap on a long-standing NHTSA investigation.'],
        ['topic' => 'OpenAI',             'verb' => 'expands',   'noun' => 'its developer platform',         'excerpt' => 'New SDKs target agent orchestration and long-running background tasks.'],
        ['topic' => 'Researchers',        'verb' => 'find',      'noun' => 'a faster route to greener steel','excerpt' => 'Hydrogen-based direct reduction trials in Sweden show promising decarbonisation economics.'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $defaultLang = Language::query()->default()->first()
            ?? Language::factory()->english()->default()->create();

        return [
            'type' => PostType::Post,
            'category_id' => null,
            'subcategory_id' => null,
            'author_id' => User::factory(),
            'default_language_id' => $defaultLang->id,
            'status' => PostStatus::Draft,
            'visibility' => Post::VISIBILITY_PUBLIC,
            'is_featured' => false,
            'is_breaking' => false,
            'is_trending' => false,
            'is_editors_pick' => false,
            'is_sponsored' => false,
            'is_premium' => false,
            'allow_comments' => true,
            'published_at' => null,
            'scheduled_at' => null,
            'breaking_expires_at' => null,
            'view_count' => 0,
            'like_count' => 0,
            'share_count' => 0,
            'comment_count' => 0,
            'featured_image_id' => null,
            'source_name' => null,
            'source_url' => null,
            'rss_source_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Post $post): void {
            if ($post->translations()->exists()) {
                return;
            }

            $fragment = self::HEADLINE_FRAGMENTS[array_rand(self::HEADLINE_FRAGMENTS)];
            $title = sprintf('%s %s %s', $fragment['topic'], $fragment['verb'], $fragment['noun']);
            $title = Str::ucfirst($title);

            $post->translations()->create([
                'language_id' => $post->default_language_id,
                'title' => $title,
                // Append a numeric suffix so the unique slug constraint
                // can't bite us when two factory posts roll the same
                // fragment by chance.
                'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
                'excerpt' => $fragment['excerpt'],
                'content' => self::generateBody($title, $fragment['excerpt']),
                'reading_time' => fake()->numberBetween(3, 12).' min read',
                'is_published' => $post->status === PostStatus::Published,
                'translation_status' => $post->status === PostStatus::Published
                    ? 'published'
                    : 'manual',
            ]);
        });
    }

    /**
     * Build a believable article body. Mix of dek paragraph, fillers,
     * a key-takeaways list, and a pull quote so previews look real.
     */
    private static function generateBody(string $title, string $excerpt): string
    {
        return "<h2>{$title}</h2>\n".
            "<p><em>{$excerpt}</em></p>\n".
            '<p>'.fake()->paragraph(6).'</p>'."\n".
            '<h3>What we know so far</h3>'."\n".
            '<ul>'."\n".
            '  <li>'.fake()->sentence(12).'</li>'."\n".
            '  <li>'.fake()->sentence(12).'</li>'."\n".
            '  <li>'.fake()->sentence(12).'</li>'."\n".
            '</ul>'."\n".
            '<p>'.fake()->paragraph(8).'</p>'."\n".
            '<blockquote>'.fake()->sentence(16).'</blockquote>'."\n".
            '<p>'.fake()->paragraph(5).'</p>';
    }

    public function ofType(PostType|string $type): static
    {
        return $this->state(fn (array $a): array => [
            'type' => $type instanceof PostType ? $type : PostType::from($type),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => PostStatus::Published,
            // Distribute publish times across the last 90 days, weighted
            // toward recency so the homepage feed has fresh items but
            // archives still look populated.
            'published_at' => now()->subMinutes(fake()->numberBetween(1, 60 * 24 * 90)),
        ]);
    }

    public function publishedRecently(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => PostStatus::Published,
            'published_at' => now()->subMinutes(fake()->numberBetween(1, 60 * 24 * 3)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => PostStatus::Draft,
        ]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => PostStatus::PendingReview,
        ]);
    }

    public function scheduled(?\DateTimeInterface $at = null): static
    {
        return $this->state(fn (array $a): array => [
            'status' => PostStatus::Scheduled,
            'scheduled_at' => $at ?? now()->addHours(fake()->numberBetween(1, 48)),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => PostStatus::Archived,
            'published_at' => now()->subMonths(fake()->numberBetween(3, 18)),
        ]);
    }

    public function breaking(int $hours = 6): static
    {
        return $this->published()->state(fn (array $a): array => [
            'is_breaking' => true,
            'breaking_expires_at' => now()->addHours($hours),
            // Breaking news posts deserve a hot view count.
            'view_count' => fake()->numberBetween(5_000, 80_000),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $a): array => [
            'is_featured' => true,
        ]);
    }

    public function trending(): static
    {
        return $this->state(fn (array $a): array => [
            'is_trending' => true,
            'view_count' => fake()->numberBetween(1000, 50000),
        ]);
    }

    public function editorsPick(): static
    {
        return $this->state(fn (array $a): array => [
            'is_editors_pick' => true,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $a): array => [
            'is_premium' => true,
            'visibility' => Post::VISIBILITY_PREMIUM,
        ]);
    }

    public function sponsored(): static
    {
        return $this->state(fn (array $a): array => [
            'is_sponsored' => true,
        ]);
    }

    public function withCategory(?int $categoryId = null): static
    {
        return $this->state(fn (array $a): array => [
            'category_id' => $categoryId ?? Category::factory(),
        ]);
    }

    public function withAuthor(?int $authorId = null): static
    {
        return $this->state(fn (array $a): array => [
            'author_id' => $authorId ?? User::factory(),
        ]);
    }

    public function withoutTranslations(): static
    {
        return $this->afterCreating(function (Post $post): void {
            $post->translations()->delete();
        });
    }
}
