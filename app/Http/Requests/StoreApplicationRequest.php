<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = ['name' => 'required|string|max:150', 'email' => 'required|email|max:254', 'phone' => 'required|string|max:40', 'consent' => 'accepted', 'resume' => 'required|file|mimes:pdf|max:5120', 'website' => 'nullable|prohibited'];
        foreach (['location', 'qualification', 'experience', 'current_company', 'current_designation', 'skills', 'preferred_department', 'preferred_location', 'cover_letter'] as $key) {
            $rules[$key] = [in_array($key, ['location', 'qualification', 'experience']) ? 'required' : 'nullable', 'string', 'max:'.($key === 'cover_letter' ? 5000 : 1000)];
        }

        return $rules;
    }
}
