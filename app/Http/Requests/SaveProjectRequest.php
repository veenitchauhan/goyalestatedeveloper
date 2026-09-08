<?php

namespace App\Http\Requests;

use App\Services\LocationContent;
use App\Services\ProjectContent;
use Illuminate\Foundation\Http\FormRequest;

class SaveProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('entry') ? $this->user()->can('update', $this->route('entry')) : $this->user()->can('projects.edit');
    }

    protected function prepareForValidation(): void
    {
        $locationId = $this->input('location_entry_id');
        if (is_scalar($locationId) && ctype_digit((string) $locationId)) {
            $locations = LocationContent::active();
            $city = $locations->get($this->input('location_entry_id'));
            if ($city && $city['level'] === 'city') {
                $region = $locations->get($city['parent_id']);
                $state = $region ? $locations->get($region['parent_id']) : null;
                $country = $state ? $locations->get($state['parent_id']) : null;
                $this->merge(['city' => $city['title'], 'state' => $state['title'] ?? '', 'country' => $country['title'] ?? '']);
            }
        }
    }

    public function rules(): array
    {
        return ProjectContent::rules($this->route('entry'), false, $this->all()) + ['version' => ['required', 'integer', 'min:0']];
    }
}
