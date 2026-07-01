<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks /visitor/* routes to users whose portal_type is `visitor`.
 *
 * Admins and authors are bounced back to /dashboard (their own portal).
 * Guests get punted to /login so the visitor portal acts like a logged-in
 * reader area — same idea as the admin sidebar but for frontend readers.
 */
class EnsureVisitorPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->portal_type !== 'visitor') {
            // Staff land on their own dashboard rather than seeing a permission
            // error — keeps the role switch friction-free during development.
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
