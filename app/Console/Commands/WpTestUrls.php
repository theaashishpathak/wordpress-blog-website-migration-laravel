<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class WpTestUrls extends Command
{
    protected $signature = 'wp:test-urls';
    protected $description = 'Comprehensive route testing for frontend, dashboard and admin';

    public function handle(): int
    {
        $adminUser = User::first();
        if ($adminUser) {
            auth()->login($adminUser);
        }

        $urls = [
            '/',
            '/dashboard',
            '/settings',
            '/admin/posts',
            '/admin/posts/11130/edit',
            '/author/1',
            '/category/health',
            '/search',
            '/reverse-deteriorating-dental-health',
            '/mathematics-teacher',
        ];

        $kernel = app()->make(\Illuminate\Contracts\Http\Kernel::class);
        $allPassed = true;

        foreach ($urls as $url) {
            $req = Request::create($url, 'GET');
            try {
                $route = app('router')->getRoutes()->match($req);
                $response = $kernel->handle($req);
                $status = $response->getStatusCode();
                $routeName = $route->getName() ?? 'unnamed';
                if ($status === 200 || $status === 302) {
                    $this->info("✓ URL: '{$url}' -> Status: {$status} | Route: {$routeName}");
                } else {
                    $this->error("✗ URL: '{$url}' -> Status: {$status} | Route: {$routeName}");
                    $this->line("Content preview: " . substr($response->getContent(), 0, 300));
                    $allPassed = false;
                }
            } catch (\Throwable $e) {
                $this->error("✗ URL: '{$url}' -> Exception: " . $e->getMessage());
                $allPassed = false;
            }
        }

        return $allPassed ? 0 : 1;
    }
}
