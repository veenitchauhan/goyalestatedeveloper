<?php

namespace App\Http\Requests;

use App\Services\KnowledgeContent;
use Illuminate\Foundation\Http\FormRequest;

class SaveKnowledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('entry') ? 'pages.edit' : 'pages.create');
    }

    public function rules(): array
    {
        return KnowledgeContent::rules($this->route('entry')) + ['version' => 'required|integer|min:0'];
    }
}
