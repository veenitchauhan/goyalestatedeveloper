<?php

namespace App\Http\Middleware;

use App\Models\SeoRedirect;
use App\Services\SeoContent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySeoRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $redirect = SeoRedirect::where('source', '/'.ltrim($request->path(), '/'))->first();
            if ($redirect) {
                $inventory = SeoContent::inventory();
                if (! $inventory->has($redirect->source) && $inventory->has($redirect->destination)) {
                    return redirect()->to(SeoContent::absolute($redirect->destination), $redirect->status);
                }
            }
        }

        return $next($request);
    }
}
