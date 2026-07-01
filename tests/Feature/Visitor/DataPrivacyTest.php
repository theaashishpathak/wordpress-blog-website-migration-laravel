<?php

declare(strict_types=1);

use App\Actions\Visitor\Data\CancelAccountDeletionAction;
use App\Actions\Visitor\Data\ProcessAccountDeletionAction;
use App\Actions\Visitor\Data\RequestAccountDeletionAction;
use App\Actions\Visitor\Data\RequestDataExportAction;
use App\Jobs\GenerateUserDataExport;
use App\Livewire\Visitor\Data\Delete as DeletePage;
use App\Livewire\Visitor\Data\Export as ExportPage;
use App\Models\AccountDeletionRequest;
use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\DataExportRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->visitor = User::factory()->visitor()->create([
        'password' => Hash::make('TestPass!12345'),
    ]);
});

// ── Export Action ──────────────────────────────────────────────────────

test('RequestDataExportAction dispatches the queue job', function () {
    Bus::fake();

    app(RequestDataExportAction::class)->handle($this->visitor);

    Bus::assertDispatched(GenerateUserDataExport::class);
    expect(DataExportRequest::query()->where('user_id', $this->visitor->id)->count())->toBe(1);
});

test('RequestDataExportAction blocks duplicate in-flight request', function () {
    Bus::fake();

    app(RequestDataExportAction::class)->handle($this->visitor);
    app(RequestDataExportAction::class)->handle($this->visitor);
})->throws(ValidationException::class);

test('GenerateUserDataExport produces a ZIP and marks ready', function () {
    Storage::fake('local');

    Bookmark::factory()->count(2)->create(['user_id' => $this->visitor->id]);

    $request = DataExportRequest::query()->create([
        'user_id' => $this->visitor->id,
        'status' => DataExportRequest::STATUS_PENDING,
    ]);

    (new GenerateUserDataExport($request))->handle();

    $fresh = $request->fresh();
    expect($fresh->status)->toBe(DataExportRequest::STATUS_READY)
        ->and($fresh->file_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($fresh->file_path))->toBeTrue();
});

// ── Export Livewire page ───────────────────────────────────────────────

test('Export page renders past requests and triggers a new one', function () {
    Bus::fake();

    Livewire::actingAs($this->visitor)
        ->test(ExportPage::class)
        ->call('requestExport');

    Bus::assertDispatched(GenerateUserDataExport::class);
});

// ── Download controller ────────────────────────────────────────────────

test('download returns the file for the owning user', function () {
    Storage::fake('local');

    Storage::disk('local')->put('exports/test.zip', 'zipdata');

    $export = DataExportRequest::query()->create([
        'user_id' => $this->visitor->id,
        'status' => DataExportRequest::STATUS_READY,
        'file_path' => 'exports/test.zip',
        'file_size_bytes' => 7,
        'completed_at' => now(),
        'expires_at' => now()->addDays(7),
    ]);

    $this->actingAs($this->visitor)
        ->get(route('visitor.data.export.download', $export))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/zip');
});

test('download blocks a different users export', function () {
    $other = User::factory()->visitor()->create();
    $export = DataExportRequest::query()->create([
        'user_id' => $other->id,
        'status' => DataExportRequest::STATUS_READY,
        'file_path' => 'exports/test.zip',
        'expires_at' => now()->addDays(7),
    ]);

    $this->actingAs($this->visitor)
        ->get(route('visitor.data.export.download', $export))
        ->assertForbidden();
});

// ── Deletion Actions ───────────────────────────────────────────────────

test('RequestAccountDeletionAction schedules with 30-day grace by default', function () {
    $request = app(RequestAccountDeletionAction::class)->handle($this->visitor);

    expect($request->scheduled_for->between(now()->addDays(29), now()->addDays(31)))->toBeTrue()
        ->and($request->cancelled_at)->toBeNull()
        ->and($request->processed_at)->toBeNull();
});

test('RequestAccountDeletionAction blocks duplicate pending request', function () {
    app(RequestAccountDeletionAction::class)->handle($this->visitor);
    app(RequestAccountDeletionAction::class)->handle($this->visitor);
})->throws(ValidationException::class);

test('CancelAccountDeletionAction stamps cancelled_at', function () {
    $request = app(RequestAccountDeletionAction::class)->handle($this->visitor);

    $cancelled = app(CancelAccountDeletionAction::class)->handle($this->visitor, $request);

    expect($cancelled->cancelled_at)->not->toBeNull();
});

test('ProcessAccountDeletionAction skips when scheduled_for is future', function () {
    $request = app(RequestAccountDeletionAction::class)->handle($this->visitor);

    expect(app(ProcessAccountDeletionAction::class)->handle($request))->toBeFalse()
        ->and(User::query()->find($this->visitor->id))->not->toBeNull();
});

test('ProcessAccountDeletionAction hard-deletes when due', function () {
    $request = app(RequestAccountDeletionAction::class)->handle($this->visitor);
    // Fast-forward the schedule into the past
    $request->update(['scheduled_for' => now()->subDay()]);

    expect(app(ProcessAccountDeletionAction::class)->handle($request))->toBeTrue()
        ->and(User::query()->find($this->visitor->id))->toBeNull();
});

test('ProcessAccountDeletionAction skips a cancelled request', function () {
    $request = app(RequestAccountDeletionAction::class)->handle($this->visitor);
    $request->update(['scheduled_for' => now()->subDay(), 'cancelled_at' => now()]);

    expect(app(ProcessAccountDeletionAction::class)->handle($request))->toBeFalse()
        ->and(User::query()->find($this->visitor->id))->not->toBeNull();
});

// ── Delete Livewire page ──────────────────────────────────────────────

test('Delete page requires correct password + DELETE confirm text', function () {
    Livewire::actingAs($this->visitor)
        ->test(DeletePage::class)
        ->set('reason', 'no_longer_needed')
        ->set('confirmText', 'delete')
        ->set('password', 'TestPass!12345')
        ->call('submit')
        ->assertHasErrors('confirmText');
});

test('Delete page rejects wrong password even with valid confirm', function () {
    Livewire::actingAs($this->visitor)
        ->test(DeletePage::class)
        ->set('reason', 'no_longer_needed')
        ->set('confirmText', 'DELETE')
        ->set('password', 'wrong')
        ->call('submit')
        ->assertHasErrors('password');
});

test('Delete page schedules when all checks pass', function () {
    Livewire::actingAs($this->visitor)
        ->test(DeletePage::class)
        ->set('reason', 'no_longer_needed')
        ->set('confirmText', 'DELETE')
        ->set('password', 'TestPass!12345')
        ->call('submit');

    expect(AccountDeletionRequest::query()->where('user_id', $this->visitor->id)->pending()->count())->toBe(1);
});

test('Delete page cancel button clears pending request', function () {
    app(RequestAccountDeletionAction::class)->handle($this->visitor);

    Livewire::actingAs($this->visitor)
        ->test(DeletePage::class)
        ->call('cancel');

    expect(AccountDeletionRequest::query()->where('user_id', $this->visitor->id)->pending()->count())->toBe(0);
});

// ── Console command ───────────────────────────────────────────────────

test('accounts:process-deletions processes only due requests', function () {
    $futureRequest = app(RequestAccountDeletionAction::class)->handle($this->visitor);

    $other = User::factory()->visitor()->create();
    $dueRequest = app(RequestAccountDeletionAction::class)->handle($other);
    $dueRequest->update(['scheduled_for' => now()->subDay()]);

    $this->artisan('accounts:process-deletions')
        ->expectsOutput('Found 1 due deletion request(s).')
        ->assertSuccessful();

    expect(User::query()->find($this->visitor->id))->not->toBeNull()
        ->and(User::query()->find($other->id))->toBeNull();
});

test('dry run does not delete', function () {
    $other = User::factory()->visitor()->create();
    $dueRequest = app(RequestAccountDeletionAction::class)->handle($other);
    $dueRequest->update(['scheduled_for' => now()->subDay()]);

    $this->artisan('accounts:process-deletions', ['--dry' => true])
        ->expectsOutput('Dry run — nothing deleted.')
        ->assertSuccessful();

    expect(User::query()->find($other->id))->not->toBeNull();
});
