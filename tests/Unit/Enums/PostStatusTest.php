<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use Tests\TestCase;

uses(TestCase::class);

test('values returns all 10 enum values', function (): void {
    expect(PostStatus::values())->toHaveCount(10);
    expect(PostStatus::values())->toContain('draft');
    expect(PostStatus::values())->toContain('published');
    expect(PostStatus::values())->toContain('archived');
});

test('isVisibleToPublic returns true only for Published', function (): void {
    expect(PostStatus::Published->isVisibleToPublic())->toBeTrue();

    foreach (PostStatus::cases() as $status) {
        if ($status !== PostStatus::Published) {
            expect($status->isVisibleToPublic())->toBeFalse();
        }
    }
});

test('isInEditorialFlow covers review pipeline states', function (): void {
    expect(PostStatus::PendingReview->isInEditorialFlow())->toBeTrue();
    expect(PostStatus::InReview->isInEditorialFlow())->toBeTrue();
    expect(PostStatus::ChangesRequested->isInEditorialFlow())->toBeTrue();
    expect(PostStatus::Approved->isInEditorialFlow())->toBeTrue();

    expect(PostStatus::Draft->isInEditorialFlow())->toBeFalse();
    expect(PostStatus::Published->isInEditorialFlow())->toBeFalse();
    expect(PostStatus::Archived->isInEditorialFlow())->toBeFalse();
});

test('terminal states cannot transition further', function (): void {
    expect(PostStatus::Archived->isTerminal())->toBeTrue();
    expect(PostStatus::Archived->allowedNextStates())->toBe([]);
});

test('Draft can transition to PendingReview or Published', function (): void {
    expect(PostStatus::Draft->canTransitionTo(PostStatus::PendingReview))->toBeTrue();
    expect(PostStatus::Draft->canTransitionTo(PostStatus::Published))->toBeTrue();

    expect(PostStatus::Draft->canTransitionTo(PostStatus::Approved))->toBeFalse();
    expect(PostStatus::Draft->canTransitionTo(PostStatus::Archived))->toBeFalse();
});

test('Approved can transition to Scheduled or Published', function (): void {
    expect(PostStatus::Approved->canTransitionTo(PostStatus::Scheduled))->toBeTrue();
    expect(PostStatus::Approved->canTransitionTo(PostStatus::Published))->toBeTrue();
});

test('Published can transition to Unpublished or Archived', function (): void {
    expect(PostStatus::Published->canTransitionTo(PostStatus::Unpublished))->toBeTrue();
    expect(PostStatus::Published->canTransitionTo(PostStatus::Archived))->toBeTrue();

    expect(PostStatus::Published->canTransitionTo(PostStatus::Draft))->toBeFalse();
});

test('cannot transition to the same status', function (): void {
    foreach (PostStatus::cases() as $status) {
        expect($status->canTransitionTo($status))->toBeFalse();
    }
});

test('label produces human-readable text', function (): void {
    expect(PostStatus::Draft->label())->toBe('Draft');
    expect(PostStatus::PendingReview->label())->toBe('Pending Review');
    expect(PostStatus::ChangesRequested->label())->toBe('Changes Requested');
});
