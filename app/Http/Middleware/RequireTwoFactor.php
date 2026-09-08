<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->hasRole('super-admin') && ! $request->user()->two_factor_confirmed_at) {
            return redirect()->route('admin.security')->with('status', 'Set up two-factor authentication before opening administration.');
        }

        return $next($request);
    }
}
