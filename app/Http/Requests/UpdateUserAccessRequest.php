<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('users.manage');
    }

    public function rules(): array
    {
        return ['role_id' => ['required', 'integer', 'exists:roles,id'], 'is_active' => ['required', 'boolean']];
    }
}
