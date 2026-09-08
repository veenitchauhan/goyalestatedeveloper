<?php

namespace App\Http\Requests;

use App\Models\ContentEntry;
use App\Services\EnquiryForms;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
{
    protected function getRedirectUrl(): string
    {
        $type = $this->input('type');

        $parameters = is_string($type) && isset(EnquiryForms::enabled()[$type]) ? ['type' => $type] : [];
        $projectId = $this->input('content_entry_id');
        if (is_scalar($projectId) && filter_var($projectId, FILTER_VALIDATE_INT) && ContentEntry::whereKey($projectId)->where('type', 'project')->whereNotNull('published_revision_id')->exists()) {
            $parameters['project'] = $projectId;
        }
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'cta'] as $key) {
            if (is_string($this->input($key)) && mb_strlen($this->input($key)) <= 255) {
                $parameters[$key] = $this->input($key);
            }
        }

        return route('contact', $parameters);
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('type');
        if (is_string($type) && isset(EnquiryForms::ALIASES[$type])) {
            $this->merge(['type' => EnquiryForms::ALIASES[$type]]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = ['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:254'], 'phone' => ['nullable', 'string', 'regex:/^[+0-9()\s-]{7,25}$/'], 'type' => ['required', Rule::in(array_keys(EnquiryForms::enabled()))], 'location' => ['required', 'string', 'max:150'], 'message' => ['required', 'string', 'min:10', 'max:5000'], 'consent' => ['accepted'], 'website' => ['prohibited'], 'content_entry_id' => ['nullable', 'integer', Rule::exists('content_entries', 'id')->where('type', 'project')->whereNotNull('published_revision_id')], 'details' => 'sometimes|array'];
        $type = $this->input('type');
        $form = is_string($type) ? (EnquiryForms::enabled()[$type] ?? null) : null;
        foreach (EnquiryForms::FIELDS as $field => $label) {
            $rules['details.'.$field] = [in_array($field, $form['required_fields'] ?? []) ? 'required' : 'nullable', 'string', 'max:1000'];
        }
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'cta'] as $field) {
            $rules[$field] = 'nullable|string|max:255';
        }

        return $rules;
    }
}
