<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $scriptSource = $request->is('admin', 'admin/*', 'login', 'user/*', 'two-factor-challenge', 'projects/*') ? "'self'" : "'none'";
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src {$scriptSource}; style-src 'self'; img-src 'self' data:; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
        if (! app()->isProduction()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
