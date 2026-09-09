<?php

namespace App\Services;

use App\Models\ContentEntry;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CareerContent
{
    public const TYPES = ['Permanent', 'Contract', 'Internship', 'Graduate opportunity'];

    public const EMPLOYMENT = ['Full-time', 'Part-time', 'Temporary'];

    public static function rules(?ContentEntry $entry, bool $publishing = false): array
    {
        $rules = ['title' => ['required', 'string', 'max:180'], 'slug' => ['required', 'alpha_dash:ascii', 'max:150', Rule::notIn(['apply']), Rule::unique('content_entries')->where('type', 'job')->ignore($entry)], 'job_type' => ['required', Rule::in(self::TYPES)], 'employment_type' => ['required', Rule::in(self::EMPLOYMENT)], 'deadline' => ['nullable', 'date_format:Y-m-d'], 'salary_public' => ['required', 'boolean'], 'verified' => [$publishing && ! ContentPublisher::immediate() ? 'accepted' : 'nullable', 'boolean']];
        foreach (['department', 'location', 'experience', 'salary'] as $key) {
            $rules[$key] = [in_array($key, ['department', 'location']) ? 'required' : 'nullable', 'string', 'max:255'];
        }
        foreach (['description', 'responsibilities', 'requirements', 'skills', 'education', 'source_note'] as $key) {
            $rules[$key] = [$publishing && in_array($key, ContentPublisher::immediate() ? ['description', 'responsibilities', 'requirements'] : ['description', 'responsibilities', 'requirements', 'source_note']) ? 'required' : 'nullable', 'string', 'max:15000'];
        }

        $rules['address_country'] = ['nullable', 'string', 'regex:/^[A-Z]{2}$/'];
        foreach (['address_locality', 'address_region', 'postal_code', 'street_address'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public static function openings(): Collection
    {
        return ContentEntry::publishedItems('job')->filter(fn (array $job) => empty($job['deadline']) || $job['deadline'] >= now()->toDateString());
    }

    public static function detail(ContentEntry $entry, array $payload, bool $preview = false): View
    {
        return view('careers.show', CorporateContent::layout($payload['title']) + compact('entry', 'payload', 'preview') + ['canonical' => route('careers.show', $entry->slug)]);
    }
}
