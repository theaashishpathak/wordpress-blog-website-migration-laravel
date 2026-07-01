<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Data;

use App\Actions\Visitor\Data\CancelAccountDeletionAction;
use App\Actions\Visitor\Data\RequestAccountDeletionAction;
use App\Models\AccountDeletionRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.visitor')]
#[Title('Delete Account')]
class Delete extends Component
{
    public string $reason = '';

    public string $note = '';

    public string $confirmText = '';

    public string $password = '';

    public const REASONS = [
        'no_longer_needed' => 'I no longer need an account',
        'privacy_concerns' => 'Privacy concerns',
        'too_many_emails' => 'Too many emails / notifications',
        'using_alternative' => 'Using another news platform',
        'other' => 'Other',
    ];

    /** @return AccountDeletionRequest|null */
    #[Computed]
    public function pending()
    {
        return AccountDeletionRequest::query()
            ->where('user_id', auth()->id())
            ->pending()
            ->latest()
            ->first();
    }

    public function submit(): void
    {
        $this->validate([
            'reason' => 'nullable|in:'.implode(',', array_keys(self::REASONS)),
            'note' => 'nullable|string|max:1000',
            'confirmText' => 'required|in:DELETE',
            'password' => 'required|string',
        ], messages: [
            'confirmText.in' => 'Type DELETE in capital letters to confirm.',
        ]);

        if (! Hash::check($this->password, auth()->user()->password)) {
            $this->addError('password', 'That password is incorrect.');

            return;
        }

        try {
            app(RequestAccountDeletionAction::class)->handle(
                user: auth()->user(),
                reason: $this->reason ?: null,
                note: $this->note ?: null,
            );

            $this->reset(['reason', 'note', 'confirmText', 'password']);
            unset($this->pending);
            $this->dispatch('toast', message: 'Deletion scheduled — you have 30 days to change your mind.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', message: $e->validator->errors()->first() ?: 'Could not schedule deletion.');
        }
    }

    public function cancel(): void
    {
        $pending = $this->pending;

        if ($pending === null) {
            return;
        }

        app(CancelAccountDeletionAction::class)->handle(auth()->user(), $pending);

        unset($this->pending);
        $this->dispatch('toast', message: 'Deletion cancelled — your account is safe.');
    }

    public function render(): View
    {
        return view('livewire.visitor.data.delete', [
            'reasons' => self::REASONS,
        ]);
    }
}
