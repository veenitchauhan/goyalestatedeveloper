<?php

namespace App\Http\Requests;

use App\Services\LocationContent;
use Illuminate\Foundation\Http\FormRequest;

class SaveLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->isMethod('post') ? 'pages.create' : 'pages.edit');
    }

    public function rules(): array
    {
        return LocationContent::rules($this->route('entry'), $this->all()) + ['version' => ['required', 'integer', 'min:0']];
    }
}
