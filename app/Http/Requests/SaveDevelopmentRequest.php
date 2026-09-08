<?php

namespace App\Http\Requests;

use App\Services\DevelopmentContent;
use Illuminate\Foundation\Http\FormRequest;

class SaveDevelopmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    public function rules(): array
    {
        return DevelopmentContent::rules($this->route('entry')) + ['version' => 'required|integer|min:0'];
    }
}
