<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.manage');
    }

    public function rules(): array
    {
        return ['file' => [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,mp4', 'max:20480'],
            'title' => 'required|string|max:180', 'alt' => 'nullable|string|max:255', 'caption' => 'nullable|string|max:2000', 'description' => 'nullable|string|max:5000',
            'category' => 'required|in:Projects,Machinery,Team,Careers,Company,Videos,Documents,Social',
            'location' => 'nullable|string|max:255', 'project' => 'nullable|string|max:255', 'source' => 'nullable|string|max:255', 'taken_at' => 'nullable|date|before_or_equal:today',
            'is_public' => 'required|boolean', 'sort_order' => 'required|integer|min:0|max:1000',
            'watermark.enabled' => 'required|boolean', 'watermark.position' => 'required|in:bottom-left,bottom-right,top-left,top-right,center',
            'watermark.opacity' => 'required|integer|min:10|max:100', 'watermark.size' => 'required|integer|min:1|max:5', 'watermark.padding' => 'required|integer|min:0|max:100'];
    }
}
