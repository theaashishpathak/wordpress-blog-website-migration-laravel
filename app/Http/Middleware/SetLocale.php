<?php

namespace App\Http\Middleware;

use App\Support\LocaleResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the active locale for this request and bind it to App::setLocale().
 *
 * Resolution order:
 *   1. {locale} URL segment (route parameter named "locale")
 *   2. Session-stored locale (admin language switcher persistence)
 *   3. Authenticated user's `locale` column
 *   4. Browser Accept-Language header (first acceptable active locale)
 *   5. Configured default language
 *
 * Always sets App::setLocale(...) so all translation helpers and Carbon
 * formatting use the resolved code. Active Language model is bound on the
 * shared LocaleResolver singleton so downstream code (Livewire components,
 * frontend controllers) can access it via `app(LocaleResolver::class)->current()`.
 */
class SetLocale
{
    public function __construct(private LocaleResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $resolved = $this->resolver->resolve($this->pickCandidate($request));

        if ($resolved === null) {
            // Languages table empty (fresh install). Fall back to app config
            // without breaking the request.
            App::setLocale(config('app.locale', 'en'));

            return $next($request);
        }

        App::setLocale($resolved->code);
        $this->resolver->setCurrent($resolved);

        if ($resolved->locale_php !== null && $resolved->locale_php !== '') {
            // Carbon / number formatter localization hook.
            \Illuminate\Support\Carbon::setLocale($resolved->locale_php);
        }

        // Persist for session-based admin/author UI choices.
        $request->session()?->put('locale', $resolved->code);

        return $next($request);
    }

    /**
     * Walk the candidate sources and return the first that yields a non-empty code.
     */
    private function pickCandidate(Request $request): ?string
    {
        $candidates = [
            $request->route('locale'),
            $request->session()?->get('locale'),
            $request->user()?->locale,
            $this->preferredBrowserCode($request),
        ];

        foreach ($candidates as $code) {
            if (is_string($code) && $code !== '' && $this->resolver->isValidCode($code)) {
                return $code;
            }
        }

        return null;
    }

    private function preferredBrowserCode(Request $request): ?string
    {
        $codes = array_keys($this->resolver->activeMap());

        if ($codes === []) {
            return null;
        }

        $preferred = $request->getPreferredLanguage($codes);

        return is_string($preferred) && $preferred !== '' ? $preferred : null;
    }
}
