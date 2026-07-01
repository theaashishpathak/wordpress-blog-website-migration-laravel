<?php

declare(strict_types=1);

use App\Actions\Visitor\Bookmark\ToggleBookmarkAction;
use App\Actions\Visitor\Highlight\CreateHighlightAction;
use App\Actions\Visitor\Highlight\DeleteHighlightAction;
use App\Actions\Visitor\Highlight\UpdateHighlightNoteAction;
use App\Actions\Visitor\ReadingHistory\RecordReadAction;
use App\Actions\Visitor\ReadingList\ToggleReadingListAction;
use App\Models\Highlight;
use App\Models\Post;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Phase V2 — Action layer tests for My Library features
|--------------------------------------------------------------------------
| Each Action is exercised directly (not via Livewire) so the business
| logic stays decoupled from the UI. Livewire integration is covered in
| separate browser/feature tests.
*/

beforeEach(function () {
    $this->visitor = User::factory()->visitor()->create();
    $this->post = Post::factory()->create();
});

// ── Bookmarks ───────────────────────────────────────────────────────────

test('ToggleBookmarkAction creates a bookmark on first call', function () {
    $action = app(ToggleBookmarkAction::class);

    expect($action->handle($this->visitor, $this->post))->toBeTrue()
        ->and($this->visitor->bookmarks()->count())->toBe(1);
});

test('ToggleBookmarkAction removes a bookmark on second call', function () {
    $action = app(ToggleBookmarkAction::class);

    $action->handle($this->visitor, $this->post);
    expect($action->handle($this->visitor, $this->post))->toBeFalse()
        ->and($this->visitor->bookmarks()->count())->toBe(0);
});

// ── Reading List ────────────────────────────────────────────────────────

test('ToggleReadingListAction transitions create → dismiss → restore', function () {
    $action = app(ToggleReadingListAction::class);

    // 1st: create
    expect($action->handle($this->visitor, $this->post))->toBeTrue();
    expect($this->visitor->readingListItems()->active()->count())->toBe(1);

    // 2nd: dismiss
    expect($action->handle($this->visitor, $this->post))->toBeFalse();
    expect($this->visitor->readingListItems()->active()->count())->toBe(0)
        ->and($this->visitor->readingListItems()->dismissed()->count())->toBe(1);

    // 3rd: re-activate
    expect($action->handle($this->visitor, $this->post))->toBeTrue();
    expect($this->visitor->readingListItems()->active()->count())->toBe(1);
});

// ── Reading History ─────────────────────────────────────────────────────

test('RecordReadAction creates new history on first read', function () {
    $action = app(RecordReadAction::class);

    $history = $action->handle($this->visitor, $this->post);

    expect($history->read_count)->toBe(1)
        ->and($history->user_id)->toBe($this->visitor->id);
});

test('RecordReadAction same day does not bump read_count', function () {
    $action = app(RecordReadAction::class);

    $action->handle($this->visitor, $this->post);
    $second = $action->handle($this->visitor, $this->post);

    expect($second->read_count)->toBe(1);
});

test('RecordReadAction bumps read_count when read on a later day', function () {
    $existing = ReadingHistory::factory()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $this->post->id,
        'first_read_at' => now()->subDays(2),
        'last_read_at' => now()->subDays(2),
        'read_count' => 1,
    ]);

    app(RecordReadAction::class)->handle($this->visitor, $this->post);

    expect($existing->fresh()->read_count)->toBe(2);
});

test('RecordReadAction accumulates read_duration_seconds across calls', function () {
    $action = app(RecordReadAction::class);

    $action->handle($this->visitor, $this->post, durationSeconds: 30);
    $action->handle($this->visitor, $this->post, durationSeconds: 45);

    $history = $this->visitor->readingHistory()->where('post_id', $this->post->id)->first();
    expect($history->read_duration_seconds)->toBe(75);
});

test('RecordReadAction sets completed when explicitly true', function () {
    app(RecordReadAction::class)->handle($this->visitor, $this->post, completed: true);

    expect($this->visitor->readingHistory()->where('post_id', $this->post->id)->first()->completed)
        ->toBeTrue();
});

// ── Highlights ──────────────────────────────────────────────────────────

test('CreateHighlightAction stores selected text and context hash', function () {
    $action = app(CreateHighlightAction::class);

    $highlight = $action->handle($this->visitor, $this->post, [
        'selected_text' => 'AI is reshaping how we read.',
        'note' => null,
    ]);

    expect($highlight->selected_text)->toBe('AI is reshaping how we read.')
        ->and($highlight->context_hash)->toHaveLength(40);
});

test('CreateHighlightAction rejects empty selection', function () {
    app(CreateHighlightAction::class)->handle($this->visitor, $this->post, [
        'selected_text' => '   ',
    ]);
})->throws(ValidationException::class);

test('CreateHighlightAction truncates selections longer than 2000 chars', function () {
    $longText = str_repeat('a', 2500);

    $highlight = app(CreateHighlightAction::class)->handle($this->visitor, $this->post, [
        'selected_text' => $longText,
    ]);

    expect(mb_strlen($highlight->selected_text))->toBe(2000);
});

test('UpdateHighlightNoteAction edits only the note', function () {
    $highlight = Highlight::factory()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $this->post->id,
        'selected_text' => 'Original passage',
        'note' => null,
    ]);

    app(UpdateHighlightNoteAction::class)->handle($highlight, 'My take on this');

    $fresh = $highlight->fresh();
    expect($fresh->note)->toBe('My take on this')
        ->and($fresh->selected_text)->toBe('Original passage');
});

test('DeleteHighlightAction removes the row', function () {
    $highlight = Highlight::factory()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $this->post->id,
    ]);

    expect(app(DeleteHighlightAction::class)->handle($highlight))->toBeTrue()
        ->and(Highlight::query()->find($highlight->id))->toBeNull();
});
