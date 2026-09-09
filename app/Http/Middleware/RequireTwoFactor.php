<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    public static function enforced(): bool
    {
        return (bool) config('fortify.require_admin_two_factor', true);
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! static::enforced()) {
            $request->session()->forget(['admin.setup_destination', 'admin.setup_recovery_pending']);

            return $next($request);
        }

        if ($request->user()->hasRole('super-admin') && (! $request->user()->two_factor_confirmed_at || $request->session()->get('admin.setup_recovery_pending'))) {
            if ($request->isMethod('GET')) {
                $request->session()->put('admin.setup_destination', $request->getRequestUri());
            }

            return redirect()->route('admin.security')->with('status', 'Set up two-factor authentication before opening administration.');
        }

        return $next($request);
    }
}
