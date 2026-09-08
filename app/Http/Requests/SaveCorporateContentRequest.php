<?php

namespace App\Http\Requests;

use App\Services\CorporateContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SaveCorporateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->isMethod('post') ? 'pages.create' : 'pages.edit');
    }

    public function rules(): array
    {
        $entry = $this->route('entry');
        $type = $entry?->type ?? $this->input('type', 'company_page');
        if (! is_string($type) || ! CorporateContent::supports($type)) {
            throw ValidationException::withMessages(['type' => 'Select a supported company record type.']);
        }

        return CorporateContent::rules($type, $entry) + ['version' => ['required', 'integer', 'min:0']];
    }
}
