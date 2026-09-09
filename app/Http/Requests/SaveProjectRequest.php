<?php

namespace App\Http\Requests;

use App\Models\ContentEntry;
use App\Services\LocationContent;
use App\Services\ProjectContent;
use App\Services\ProjectImages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->hasFile('images') && (! $this->user()->can('media.upload') || ! $this->user()->can('media.manage'))) {
            return false;
        }

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

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->boolean('image_slots')) {
                $keptKeys = array_keys($this->input('keep_images', []));
                $uploadKeys = array_keys($this->file('images', []));
                if (array_diff([...$keptKeys, ...$uploadKeys], range(0, 4)) || array_intersect($keptKeys, $uploadKeys)) {
                    $validator->errors()->add('images', 'Use one image per card, up to five cards.');
                }
            }
            if ($this->boolean('image_selection') && count($this->input('keep_images', [])) + count($this->file('images', [])) > 5) {
                $validator->errors()->add('images', 'A project can have up to 5 images. Remove an existing image before adding another.');
            }
            if (! $this->boolean('image_selection') && count(ProjectImages::selectedIds($this->all())) > 5) {
                $validator->errors()->add('gallery', 'A project can have up to 5 images.');
            }
        }];
    }

    public function rules(): array
    {
        return ProjectContent::rules($this->route('entry'), false, $this->all()) + ['version' => ['required', 'integer', 'min:0'], 'image_selection' => 'sometimes|boolean', 'image_slots' => 'sometimes|boolean', 'keep_images' => ['sometimes', 'array', 'max:5'], 'keep_images.*' => ['integer', 'distinct', Rule::in(ProjectImages::selectedIds($this->route('entry')?->revisions()->latest('version')->first()?->payload ?? []))], 'images' => ['sometimes', 'array', 'max:5'], 'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480']];
    }
}
