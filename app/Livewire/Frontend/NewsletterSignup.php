<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Actions\Newsletter\SubscribeToNewsletterAction;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Inline newsletter signup widget — usable from footer, sidebar, or
 * as an in-article CTA.
 *
 * Built-in honeypot field (`hp`) catches form-fillers that bypass JS;
 * it's never displayed to humans (Tailwind hidden class + autocomplete
 * off) but unauthenticated bots can't see that and will fill it.
 */
class NewsletterSignup extends Component
{
    public string $email = '';

    public string $name = '';

    /** Honeypot — humans never see this field. */
    public string $hp = '';

    #[Locked]
    public string $source = 'inline_widget';

    public string $variant = 'inline';   // inline | footer | hero

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $submitted = false;

    public function mount(string $source = 'inline_widget', string $variant = 'inline'): void
    {
        $this->source = $source;
        $this->variant = $variant;
    }

    public function subscribe(SubscribeToNewsletterAction $subscribe): void
    {
        // Honeypot hit — silently succeed so the bot doesn't probe further.
        if ($this->hp !== '') {
            $this->submitted = true;
            $this->successMessage = 'Thanks! Check your email to confirm.';

            return;
        }

        $this->validate([
            'email' => ['required', 'email:rfc,filter', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $subscribe->handle($this->email, [
                'name' => $this->name !== '' ? $this->name : null,
                'source' => $this->source,
                'ip' => request()->ip(),
                'user_agent' => (string) request()->userAgent(),
            ]);

            $this->submitted = true;
            $this->successMessage = 'Almost there! Check your inbox for a confirmation link.';
            $this->reset(['email', 'name']);
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = 'Subscription failed. Please try again later.';
        }
    }

    public function render(): View
    {
        return view('livewire.frontend.newsletter-signup');
    }
}
