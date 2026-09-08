<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    public function rules(): array
    {
        $rules = ['version' => 'required|integer|min:1', 'settings.company.name' => ['required', Rule::in([config('app.name')])], 'settings.company.tagline' => 'required|string|max:200', 'settings.company.legal_information' => 'nullable|string|max:3000'];
        foreach (['phone', 'whatsapp', 'email', 'address', 'hr_email', 'sales_email', 'office_hours', 'map_url'] as $field) {
            $rules['settings.contact.'.$field] = ['nullable', 'string', 'max:1000'];
        }
        $rules['settings.contact.phone'] = ['nullable', 'regex:/^\+?[0-9 ()-]{7,25}$/'];
        $rules['settings.contact.whatsapp'] = ['nullable', 'regex:/^[0-9]{7,15}$/'];
        foreach (['email', 'hr_email', 'sales_email'] as $field) {
            $rules['settings.contact.'.$field] = 'nullable|email|max:254';
        }
        $rules['settings.contact.map_url'] = 'nullable|url:http,https|max:1000';
        foreach (['logo_id', 'favicon_id', 'og_image_id'] as $field) {
            $rules['settings.branding.'.$field] = ['nullable', 'integer', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', 'image/%'))];
        }
        foreach (['instagram', 'linkedin', 'youtube', 'facebook'] as $field) {
            $rules['settings.social.'.$field] = 'nullable|url:http,https|max:1000';
        }
        foreach (['privacy_url', 'terms_url', 'cookies_url', 'disclaimer_url'] as $field) {
            $rules['settings.footer.'.$field] = ['nullable', 'string', 'max:500', 'regex:~^/(?!/)[a-zA-Z0-9/_#?.=&%+\-]*$~'];
        }
        foreach (['title', 'description', 'analytics_id', 'tag_manager_id', 'meta_pixel_id', 'search_console_verification'] as $field) {
            $rules['settings.seo.'.$field] = 'nullable|string|max:500';
        }
        $rules['settings.seo.robots'] = ['required', Rule::in(['noindex,nofollow', 'index,follow'])];
        $rules['settings.watermark.enabled'] = 'required|boolean';
        $rules['settings.watermark.position'] = 'required|in:bottom-left,bottom-right,top-left,top-right,center';
        $rules['settings.watermark.opacity'] = 'required|integer|min:10|max:100';
        $rules['settings.watermark.size'] = 'required|integer|min:1|max:5';
        $rules['settings.watermark.padding'] = 'required|integer|min:0|max:100';

        return $rules;
    }
}
