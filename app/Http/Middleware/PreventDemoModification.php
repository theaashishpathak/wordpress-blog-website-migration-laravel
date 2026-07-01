<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDemoModification
{
    /**
     * Methods that mutate state and should be blocked when demo mode is active.
     *
     * @var list<string>
     */
    protected array $writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Route name patterns that are always allowed (login, logout, password reset, etc.).
     *
     * @var list<string>
     */
    protected array $allowedRoutePatterns = [
        'login',
        'logout',
        'password.*',
        'verification.*',
    ];

    /**
     * Path prefixes that are always allowed.
     *
     * Livewire endpoints MUST be whitelisted here because every tab switch,
     * pagination, sort, etc. sends a POST to /livewire/update. Actual data
     * mutation is blocked at the Eloquent layer (see AppServiceProvider).
     *
     * @var list<string>
     */
    protected array $allowedPathPrefixes = [
        'login',
        'logout',
        'forgot-password',
        'reset-password',
        'two-factor-challenge',
        'email/verification-notification',
        'livewire',
        'livewire/update',
        'livewire/upload-file',
        'livewire/preview-file',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('app.demo_mode')) {
            return $next($request);
        }

        if (! in_array($request->method(), $this->writeMethods, true)) {
            return $next($request);
        }

        // Any Livewire request (tab switches, pagination, sorting, etc.) must
        // pass through. Actual data writes are blocked at the Eloquent layer.
        if ($request->hasHeader('X-Livewire') || $request->is('livewire/*')) {
            return $next($request);
        }

        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        $message = 'Demo mode: changes are disabled.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], 403);
        }

        return back()->with('error', $message);
    }

    protected function isAllowedRoute(Request $request): bool
    {
        $routeName = optional($request->route())->getName();

        if (is_string($routeName)) {
            foreach ($this->allowedRoutePatterns as $pattern) {
                if (fnmatch($pattern, $routeName)) {
                    return true;
                }
            }
        }

        foreach ($this->allowedPathPrefixes as $prefix) {
            if ($request->is($prefix) || $request->is($prefix.'/*')) {
                return true;
            }
        }

        return false;
    }
}
