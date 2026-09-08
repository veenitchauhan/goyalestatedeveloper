<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->isMethod('post') ? 'pages.create' : 'pages.edit');
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:0'], 'type' => ['required', Rule::in(['page', 'block', 'statistic', 'menu', 'cta'])],
            'slug' => ['required', 'alpha_dash:ascii', 'max:150', Rule::unique('content_entries')->where('type', $this->input('type'))->ignore($this->route('entry'))],
            'title' => ['required', 'string', 'max:180'], 'body' => ['nullable', 'string', 'max:30000'],
            'description' => ['nullable', 'string', 'max:500'], 'value' => ['nullable', 'string', 'max:80', 'required_if:type,statistic'],
            'url' => ['nullable', 'string', 'max:500', 'regex:~^/(?!/)[a-zA-Z0-9/_#?.=&%+\-]*$~', 'required_if:type,menu,cta'],
            'placement' => ['required', Rule::in(['header', 'footer', 'both'])], 'order' => ['required', 'integer', 'min:0', 'max:1000'],
            'document_ids' => ['array', 'max:20'], 'document_ids.*' => ['integer', Rule::exists('media', 'id')->where('mime', 'application/pdf')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')],
            'cta_ids' => ['array', 'max:10'], 'cta_ids.*' => ['integer', Rule::exists('content_entries', 'id')->where('type', 'cta')],
            'block_ids' => ['array', 'max:20'], 'block_ids.*' => ['integer', Rule::exists('content_entries', 'id')->where('type', 'block')],
            'statistic_ids' => ['array', 'max:20'], 'statistic_ids.*' => ['integer', Rule::exists('content_entries', 'id')->where('type', 'statistic')]];
    }
}
