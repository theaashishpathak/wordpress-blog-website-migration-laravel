<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * HandleRedirects Middleware
 *
 * Intercepts incoming web requests to perform 301/302 redirects managed in the `redirects` table.
 * Preserves query strings when enabled, tracks hit statistics, and prevents redirect loops.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        // Don't intercept livewire internal or asset requests
        if ($request->is('livewire/*', '_debugbar/*', 'storage/*', 'build/*')) {
            return $next($request);
        }

        if (!Schema::hasTable('redirects')) {
            return $next($request);
        }

        $path = '/' . ltrim($request->path(), '/');
        $fullUri = $request->getRequestUri();

        // Strip legacy /en/ prefix so default locale URLs are clean
        if ($path === '/en' || $path === '/en/') {
            return redirect('/', 301);
        }
        if (str_starts_with($path, '/en/')) {
            $cleanPath = substr($path, 3);
            $query = $request->getQueryString() ? '?' . $request->getQueryString() : '';
            return redirect($cleanPath . $query, 301);
        }

        // 1. Check exact URI match (including query params if rule specified)
        $rule = Redirect::query()
            ->active()
            ->where(function ($q) use ($path, $fullUri) {
                $q->where('from_path', $path)
                  ->orWhere('from_path', rtrim($path, '/') . '/')
                  ->orWhere('from_path', $fullUri);
            })
            ->first();

        if ($rule) {
            $destination = $rule->to_url;

            // Prevent redirect loop
            if ($destination === $path || $destination === $fullUri) {
                return $next($request);
            }

            // Append query string if configured and request has query string
            if ($rule->preserve_query && $request->getQueryString()) {
                $separator = str_contains($destination, '?') ? '&' : '?';
                $destination .= $separator . $request->getQueryString();
            }

            // Update hit statistics asynchronously / silently
            try {
                $rule->increment('hit_count');
                $rule->updateQuietly(['last_hit_at' => now()]);
            } catch (\Throwable $e) {
                // Ignore tracking failures
            }

            return redirect($destination, $rule->status_code ?: 301);
        }

        return $next($request);
    }
}
