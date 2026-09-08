<?php

namespace App\Http\Requests;

use App\Models\ContentEntry;
use App\Models\Homepage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class SaveHomepageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pages.edit');
    }

    public function rules(): array
    {
        $payload = Homepage::editableContent(ContentEntry::where('type', 'homepage')->firstOrFail()->revisions()->latest('version')->firstOrFail()->payload);
        $rules = ['version' => ['required', 'integer', 'min:1']];
        foreach (Arr::dot($payload) as $key => $value) {
            if (str_ends_with($key, '.id') || str_ends_with($key, '_id') || str_starts_with($key, 'statistics.')) {
                continue;
            }
            $rules['content.'.$key] = is_bool($value) ? ['required', 'boolean'] : (is_int($value) ? ['required', 'integer', 'min:0', 'max:1000'] : ['nullable', 'string', 'max:5000']);
        }
        foreach (['hero.line_one', 'hero.line_two', 'hero.line_three', 'seo.title', 'seo.description'] as $key) {
            $rules['content.'.$key] = ['required', 'string', 'max:500'];
        }
        $rules['content.contact.phone'] = ['nullable', 'regex:/^\+?[0-9 ()-]{7,25}$/'];
        $rules['content.contact.whatsapp'] = ['nullable', 'regex:/^[0-9]{7,15}$/'];
        $rules['content.contact.email'] = ['nullable', 'email', 'max:254'];
        foreach (['hero_media_id' => 'image/%', 'hero_video_id' => 'video/mp4'] as $field => $mime) {
            $rules[$field] = ['nullable', 'integer', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', $mime))];
        }
        foreach (['primary_cta_id', 'secondary_cta_id'] as $field) {
            $rules[$field] = ['nullable', 'integer', Rule::exists('content_entries', 'id')->where('type', 'cta')->whereNotNull('published_revision_id')];
        }
        $rules['statistics_mode'] = ['sometimes', Rule::in(['all', 'selected', 'hidden'])];
        $rules['statistic_ids'] = ['sometimes', 'array', 'max:20'];
        $rules['statistic_ids.*'] = ['integer', 'distinct', Rule::exists('content_entries', 'id')->where('type', 'statistic')->whereNotNull('published_revision_id')];
        $rules['section_assets'] = ['sometimes', 'array:'.implode(',', array_keys($payload['sections']))];
        foreach ($payload['sections'] as $index => $section) {
            $rules['section_assets.'.$index] = ['sometimes', 'array:media_id,video_id,cta_id'];
            foreach (['media_id' => 'hero_media_id', 'video_id' => 'hero_video_id', 'cta_id' => 'primary_cta_id'] as $field => $reference) {
                $rules['section_assets.'.$index.'.'.$field] = $rules[$reference];
            }
        }

        return $rules;
    }
}
