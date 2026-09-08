<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Enquiry;
use App\Services\CorporateContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function preferences(Request $request): View
    {
        return view('privacy-preferences', CorporateContent::layout('Privacy preferences') + ['canonical' => route('privacy.preferences'), 'consent' => $request->session()->get('analytics_consent')]);
    }

    public function consent(Request $request): RedirectResponse
    {
        $request->validate(['analytics' => 'required|boolean']);
        $accepted = $request->boolean('analytics');
        if (! $accepted) {
            if ($visitor = $request->session()->get('analytics_visitor')) {
                AnalyticsEvent::where('visitor_session', $visitor)->delete();
            }
            $request->session()->forget(['analytics_visitor', 'analytics_first_touch', 'analytics_last_touch']);
        }
        $request->session()->put('analytics_consent', $accepted);

        return redirect()->route('privacy.preferences')->with('status', 'Your privacy preferences have been saved.');
    }

    public function index(): View
    {
        $events = AnalyticsEvent::where('created_at', '>=', now()->subDays(30))->get();
        $views = $events->where('event', 'page_view');
        $leads = Enquiry::where('created_at', '>=', now()->subDays(30))->get(['status', 'type', 'attribution']);

        return view('admin.analytics', [
            'pageViews' => $views->count(), 'sessions' => $views->pluck('visitor_session')->unique()->count(), 'conversions' => $events->where('event', 'enquiry')->count(),
            'pages' => $views->countBy('path')->sortDesc()->take(20),
            'sources' => $views->countBy(fn ($event) => $event->attribution['utm_source'] ?? $event->attribution['referrer_host'] ?? 'Direct / unspecified')->sortDesc(),
            'leadStatuses' => $leads->countBy('status'), 'leadTypes' => $leads->countBy('type'),
            'campaignLeads' => $leads->countBy(fn ($lead) => $lead->attribution['last_touch']['campaign_slug'] ?? $lead->attribution['utm_campaign'] ?? 'Unspecified'),
        ]);
    }
}
