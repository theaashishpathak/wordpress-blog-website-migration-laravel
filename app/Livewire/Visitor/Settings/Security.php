<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Settings;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Account & Security — password change + Fortify 2FA state preview.
 *
 * 2FA enable/disable flows themselves live behind Fortify's existing
 * POST /user/two-factor-authentication endpoint (and friends). We only
 * surface the current state here + provide buttons that submit to those
 * routes, so the heavy lifting (secret generation, QR code, recovery
 * codes) stays inside Fortify.
 */
#[Layout('layouts.visitor')]
#[Title('Account & Security')]
class Security extends Component
{
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function changePassword(): void
    {
        // Laravel's `confirmed` rule looks for `{field}_confirmation` (snake_case),
        // which clashes with our camelCase property name. Use `same:` instead so
        // the rule keys match what Livewire dispatches.
        $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'same:newPasswordConfirmation', Password::defaults()],
            'newPasswordConfirmation' => ['required', 'string'],
        ], attributes: [
            'newPassword' => 'new password',
            'newPasswordConfirmation' => 'password confirmation',
        ]);

        $user = auth()->user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'That password is incorrect.');

            return;
        }

        $user->forceFill(['password' => Hash::make($this->newPassword)])->save();

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);

        $this->dispatch('toast', message: 'Password updated.');
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.visitor.settings.security', [
            'twoFactorEnabled' => ! is_null($user->two_factor_secret ?? null),
            'twoFactorConfirmed' => ! is_null($user->two_factor_confirmed_at ?? null),
        ]);
    }
}
