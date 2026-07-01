<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Response;

class RobotsController
{
    public function __invoke(): Response
    {
        $sitemap = url('/sitemap.xml');

        $body = "User-agent: *\n";
        $body .= "Disallow: /admin\n";
        $body .= "Disallow: /dashboard\n";
        $body .= "Disallow: /login\n";
        $body .= "Disallow: /register\n";
        $body .= "Allow: /\n\n";
        $body .= "Sitemap: {$sitemap}\n";

        return new Response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
