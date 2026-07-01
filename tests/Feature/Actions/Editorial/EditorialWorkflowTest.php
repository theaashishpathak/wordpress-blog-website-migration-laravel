<?php

declare(strict_types=1);

use App\Actions\Editorial\ApprovePostAction;
use App\Actions\Editorial\RejectPostAction;
use App\Actions\Editorial\RequestChangesAction;
use App\Actions\Editorial\SubmitForReviewAction;
use App\Enums\PostStatus;
use App\Models\EditorialNote;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    $this->author = User::factory()->create();
    $this->editor = User::factory()->create();
});

test('submit-for-review moves Draft -> PendingReview', function (): void {
    $post = Post::factory()->draft()->withAuthor($this->author->id)->create();

    app(SubmitForReviewAction::class)->handle($post, $this->author);

    expect($post->fresh()->status)->toBe(PostStatus::PendingReview);
});

test('submit-for-review attaches optional author note', function (): void {
    $post = Post::factory()->draft()->withAuthor($this->author->id)->create();

    app(SubmitForReviewAction::class)->handle($post, $this->author, 'Ready for review!');

    expect($post->fresh()->editorialNotes()->count())->toBe(1);
    $note = $post->fresh()->editorialNotes()->first();
    expect($note->body)->toBe('Ready for review!');
    expect($note->author_id)->toBe($this->author->id);
});

test('submit-for-review rejects posts in invalid source states', function (): void {
    $post = Post::factory()->published()->create();

    app(SubmitForReviewAction::class)->handle($post, $this->author);
})->throws(InvalidArgumentException::class);

test('approve moves PendingReview -> Approved with note', function (): void {
    $post = Post::factory()->pendingReview()->create();

    app(ApprovePostAction::class)->handle($post, $this->editor, 'Looks great!');

    $post->refresh();
    expect($post->status)->toBe(PostStatus::Approved);
    expect($post->editorialNotes()->ofType(EditorialNote::TYPE_APPROVE)->count())->toBe(1);
    expect($post->editorialNotes()->first()->body)->toBe('Looks great!');
});

test('approve creates a default note even when caller omits one', function (): void {
    $post = Post::factory()->pendingReview()->create();

    app(ApprovePostAction::class)->handle($post, $this->editor);

    $note = $post->fresh()->editorialNotes()->first();
    expect($note)->not->toBeNull();
    expect($note->body)->toBe('Approved.');
});

test('approve rejects invalid source state', function (): void {
    $post = Post::factory()->draft()->create();

    app(ApprovePostAction::class)->handle($post, $this->editor);
})->throws(InvalidArgumentException::class);

test('reject requires a reason', function (): void {
    $post = Post::factory()->pendingReview()->create();

    app(RejectPostAction::class)->handle($post, $this->editor, '   ');
})->throws(ValidationException::class);

test('reject moves to Rejected with reject note', function (): void {
    $post = Post::factory()->pendingReview()->create();

    app(RejectPostAction::class)->handle($post, $this->editor, 'Off-brand content.');

    $post->refresh();
    expect($post->status)->toBe(PostStatus::Rejected);
    expect($post->editorialNotes()->ofType(EditorialNote::TYPE_REJECT)->count())->toBe(1);
    expect($post->editorialNotes()->first()->body)->toBe('Off-brand content.');
});

test('request-changes requires feedback', function (): void {
    $post = Post::factory()->pendingReview()->create();

    app(RequestChangesAction::class)->handle($post, $this->editor, '');
})->throws(ValidationException::class);

test('request-changes moves to ChangesRequested with feedback note', function (): void {
    $post = Post::factory()->pendingReview()->create();

    app(RequestChangesAction::class)->handle($post, $this->editor, 'Fix the intro paragraph.');

    $post->refresh();
    expect($post->status)->toBe(PostStatus::ChangesRequested);
    expect($post->editorialNotes()->ofType(EditorialNote::TYPE_REQUEST_CHANGES)->count())->toBe(1);
});

test('author can re-submit after request-changes', function (): void {
    $post = Post::factory()->state([
        'status' => PostStatus::ChangesRequested,
        'author_id' => $this->author->id,
    ])->create();

    app(SubmitForReviewAction::class)->handle($post, $this->author, 'Addressed feedback.');

    expect($post->fresh()->status)->toBe(PostStatus::PendingReview);
});

test('author can re-submit a rejected post', function (): void {
    $post = Post::factory()->state([
        'status' => PostStatus::Rejected,
        'author_id' => $this->author->id,
    ])->create();

    // First Draft via direct transition (Rejected → Draft is allowed).
    expect(PostStatus::Rejected->canTransitionTo(PostStatus::Draft))->toBeTrue();

    // Then re-submit from Draft.
    $post->update(['status' => PostStatus::Draft]);
    app(SubmitForReviewAction::class)->handle($post, $this->author);

    expect($post->fresh()->status)->toBe(PostStatus::PendingReview);
});
