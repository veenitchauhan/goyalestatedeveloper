<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRoleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['permissions' => $this->input('permissions', [])]);
    }

    public function authorize(): bool
    {
        return $this->user()->hasRole('super-admin') && $this->user()->can('roles.manage');
    }

    public function rules(): array
    {
        return ['name' => [$this->isMethod('post') ? 'required' : 'nullable', 'alpha_dash:ascii', 'max:80', Rule::unique('roles')->ignore($this->route('role'))],
            'label' => 'required|string|max:100', 'revision' => [$this->isMethod('post') ? 'nullable' : 'required', 'integer', 'min:1'],
            'permissions' => 'present|array', 'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name'), Rule::notIn(['users.manage', 'roles.manage'])]];
    }
}
