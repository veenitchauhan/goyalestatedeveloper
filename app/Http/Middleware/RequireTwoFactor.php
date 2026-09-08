<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->hasRole('super-admin') && (! $request->user()->two_factor_confirmed_at || $request->session()->get('admin.setup_recovery_pending'))) {
            if ($request->isMethod('GET')) {
                $request->session()->put('admin.setup_destination', $request->getRequestUri());
            }

            return redirect()->route('admin.security')->with('status', 'Set up two-factor authentication before opening administration.');
        }

        return $next($request);
    }
}
