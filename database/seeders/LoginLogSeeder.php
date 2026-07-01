<?php

namespace Database\Seeders;

use App\Models\LoginLog;
use App\Models\User;
use App\Support\UserAgentParser;
use Illuminate\Database\Seeder;

class LoginLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->limit(5)->get();
        if ($users->isEmpty()) {
            return;
        }

        $samples = [
            ['ua' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36', 'ip' => '203.0.113.42'],
            ['ua' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15', 'ip' => '198.51.100.7'],
            ['ua' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1', 'ip' => '192.0.2.18'],
            ['ua' => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Mobile Safari/537.36', 'ip' => '203.0.113.91'],
            ['ua' => 'Mozilla/5.0 (iPad; CPU OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1', 'ip' => '198.51.100.55'],
            ['ua' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0', 'ip' => '203.0.113.130'],
            ['ua' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36 Edg/124.0', 'ip' => '198.51.100.200'],
            ['ua' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36', 'ip' => '192.0.2.231'],
        ];

        // Distribution: 8 success / 1 failed / 1 logout — matches a
        // healthy auth audit profile out of the gate.
        $statusPool = [
            LoginLog::STATUS_SUCCESS, LoginLog::STATUS_SUCCESS,
            LoginLog::STATUS_SUCCESS, LoginLog::STATUS_SUCCESS,
            LoginLog::STATUS_SUCCESS, LoginLog::STATUS_SUCCESS,
            LoginLog::STATUS_SUCCESS, LoginLog::STATUS_SUCCESS,
            LoginLog::STATUS_FAILED,
            LoginLog::STATUS_LOGOUT,
        ];

        foreach (range(1, 10) as $i) {
            $sample = $samples[array_rand($samples)];
            $parsed = UserAgentParser::parse($sample['ua']);
            $user = $users->random();
            $status = $statusPool[($i - 1) % count($statusPool)];

            LoginLog::create([
                // Failed attempts may have no resolvable user; null it
                // half the time and store the attempted email instead.
                'user_id' => $status === LoginLog::STATUS_FAILED && $i % 2 === 0 ? null : $user->id,
                'attempted_email' => $status === LoginLog::STATUS_FAILED
                    ? ($i % 2 === 0 ? 'unknown'.$i.'@example.com' : $user->email)
                    : null,
                'ip_address' => $sample['ip'],
                'user_agent' => $sample['ua'],
                'device' => $parsed['device'],
                'platform' => $parsed['platform'],
                'browser' => $parsed['browser'],
                'device_type' => $parsed['device_type'],
                'status' => $status,
                'login_at' => now()->subHours(random_int(1, 240)),
            ]);
        }
    }
}
