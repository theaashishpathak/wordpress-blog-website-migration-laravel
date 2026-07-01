<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Light-weight IP → country/city lookup using a free public API.
 * Results are cached for 30 days; private/loopback IPs are skipped.
 *
 * Provider: ip-api.com (no key needed, ~45 req/min limit). Override via
 * `IP_GEO_PROVIDER_URL` env if you want a different provider/format.
 */
class IpGeolocator
{
    public const CACHE_PREFIX = 'ip-geo:';

    public const CACHE_TTL_SECONDS = 60 * 60 * 24 * 30; // 30 days

    /**
     * @return array{country: ?string, country_code: ?string, city: ?string, region: ?string}
     */
    public static function lookup(?string $ip): array
    {
        $empty = ['country' => null, 'country_code' => null, 'city' => null, 'region' => null];

        if (! is_string($ip) || $ip === '' || self::isPrivateOrReserved($ip)) {
            return $empty;
        }

        // Allow disabling entirely via env (useful in offline / testing).
        if (env('IP_GEO_ENABLED', true) === false) {
            return $empty;
        }

        $key = self::CACHE_PREFIX.$ip;

        $cached = Cache::get($key);
        if (is_array($cached)) {
            return array_merge($empty, $cached);
        }

        $url = (string) env('IP_GEO_PROVIDER_URL', 'http://ip-api.com/json/');
        $endpoint = rtrim($url, '/').'/'.$ip.'?fields=status,country,countryCode,regionName,city';

        try {
            $response = Http::timeout(3)->retry(0)->get($endpoint);
        } catch (\Throwable) {
            // Cache the negative result for a shorter window so we don't spam on outage.
            Cache::put($key, $empty, 60 * 30);

            return $empty;
        }

        if (! $response->successful()) {
            Cache::put($key, $empty, 60 * 30);

            return $empty;
        }

        $body = $response->json();

        if (! is_array($body) || ($body['status'] ?? null) !== 'success') {
            Cache::put($key, $empty, 60 * 30);

            return $empty;
        }

        $result = [
            'country' => isset($body['country']) ? (string) $body['country'] : null,
            'country_code' => isset($body['countryCode']) ? (string) $body['countryCode'] : null,
            'city' => isset($body['city']) ? (string) $body['city'] : null,
            'region' => isset($body['regionName']) ? (string) $body['regionName'] : null,
        ];

        Cache::put($key, $result, self::CACHE_TTL_SECONDS);

        return $result;
    }

    /**
     * Skip private/loopback/reserved address blocks (no useful geo data).
     */
    public static function isPrivateOrReserved(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return ! (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
