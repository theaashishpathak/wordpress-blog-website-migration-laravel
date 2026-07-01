<?php

declare(strict_types=1);

use App\Actions\Import\ImportFeedAction;
use App\Enums\PostStatus;
use App\Livewire\Admin\Imports\Sources as AdminSources;
use App\Models\ImportedItem;
use App\Models\ImportSource;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function sampleRssFeed(array $items = []): string
{
    $defaults = [
        [
            'guid' => 'https://example.com/article-1',
            'title' => 'First Article',
            'link' => 'https://example.com/article-1',
            'description' => 'A short summary',
            'content' => '<p>Long body of the article.</p>',
        ],
        [
            'guid' => 'https://example.com/article-2',
            'title' => 'Second Article',
            'link' => 'https://example.com/article-2',
            'description' => 'Another summary',
            'content' => '<p>Second body.</p>',
        ],
    ];

    $items = $items === [] ? $defaults : $items;

    $itemsXml = '';
    foreach ($items as $item) {
        $itemsXml .= "<item>\n";
        $itemsXml .= '  <guid>'.htmlspecialchars($item['guid'] ?? '')."</guid>\n";
        $itemsXml .= '  <title>'.htmlspecialchars($item['title'] ?? '')."</title>\n";
        $itemsXml .= '  <link>'.htmlspecialchars($item['link'] ?? '')."</link>\n";
        $itemsXml .= '  <description><![CDATA['.($item['description'] ?? '').']]></description>'."\n";
        if (! empty($item['content'])) {
            $itemsXml .= '  <content:encoded><![CDATA['.$item['content'].']]></content:encoded>'."\n";
        }
        $itemsXml .= "</item>\n";
    }

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <title>Sample Feed</title>
    <link>https://example.com</link>
    <description>Sample feed for tests</description>
    {$itemsXml}
  </channel>
</rss>
XML;
}

function adminUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', 'Admin')->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

// -------------------------------------------------------------------------
// ImportFeedAction
// -------------------------------------------------------------------------

test('ImportFeedAction parses RSS items and creates posts as drafts', function (): void {
    Http::fake([
        '*' => Http::response(sampleRssFeed(), 200, ['Content-Type' => 'application/rss+xml']),
    ]);

    $source = ImportSource::factory()->create(['feed_url' => 'https://example.com/feed.xml']);

    $result = app(ImportFeedAction::class)->handle($source);

    expect($result['fetched'])->toBe(2);
    expect($result['created'])->toBe(2);
    expect($result['skipped'])->toBe(0);

    expect(Post::query()->where('status', PostStatus::Draft->value)->count())->toBe(2);
    expect(ImportedItem::query()->where('source_id', $source->id)->count())->toBe(2);
});

test('ImportFeedAction respects auto_publish flag', function (): void {
    Http::fake([
        '*' => Http::response(sampleRssFeed(), 200),
    ]);

    $source = ImportSource::factory()->autoPublish()->create();

    app(ImportFeedAction::class)->handle($source);

    expect(Post::query()->where('status', PostStatus::Published->value)->count())->toBe(2);
});

test('ImportFeedAction dedupes by guid on subsequent fetches', function (): void {
    Http::fake([
        '*' => Http::response(sampleRssFeed(), 200),
    ]);

    $source = ImportSource::factory()->create();

    $first = app(ImportFeedAction::class)->handle($source);
    $second = app(ImportFeedAction::class)->handle($source);

    expect($first['created'])->toBe(2);
    expect($second['created'])->toBe(0);
    expect($second['skipped'])->toBe(2);
    expect(Post::query()->count())->toBe(2);
});

test('ImportFeedAction creates new posts when feed adds an item', function (): void {
    $source = ImportSource::factory()->create();

    // Http::fake() doesn't replace prior stubs — repeat calls just stack
    // more matches against the same URL pattern. Use Http::sequence() so
    // the first GET returns the 2-item feed and the second returns the
    // expanded 3-item feed.
    Http::fake([
        '*' => Http::sequence()
            ->push(sampleRssFeed(), 200)
            ->push(sampleRssFeed([
                ['guid' => 'https://example.com/article-1', 'title' => 'First Article', 'link' => 'https://example.com/article-1'],
                ['guid' => 'https://example.com/article-2', 'title' => 'Second Article', 'link' => 'https://example.com/article-2'],
                ['guid' => 'https://example.com/article-3', 'title' => 'Third Article', 'link' => 'https://example.com/article-3'],
            ]), 200),
    ]);

    // First fetch — 2 items, all new.
    app(ImportFeedAction::class)->handle($source);

    // Second fetch — 3 items, only the third is new.
    $result = app(ImportFeedAction::class)->handle($source);

    expect($result['created'])->toBe(1);
    expect($result['skipped'])->toBe(2);
    expect(Post::query()->count())->toBe(3);
});

test('ImportFeedAction marks source as error on HTTP failure', function (): void {
    Http::fake([
        '*' => Http::response('Service unavailable', 503),
    ]);

    $source = ImportSource::factory()->create();

    $result = app(ImportFeedAction::class)->handle($source);

    expect($result['created'])->toBe(0);
    expect($source->fresh()->status)->toBe(ImportSource::STATUS_ERROR);
    expect($source->fresh()->last_error)->toContain('HTTP 503');
});

test('ImportFeedAction marks source as error on invalid XML', function (): void {
    Http::fake([
        '*' => Http::response('not actually xml at all', 200),
    ]);

    $source = ImportSource::factory()->create();

    $result = app(ImportFeedAction::class)->handle($source);

    expect($result['created'])->toBe(0);
    expect($source->fresh()->status)->toBe(ImportSource::STATUS_ERROR);
});

test('ImportFeedAction stamps last_fetched_at + clears error on success', function (): void {
    Http::fake(['*' => Http::response(sampleRssFeed(), 200)]);

    $source = ImportSource::factory()->error('previous failure')->create();

    app(ImportFeedAction::class)->handle($source);

    expect($source->fresh()->status)->toBe(ImportSource::STATUS_ACTIVE);
    expect($source->fresh()->last_error)->toBeNull();
    expect($source->fresh()->last_fetched_at)->not->toBeNull();
});

test('ImportFeedAction generates a synthetic guid when feed lacks one', function (): void {
    $feedWithoutGuid = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Sample</title>
    <link>https://example.com</link>
    <description>Sample</description>
    <item>
      <title>Guid-less Article</title>
      <link>https://example.com/no-guid</link>
      <description>desc</description>
    </item>
  </channel>
</rss>
XML;

    Http::fake(['*' => Http::response($feedWithoutGuid, 200)]);

    $source = ImportSource::factory()->create();

    app(ImportFeedAction::class)->handle($source);

    $item = ImportedItem::query()->where('source_id', $source->id)->first();
    expect($item)->not->toBeNull();
    expect($item->guid)->toStartWith('sha1:');
});

test('Imported post stores source_name + source_url for attribution', function (): void {
    Http::fake(['*' => Http::response(sampleRssFeed(), 200)]);

    $source = ImportSource::factory()->create(['name' => 'Example News']);

    app(ImportFeedAction::class)->handle($source);

    $post = Post::query()->first();
    expect($post->source_name)->toBe('Example News');
    expect($post->source_url)->toBe('https://example.com/article-1');
});

// -------------------------------------------------------------------------
// dueForFetch scope
// -------------------------------------------------------------------------

test('dueForFetch returns active sources never fetched', function (): void {
    $fresh = ImportSource::factory()->create(['last_fetched_at' => null]);
    $stale = ImportSource::factory()->create([
        'last_fetched_at' => now()->subHours(2),
        'fetch_interval_minutes' => 60,
    ]);
    $recent = ImportSource::factory()->fetchedRecently()->create(['fetch_interval_minutes' => 60]);
    $paused = ImportSource::factory()->paused()->create(['last_fetched_at' => null]);

    $due = ImportSource::query()->dueForFetch()->pluck('id')->all();

    expect($due)->toContain($fresh->id);
    expect($due)->toContain($stale->id);
    expect($due)->not->toContain($recent->id);
    expect($due)->not->toContain($paused->id);
});

// -------------------------------------------------------------------------
// Scheduled command
// -------------------------------------------------------------------------

test('rss:import command runs against every due-for-fetch source', function (): void {
    Http::fake(['*' => Http::response(sampleRssFeed(), 200)]);

    $a = ImportSource::factory()->create(['feed_url' => 'https://a.test/feed.xml']);
    $b = ImportSource::factory()->create(['feed_url' => 'https://b.test/feed.xml']);

    $this->artisan('rss:import')->assertExitCode(0);

    expect(Post::query()->count())->toBe(4);   // 2 sources × 2 items
});

test('rss:import --source picks a single source even when not due', function (): void {
    Http::fake(['*' => Http::response(sampleRssFeed(), 200)]);

    $recentlyFetched = ImportSource::factory()->fetchedRecently()->create([
        'feed_url' => 'https://example.com/feed.xml',
    ]);

    $this->artisan('rss:import', ['--source' => $recentlyFetched->id])->assertExitCode(0);

    expect(Post::query()->count())->toBe(2);
});

// -------------------------------------------------------------------------
// Admin Livewire
// -------------------------------------------------------------------------

test('users without rss.view are denied admin access', function (): void {
    $u = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($u)->test(AdminSources::class)->assertForbidden();
});

test('admin can create a new RSS source via the modal form', function (): void {
    $admin = adminUser();

    Livewire::actingAs($admin)
        ->test(AdminSources::class)
        ->call('newSource')
        ->set('name', 'TechCrunch')
        ->set('feedUrl', 'https://techcrunch.com/feed/')
        ->set('defaultLanguageId', $this->english->id)
        ->call('save')
        ->assertSet('showForm', false);

    expect(ImportSource::query()->where('name', 'TechCrunch')->exists())->toBeTrue();
});

test('admin fetchNow button runs the importer synchronously', function (): void {
    Http::fake(['*' => Http::response(sampleRssFeed(), 200)]);

    $admin = adminUser();
    $source = ImportSource::factory()->create();

    Livewire::actingAs($admin)
        ->test(AdminSources::class)
        ->call('fetchNow', $source->id);

    expect(Post::query()->count())->toBe(2);
    expect($source->fresh()->last_fetched_at)->not->toBeNull();
});

test('admin togglePause flips source status', function (): void {
    $admin = adminUser();
    $source = ImportSource::factory()->create(['status' => ImportSource::STATUS_ACTIVE]);

    Livewire::actingAs($admin)
        ->test(AdminSources::class)
        ->call('togglePause', $source->id);

    expect($source->fresh()->status)->toBe(ImportSource::STATUS_PAUSED);
});
