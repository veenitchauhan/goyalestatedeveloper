<?php

namespace App\Http\Requests;

use App\Services\CampaignContent;
use Illuminate\Foundation\Http\FormRequest;

class SaveCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('campaigns.manage');
    }

    public function rules(): array
    {
        return CampaignContent::rules($this->route('entry')) + ['version' => 'required|integer|min:0'];
    }
}
