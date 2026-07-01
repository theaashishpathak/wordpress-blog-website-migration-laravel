<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Support\IpGeolocator;
use App\Support\UserAgentParser;
use Illuminate\Auth\Events\Logout;

/**
 * Records explicit logouts so session-duration analytics + remote
 * sign-out audits are possible. Status='logout' keeps these rows out
 * of the "active 7d" counters which filter on status='success'.
 *
 * Auto-discovered by Laravel via the `handle(Logout $event)` signature.
 */
class RecordLogoutLog
{
    public function handle(Logout $event): void
    {
        try {
            // No-op when the framework dispatches a Logout without a
            // user (e.g. anonymous session expiry through guard()).
            $userId = $event->user?->getAuthIdentifier();
            if ($userId === null) {
                return;
            }

            $request = request();
            $ua = $request?->userAgent();
            $ip = $request?->ip();
            $parsed = UserAgentParser::parse($ua);
            $geo = IpGeolocator::lookup($ip);

            LoginLog::create([
                'user_id' => $userId,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'device' => $parsed['device'],
                'platform' => $parsed['platform'],
                'browser' => $parsed['browser'],
                'device_type' => $parsed['device_type'],
                'country' => $geo['country'],
                'country_code' => $geo['country_code'],
                'city' => $geo['city'],
                'status' => LoginLog::STATUS_LOGOUT,
                'login_at' => now(),
            ]);
        } catch (\Throwable) {
            // Never crash logout if logging fails.
        }
    }
}
