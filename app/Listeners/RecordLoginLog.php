<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Support\IpGeolocator;
use App\Support\UserAgentParser;
use Illuminate\Auth\Events\Login;

class RecordLoginLog
{
    public function handle(Login $event): void
    {
        try {
            $request = request();
            $ua = $request?->userAgent();
            $ip = $request?->ip();
            $parsed = UserAgentParser::parse($ua);
            $geo = IpGeolocator::lookup($ip);

            LoginLog::create([
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
                'status' => LoginLog::STATUS_SUCCESS,
                'login_at' => now(),
            ]);
        } catch (\Throwable) {
            // Never crash login if logging fails.
        }
    }
}
