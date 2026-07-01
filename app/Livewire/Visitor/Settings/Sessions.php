<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Settings;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Active session list — only meaningful when SESSION_DRIVER=database.
 * On file/redis/array drivers we fall back to a "current device only"
 * message so the page is still useful.
 *
 * Each row is decoded from the user_agent + ip_address columns; "current"
 * session is identified by matching the runtime session id.
 */
#[Layout('layouts.visitor')]
#[Title('Sessions & Devices')]
class Sessions extends Component
{
    public string $password = '';

    public function logoutOthers(): void
    {
        $this->validate(['password' => ['required', 'string']]);

        if (! Hash::check($this->password, auth()->user()->password)) {
            $this->addError('password', 'That password is incorrect.');

            return;
        }

        // Invalidate every other session — only works on db/redis drivers.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', auth()->id())
                ->where('id', '!=', request()->session()->getId())
                ->delete();
        }

        auth()->logoutOtherDevices($this->password);

        auth()->user()?->logProfileActivity(
            'sessions_revoked_all',
            'Signed out from all other devices.',
        );

        $this->password = '';
        $this->dispatch('toast', message: 'Signed out from all other devices.');
    }

    public function revoke(string $sessionId): void
    {
        $currentId = request()->hasSession() ? request()->session()->getId() : null;

        if ($sessionId === $currentId) {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('id', $sessionId)
            ->where('user_id', auth()->id())
            ->delete();

        auth()->user()?->logProfileActivity(
            'session_revoked',
            'Signed out a single remote session.',
            ['session_id_hash' => sha1($sessionId)],
        );

        unset($this->sessions);
        $this->dispatch('toast', message: 'Session signed out.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    #[Computed]
    public function sessions(): \Illuminate\Support\Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        $currentId = request()->hasSession() ? request()->session()->getId() : null;

        $rows = DB::table(config('session.table', 'sessions'))
            ->where('user_id', auth()->id())
            ->orderByDesc('last_activity')
            ->get();

        $agentParser = class_exists(Agent::class) ? new Agent : null;

        return $rows->map(function ($row) use ($currentId, $agentParser) {
            $ua = (string) ($row->user_agent ?? '');
            $browser = 'Unknown browser';
            $platform = 'Unknown OS';
            $isMobile = false;

            if ($agentParser !== null) {
                $agentParser->setUserAgent($ua);
                $browser = $agentParser->browser() ?: 'Unknown';
                $platform = $agentParser->platform() ?: 'Unknown';
                $isMobile = $agentParser->isMobile() || $agentParser->isTablet();
            } else {
                // Lightweight fallback so the page still renders without
                // the jenssegers/agent dependency installed.
                $browser = match (true) {
                    str_contains($ua, 'Chrome/') => 'Chrome',
                    str_contains($ua, 'Firefox/') => 'Firefox',
                    str_contains($ua, 'Safari/') && ! str_contains($ua, 'Chrome/') => 'Safari',
                    str_contains($ua, 'Edg/') => 'Edge',
                    default => 'Browser',
                };
                $platform = match (true) {
                    str_contains($ua, 'Windows') => 'Windows',
                    str_contains($ua, 'Mac OS X') => 'macOS',
                    str_contains($ua, 'Linux') => 'Linux',
                    str_contains($ua, 'Android') => 'Android',
                    str_contains($ua, 'iPhone') => 'iOS',
                    default => 'Device',
                };
                $isMobile = str_contains($ua, 'Mobile') || str_contains($ua, 'Android') || str_contains($ua, 'iPhone');
            }

            return (object) [
                'id' => $row->id,
                'ip' => $row->ip_address,
                'browser' => $browser,
                'platform' => $platform,
                'is_mobile' => $isMobile,
                'last_activity' => \Carbon\Carbon::createFromTimestamp((int) $row->last_activity),
                'is_current' => $row->id === $currentId,
            ];
        });
    }

    public function render(): View
    {
        return view('livewire.visitor.settings.sessions', [
            'driver' => config('session.driver'),
        ]);
    }
}
