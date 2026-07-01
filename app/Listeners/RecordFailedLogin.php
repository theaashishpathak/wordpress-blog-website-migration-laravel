<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Support\IpGeolocator;
use App\Support\UserAgentParser;
use Illuminate\Auth\Events\Failed;

/**
 * Records every failed login attempt to the login_logs table with
 * status='failed'. Captures the email the actor tried so security
 * audits can spot enumeration attempts (one IP probing many emails).
 *
 * Auto-discovered by Laravel via the `handle(Failed $event)` signature.
 */
class RecordFailedLogin
{
    public function handle(Failed $event): void
    {
        try {
            $request = request();
            $ua = $request?->userAgent();
            $ip = $request?->ip();
            $parsed = UserAgentParser::parse($ua);
            $geo = IpGeolocator::lookup($ip);

            // Pull the attempted email from the credentials bag — it
            // could be under 'email' or a custom field. We don't store
            // the password obviously.
            $credentials = (array) ($event->credentials ?? []);
            $attemptedEmail = $credentials['email']
                ?? $credentials['username']
                ?? $credentials['login']
                ?? null;

            LoginLog::create([
                // user_id may still be set if the email matched but
                // password failed; Laravel passes the User on those.
                'user_id' => $event->user?->getAuthIdentifier(),
                'ip_address' => $ip,
                'user_agent' => $ua,
                'device' => $parsed['device'],
                'platform' => $parsed['platform'],
                'browser' => $parsed['browser'],
                'device_type' => $parsed['device_type'],
                'country' => $geo['country'],
                'country_code' => $geo['country_code'],
                'city' => $geo['city'],
                'status' => LoginLog::STATUS_FAILED,
                'attempted_email' => is_string($attemptedEmail) ? mb_substr($attemptedEmail, 0, 191) : null,
                'login_at' => now(),
            ]);
        } catch (\Throwable) {
            // Never crash the auth flow if logging fails.
        }
    }
}
