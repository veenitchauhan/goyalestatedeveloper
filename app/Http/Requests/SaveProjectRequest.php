<?php

namespace App\Http\Requests;

use App\Services\ProjectContent;
use Illuminate\Foundation\Http\FormRequest;

class SaveProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('entry') ? $this->user()->can('update', $this->route('entry')) : $this->user()->can('projects.edit');
    }

    public function rules(): array
    {
        return ProjectContent::rules($this->route('entry'), false, $this->all()) + ['version' => ['required', 'integer', 'min:0']];
    }
}
