<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Services\CorporateContent;
use App\Services\EnquiryCapture;
use App\Services\EnquiryForms;
use App\Services\ProjectContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['type' => ['nullable', Rule::in(array_keys(EnquiryForms::enabled()))], 'project' => 'nullable|integer', 'cta' => 'nullable|string|max:255', 'utm_source' => 'nullable|string|max:255', 'utm_medium' => 'nullable|string|max:255', 'utm_campaign' => 'nullable|string|max:255', 'utm_content' => 'nullable|string|max:255', 'utm_term' => 'nullable|string|max:255']);
        $forms = EnquiryForms::enabled();
        $type = $filters['type'] ?? array_key_first($forms);
        $form = $forms[$type] ?? null;
        $project = ! empty($filters['project']) ? ProjectContent::items()->firstWhere('id', (int) $filters['project']) : null;
        abort_if(! empty($filters['project']) && ! $project, 404);

        return view('contact', CorporateContent::layout('Contact our team') + compact('forms', 'type', 'form', 'project', 'filters') + ['canonical' => route('contact')]);
    }

    public function store(StoreEnquiryRequest $request, EnquiryCapture $capture): RedirectResponse
    {
        $capture->store($request);

        return redirect()->route('contact')->with('enquiry_sent', true);
    }
}
