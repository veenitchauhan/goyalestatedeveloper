<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AuditRecorder;
use App\Services\EnquiryForms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EnquiryFormController extends Controller
{
    public function edit(): View
    {
        return view('admin.enquiry-forms', ['settings' => EnquiryForms::current(), 'users' => User::where('is_active', true)->get()->filter(fn ($user) => $user->can('leads.view') || $user->can('leads.view-assigned'))]);
    }

    public function update(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $rules = ['version' => 'required|integer|min:0', 'forms' => 'required|array'];
        foreach (EnquiryForms::TYPES as $type) {
            foreach (['enabled' => 'required|boolean', 'title' => 'required|string|max:180', 'intro' => 'nullable|string|max:1000', 'notify_owner' => 'required|boolean', 'fields' => 'sometimes|array', 'required_fields' => 'sometimes|array'] as $field => $rule) {
                $rules['forms.'.$type.'.'.$field] = $rule;
            }
            $rules['forms.'.$type.'.assigned_to'] = ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)];
            $rules['forms.'.$type.'.fields.*'] = ['string', Rule::in(array_keys(EnquiryForms::FIELDS))];
            $rules['forms.'.$type.'.required_fields.*'] = ['string', Rule::in(array_keys(EnquiryForms::FIELDS))];
        }
        $data = $request->validate($rules);
        $forms = [];
        foreach (EnquiryForms::TYPES as $type) {
            $form = $data['forms'][$type];
            $form['fields'] ??= [];
            $form['required_fields'] ??= [];
            if (array_diff($form['required_fields'], $form['fields'])) {
                throw ValidationException::withMessages(['forms.'.$type.'.required_fields' => 'Required fields must also be enabled.']);
            }
            $owner = ! empty($form['assigned_to']) ? User::findOrFail($form['assigned_to']) : null;
            if (($owner && ! $owner->can('leads.view') && ! $owner->can('leads.view-assigned')) || ($form['notify_owner'] && ! $owner)) {
                throw ValidationException::withMessages(['forms.'.$type.'.assigned_to' => 'Choose an eligible lead owner before enabling notifications.']);
            }
            $forms[$type] = $form;
        }
        DB::transaction(function () use ($data, $forms, $audit) {
            SiteSetting::firstOrCreate(['key' => 'enquiry_forms'], ['data' => ['version' => 0, 'forms' => []]]);
            $setting = SiteSetting::where('key', 'enquiry_forms')->lockForUpdate()->firstOrFail();
            if (($setting->data['version'] ?? 0) !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'Form settings have changed. Reload before saving.']);
            }
            $setting->update(['data' => ['version' => (int) $data['version'] + 1, 'forms' => $forms]]);
            $audit->record('enquiry.forms_updated', $setting);
        });

        return back()->with('status', 'Enquiry forms and routing updated.');
    }
}
