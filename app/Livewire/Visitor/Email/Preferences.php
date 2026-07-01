<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Email;

use App\Models\NotificationPreference;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Visitor notification + email preferences matrix.
 *
 * State shape:
 *   $prefs = [
 *     'comment_reply' => ['in_app' => true, 'email' => false],
 *     ...
 *   ]
 *
 * Mounted lazily from the eventCatalog + existing user_notification rows.
 * Saving writes upserts via NotificationPreference::setValue().
 */
#[Layout('layouts.visitor')]
#[Title('Notification Preferences')]
class Preferences extends Component
{
    /** @var array<string, array<string, bool>> */
    public array $prefs = [];

    public bool $masterEmailMute = false;

    public function mount(): void
    {
        $this->loadPrefs();
    }

    public function toggle(string $key, string $channel): void
    {
        $current = (bool) ($this->prefs[$key][$channel] ?? false);
        $next = ! $current;

        NotificationPreference::setValue(auth()->id(), $key, $channel, $next);
        $this->prefs[$key][$channel] = $next;

        $this->dispatch('toast', message: $next ? 'Notification on.' : 'Notification off.');
    }

    /**
     * Master switch — mutes/unmutes the email channel for every event in
     * one click. The mute state is stored on user_settings so it survives
     * page reloads even if individual events have mixed values.
     */
    public function toggleMasterEmailMute(): void
    {
        $next = ! $this->masterEmailMute;

        \App\Models\UserSetting::setValue(auth()->id(), 'email_master_muted', $next);

        // When muting, write enabled=false for every event's email channel.
        // When unmuting, restore each event's catalog default for email.
        foreach (NotificationPreference::eventCatalog() as $key => $config) {
            if (! in_array('email', $config['channels'] ?? [], true)) {
                continue;
            }
            $value = $next ? false : (bool) ($config['defaults']['email'] ?? false);
            NotificationPreference::setValue(auth()->id(), $key, 'email', $value);
        }

        $this->masterEmailMute = $next;
        $this->loadPrefs();
        $this->dispatch('toast', message: $next ? 'Muted all emails.' : 'Restored default email settings.');
    }

    private function loadPrefs(): void
    {
        $catalog = NotificationPreference::eventCatalog();
        $userId = auth()->id();

        $stored = NotificationPreference::query()
            ->where('user_id', $userId)
            ->get()
            ->groupBy('key');

        foreach ($catalog as $key => $config) {
            foreach ($config['channels'] as $channel) {
                $row = $stored->get($key)?->firstWhere('channel', $channel);
                $this->prefs[$key][$channel] = $row !== null
                    ? (bool) $row->enabled
                    : (bool) ($config['defaults'][$channel] ?? false);
            }
        }

        $this->masterEmailMute = (bool) \App\Models\UserSetting::getValue($userId, 'email_master_muted', false);
    }

    public function render(): View
    {
        return view('livewire.visitor.email.preferences', [
            'catalog' => NotificationPreference::eventCatalog(),
        ]);
    }
}
