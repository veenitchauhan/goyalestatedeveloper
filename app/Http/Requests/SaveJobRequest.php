<?php

namespace App\Http\Requests;

use App\Services\CareerContent;
use Illuminate\Foundation\Http\FormRequest;

class SaveJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('jobs.edit');
    }

    public function rules(): array
    {
        return CareerContent::rules($this->route('entry')) + ['version' => 'required|integer|min:0'];
    }
}
