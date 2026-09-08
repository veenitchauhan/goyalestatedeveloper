<?php

namespace App\Http\Middleware;

use App\Models\AnalyticsEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackConsentedVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || ! $request->routeIs('home', 'corporate.*', 'projects.*', 'locations.*', 'careers.*', 'knowledge.*', 'campaigns.show', 'developments.*', 'contact', 'search') || $request->session()->get('analytics_consent') !== true) {
            return $response;
        }
        $touch = ['landing_path' => '/'.ltrim($request->path(), '/'), 'referrer_host' => parse_url($request->headers->get('referer', ''), PHP_URL_HOST) ?: null, 'device' => preg_match('/Mobile|Android|iPhone/i', $request->userAgent() ?? '') ? 'Mobile' : 'Desktop / other'];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $key) {
            $value = $request->query($key);
            if (is_string($value) && $value !== '') {
                $touch[$key] = mb_substr($value, 0, 255);
            }
        }
        if ($request->routeIs('campaigns.show')) {
            $touch['campaign_slug'] = $request->route('slug');
        }
        if (! $request->session()->has('analytics_first_touch')) {
            $request->session()->put('analytics_first_touch', $touch);
        }
        if (isset($touch['utm_source']) || isset($touch['utm_campaign']) || isset($touch['campaign_slug']) || ! $request->session()->has('analytics_last_touch')) {
            $request->session()->put('analytics_last_touch', $touch);
        }
        if (! $request->session()->has('analytics_visitor')) {
            $request->session()->put('analytics_visitor', (string) Str::uuid());
        }
        AnalyticsEvent::create(['visitor_session' => $request->session()->get('analytics_visitor'), 'event' => 'page_view', 'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 500), 'attribution' => $request->session()->get('analytics_last_touch')]);

        return $response;
    }
}
