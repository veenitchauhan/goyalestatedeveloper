<?php

namespace App\Services;

use App\Http\Requests\StoreEnquiryRequest;
use App\Jobs\NotifyEnquiryOwner;
use App\Models\AnalyticsEvent;
use App\Models\ContentEntry;
use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnquiryCapture
{
    public function store(StoreEnquiryRequest $request): Enquiry
    {
        return DB::transaction(function () use ($request) {
            $form = EnquiryForms::enabled()[$request->validated('type')] ?? null;
            abort_unless($form, 422);
            $owner = $form['assigned_to'] ? User::find($form['assigned_to']) : null;
            if ($owner && (! $owner->is_active || (! $owner->can('leads.view') && ! $owner->can('leads.view-assigned')))) {
                $owner = null;
            }
            $project = $request->validated('content_entry_id') ? ContentEntry::where('type', 'project')->whereNotNull('published_revision_id')->lockForUpdate()->findOrFail($request->validated('content_entry_id')) : null;
            $attribution = $request->safe()->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'cta']);
            if ($request->session()->get('analytics_consent') === true) {
                $attribution['first_touch'] = $request->session()->get('analytics_first_touch', []);
                $attribution['last_touch'] = $request->session()->get('analytics_last_touch', []);
            }
            $attribution['channel'] = 'Website form';
            $attribution['landing_path'] = parse_url($request->headers->get('referer', ''), PHP_URL_PATH) ?: '/';
            $attribution['referrer_host'] = parse_url($request->headers->get('referer', ''), PHP_URL_HOST) ?: null;
            $attribution['device'] = preg_match('/Mobile|Android|iPhone/i', $request->userAgent() ?? '') ? 'Mobile' : 'Desktop / other';
            $details = array_intersect_key($request->validated('details', []), array_flip($form['fields']));
            if ($project) {
                $details['project_title'] = $project->publishedRevision->payload['title'];
            }
            $enquiry = Enquiry::create([...$request->safe()->only(['name', 'email', 'phone', 'type', 'location', 'message']), 'details' => $details, 'attribution' => $attribution, 'assigned_to' => $owner?->id, 'content_entry_id' => $project?->id, 'consented_at' => now(), 'consent_version' => 'enquiry-v1', 'status' => 'new']);
            if ($request->session()->get('analytics_consent') === true && $request->session()->has('analytics_visitor')) {
                AnalyticsEvent::create(['visitor_session' => $request->session()->get('analytics_visitor'), 'event' => 'enquiry', 'path' => '/contact', 'attribution' => $attribution['last_touch'] ?? []]);
            }
            if ($owner && $form['notify_owner']) {
                NotifyEnquiryOwner::dispatch($enquiry->id)->onConnection('database');
            }

            return $enquiry;
        });
    }
}
