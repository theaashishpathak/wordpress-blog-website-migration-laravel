<?php

declare(strict_types=1);

use App\Livewire\Visitor\Bookmarks\Index as BookmarksIndex;
use App\Livewire\Visitor\Highlights\Index as HighlightsIndex;
use App\Livewire\Visitor\ReadingHistory\Index as ReadingHistoryIndex;
use App\Livewire\Visitor\ReadingList\Index as ReadingListIndex;
use App\Models\Bookmark;
use App\Models\Highlight;
use App\Models\Post;
use App\Models\ReadingHistory;
use App\Models\ReadingListItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Phase V2 — Livewire index pages render + interactive behaviour
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->visitor = User::factory()->visitor()->create();
    $this->post = Post::factory()->create();
});

// ── Bookmarks ───────────────────────────────────────────────────────────

test('bookmarks index lists current users saved posts only', function () {
    $otherUser = User::factory()->visitor()->create();
    Bookmark::factory()->count(3)->create(['user_id' => $this->visitor->id]);
    Bookmark::factory()->count(2)->create(['user_id' => $otherUser->id]);

    Livewire::actingAs($this->visitor)
        ->test(BookmarksIndex::class)
        ->assertOk();

    expect($this->visitor->bookmarks()->count())->toBe(3);
});

test('bookmarks unbookmark removes the row', function () {
    Bookmark::factory()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $this->post->id,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(BookmarksIndex::class)
        ->call('unbookmark', $this->post->id);

    expect($this->visitor->bookmarks()->count())->toBe(0);
});

// ── Reading List ────────────────────────────────────────────────────────

test('reading list index shows active items by default', function () {
    ReadingListItem::factory()->count(2)->create(['user_id' => $this->visitor->id]);
    ReadingListItem::factory()->dismissed()->count(3)->create(['user_id' => $this->visitor->id]);

    Livewire::actingAs($this->visitor)
        ->test(ReadingListIndex::class)
        ->assertSet('filter', 'active');
});

test('reading list dismiss soft-removes the item', function () {
    ReadingListItem::factory()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $this->post->id,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(ReadingListIndex::class)
        ->call('dismiss', $this->post->id);

    expect($this->visitor->readingListItems()->active()->count())->toBe(0)
        ->and($this->visitor->readingListItems()->dismissed()->count())->toBe(1);
});

// ── Reading History ─────────────────────────────────────────────────────

test('reading history clear deletes the entry', function () {
    ReadingHistory::factory()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $this->post->id,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(ReadingHistoryIndex::class)
        ->call('clear', $this->post->id);

    expect($this->visitor->readingHistory()->count())->toBe(0);
});

// ── Highlights ──────────────────────────────────────────────────────────

test('highlights edit-note flow saves only the note field', function () {
    $highlight = Highlight::factory()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $this->post->id,
        'selected_text' => 'Selected passage',
        'note' => null,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(HighlightsIndex::class)
        ->call('startEditingNote', $highlight->id)
        ->assertSet('editingId', $highlight->id)
        ->set('editingNote', 'Reminder: come back to this')
        ->call('saveNote')
        ->assertSet('editingId', null);

    expect($highlight->fresh()->note)->toBe('Reminder: come back to this')
        ->and($highlight->fresh()->selected_text)->toBe('Selected passage');
});

test('highlights delete removes the highlight', function () {
    $highlight = Highlight::factory()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $this->post->id,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(HighlightsIndex::class)
        ->call('delete', $highlight->id);

    expect(Highlight::query()->find($highlight->id))->toBeNull();
});

test('highlights cannot edit or delete another users row', function () {
    $other = User::factory()->visitor()->create();
    $foreignHighlight = Highlight::factory()->create([
        'user_id' => $other->id,
        'post_id' => $this->post->id,
    ]);

    expect(fn () => Livewire::actingAs($this->visitor)
        ->test(HighlightsIndex::class)
        ->call('delete', $foreignHighlight->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Highlight::query()->find($foreignHighlight->id))->not->toBeNull();
});
