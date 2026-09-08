<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
{
    protected $redirect = '/#contact';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:254'], 'phone' => ['nullable', 'string', 'regex:/^[+0-9()\s-]{7,25}$/'], 'type' => ['required', Rule::in(['Construction', 'Infrastructure', 'Project delivery', 'Development opportunity', 'Vendor / partner', 'Career', 'General'])], 'location' => ['required', 'string', 'max:150'], 'message' => ['required', 'string', 'min:10', 'max:5000'], 'consent' => ['accepted'], 'website' => ['prohibited']];
    }
}
