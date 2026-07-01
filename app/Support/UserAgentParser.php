<?php

namespace App\Support;

class UserAgentParser
{
    /**
     * @return array{device: string, platform: string, browser: string, device_type: string}
     */
    public static function parse(?string $ua): array
    {
        $ua = (string) $ua;
        if ($ua === '') {
            return ['device' => 'Unknown', 'platform' => 'Unknown', 'browser' => 'Unknown', 'device_type' => 'Desktop'];
        }

        $browser = match (true) {
            (bool) preg_match('/Edg\//i', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/Firefox/i', $ua) => 'Firefox',
            (bool) preg_match('/Chrome/i', $ua) => 'Chrome',
            (bool) preg_match('/Safari/i', $ua) => 'Safari',
            (bool) preg_match('/MSIE|Trident/i', $ua) => 'IE',
            default => 'Other',
        };

        $platform = match (true) {
            (bool) preg_match('/iPhone|iPad|iPod/i', $ua) => 'iOS',
            (bool) preg_match('/Android/i', $ua) => 'AndroidOS',
            (bool) preg_match('/Windows/i', $ua) => 'Windows',
            (bool) preg_match('/Macintosh|Mac OS X/i', $ua) => 'macOS',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => 'Unknown',
        };

        $device = match (true) {
            (bool) preg_match('/iPhone/i', $ua) => 'iPhone',
            (bool) preg_match('/iPad/i', $ua) => 'iPad',
            (bool) preg_match('/Android/i', $ua) => 'WebKit',
            (bool) preg_match('/AppleWebKit/i', $ua) => 'WebKit',
            (bool) preg_match('/Gecko/i', $ua) => 'Gecko',
            default => 'Other',
        };

        $deviceType = match (true) {
            (bool) preg_match('/iPad|Tablet/i', $ua) => 'Tablet',
            (bool) preg_match('/Mobile|Android|iPhone/i', $ua) => 'Mobile',
            default => 'Desktop',
        };

        return [
            'device' => $device,
            'platform' => $platform,
            'browser' => $browser,
            'device_type' => $deviceType,
        ];
    }
}
