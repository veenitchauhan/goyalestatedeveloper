<?php

namespace App\Http\Requests;

use App\Models\ContentEntry;
use App\Services\LocationContent;
use App\Services\ProjectContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class SaveProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('entry') ? $this->user()->can('update', $this->route('entry')) : $this->user()->can('projects.edit');
    }

    protected function prepareForValidation(): void
    {
        $entry = $this->route('entry');
        $previous = $entry?->revisions()->latest('version')->first()?->payload ?? [];
        if (! $this->has('slug')) {
            $slug = $entry?->slug;
            if (! $slug) {
                $base = Str::limit(Str::slug(is_string($this->input('title')) ? $this->input('title') : ''), 130, '') ?: 'project';
                $slug = $base;
                $suffix = 2;
                while (ContentEntry::where('type', 'project')->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$suffix++;
                }
            }
            $this->merge(['slug' => $slug]);
        }
        if (! $this->has('project_type')) {
            $this->merge(['project_type' => $previous['project_type'] ?? $this->input('sector')]);
        }
        if (! $this->has('progress_date')) {
            $progressChanged = ! $entry || (string) $this->input('progress') !== (string) ($previous['progress'] ?? '') || $this->input('stage') !== ($previous['stage'] ?? null);
            $this->merge(['progress_date' => $progressChanged ? now()->toDateString() : ($previous['progress_date'] ?? now()->toDateString())]);
        }
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
