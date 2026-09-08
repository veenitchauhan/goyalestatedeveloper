<?php

namespace App\Services;

use App\Models\ContentEntry;
use App\Models\Media;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectContent
{
    public const STATUSES = ['Upcoming', 'Ongoing', 'Completed', 'On Hold'];

    public const SECTORS = ['Buildings', 'Infrastructure', 'Commercial', 'Residential', 'Industrial', 'Institutional', 'Other'];

    public const STAGES = ['Planning', 'Pre-Construction', 'Site Preparation', 'Foundation', 'Basement', 'Structure', 'Civil Works', 'MEP', 'Façade', 'Finishing', 'Testing', 'Handover', 'Completed', 'On Hold'];

    public const GALLERY_CATEGORIES = ['Site Progress', 'Construction', 'Machinery', 'Team', 'Engineering', 'Drone', 'Before/After', 'Completed', 'Time-lapse', 'Other'];

    public static function rules(?ContentEntry $entry = null, bool $publishing = false, array $payload = []): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:150', Rule::unique('content_entries')->where('type', 'project')->ignore($entry)],
            'status' => ['required', Rule::in(self::STATUSES)], 'sector' => ['required', Rule::in(self::SECTORS)],
            'stage' => ['required', Rule::in(self::STAGES)], 'progress' => ['required', 'integer', 'between:0,100'],
            'featured' => ['required', 'boolean'], 'client_approved' => ['required', 'boolean'], 'value_approved' => ['required', 'boolean'],
            'verified' => [$publishing ? 'accepted' : 'nullable', 'boolean'],
        ];
        foreach (['project_type', 'location', 'city', 'state', 'country', 'client', 'project_area', 'built_up_area', 'project_value', 'manager', 'seo_title'] as $field) {
            $rules[$field] = [in_array($field, ['city', 'project_type']) ? 'required' : 'nullable', 'string', 'max:255'];
        }
        foreach (['description', 'scope', 'engineering', 'construction', 'management', 'technical_highlights', 'quality_safety', 'outcome', 'source_note', 'seo_description'] as $field) {
            $rules[$field] = [$publishing && in_array($field, ['description', 'scope', 'source_note']) ? 'required' : 'nullable', 'string', 'max:15000'];
        }
        foreach (['start_date', 'expected_completion', 'actual_completion', 'progress_date'] as $field) {
            $rules[$field] = [$field === 'progress_date' ? 'required' : 'nullable', 'date_format:Y-m-d'];
        }
        $rules['progress_date'][] = 'before_or_equal:today';
        $rules['actual_completion'][] = 'before_or_equal:today';
        if ($payload['start_date'] ?? null) {
            $rules['expected_completion'][] = 'after_or_equal:start_date';
            $rules['actual_completion'][] = 'after_or_equal:start_date';
        }
        if (($payload['status'] ?? null) === 'Completed') {
            $rules['progress'][] = 'in:100';
        }
        $image = fn () => Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($q) => $q->where('mime', 'like', 'image/%'));
        $rules['panorama_media_id'] = ['bail', 'nullable', 'integer', $image(), function (string $attribute, mixed $value, \Closure $fail): void {
            $media = Media::find($value);
            $path = $media ? Storage::disk('local')->path($media->web_path ?: $media->original_path) : '';
            $size = is_file($path) ? @getimagesize($path) : false;
            if (! $size || abs($size[0] / $size[1] - 2) > 0.02) {
                $fail('Select a full 360° equirectangular image with a 2:1 width-to-height ratio.');
            }
        }];
        $rules['panorama_caption'] = ['nullable', 'string', 'max:500'];
        $rules['cover_media_id'] = ['nullable', 'integer', $image()];
        foreach (['equipment_ids', 'related_ids', 'document_ids'] as $field) {
            $rules[$field] = ['sometimes', 'array', 'max:30'];
        }
        $rules['equipment_ids.*'] = ['integer', 'distinct', Rule::exists('content_entries', 'id')->where('type', 'equipment')->whereNotNull('published_revision_id')];
        $rules['related_ids.*'] = ['integer', 'distinct', Rule::exists('content_entries', 'id')->where('type', 'project')->whereNotNull('published_revision_id')->where('id', '!=', $entry?->id ?? 0)];
        $rules['document_ids.*'] = ['integer', 'distinct', Rule::exists('media', 'id')->where('mime', 'application/pdf')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')];
        foreach (['timeline', 'gallery', 'faqs'] as $field) {
            $rules[$field] = ['sometimes', 'array', 'max:60'];
        }
        $rules += [
            'timeline.*.name' => ['required', 'string', 'max:150'], 'timeline.*.progress' => ['required', 'integer', 'between:0,100'],
            'timeline.*.date' => ['nullable', 'date_format:Y-m-d'], 'timeline.*.description' => ['nullable', 'string', 'max:2000'],
            'timeline.*.milestone' => ['nullable', 'string', 'max:255'], 'timeline.*.media_id' => ['nullable', 'integer', $image()],
            'gallery.*.media_id' => ['required', 'integer', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($q) => $q->where('mime', 'like', 'image/%')->orWhere('mime', 'video/mp4'))],
            'gallery.*.category' => ['required', Rule::in(self::GALLERY_CATEGORIES)], 'gallery.*.caption' => ['nullable', 'string', 'max:500'],
            'gallery.*.date' => ['nullable', 'date_format:Y-m-d'], 'gallery.*.location' => ['nullable', 'string', 'max:255'],
            'gallery.*.alt' => ['nullable', 'string', 'max:255'], 'gallery.*.description' => ['nullable', 'string', 'max:2000'],
            'gallery.*.stage' => ['nullable', 'string', 'max:150'], 'gallery.*.visible' => ['required', 'boolean'],
            'gallery.*.equipment_id' => ['nullable', 'integer', Rule::exists('content_entries', 'id')->where('type', 'equipment')->whereNotNull('published_revision_id')],
            'faqs.*.question' => ['required', 'string', 'max:500'], 'faqs.*.answer' => ['required', 'string', 'max:4000'],
            'before_media_id' => ['nullable', 'integer', $image()], 'after_media_id' => ['nullable', 'integer', $image()],
        ];

        return $rules;
    }

    public static function items(): Collection
    {
        return ContentEntry::where('type', 'project')->whereNotNull('published_revision_id')->with('publishedRevision')->latest('published_at')->get()
            ->map(fn (ContentEntry $entry) => ['id' => $entry->id, ...$entry->publishedRevision->payload, 'url' => route('projects.show', $entry->slug)])
            ->sortByDesc('featured');
    }

    public static function detail(ContentEntry $entry, array $payload, bool $preview = false): View
    {
        $ids = [$payload['panorama_media_id'] ?? null, $payload['cover_media_id'] ?? null, $payload['before_media_id'] ?? null, $payload['after_media_id'] ?? null, ...($payload['document_ids'] ?? []), ...array_column($payload['gallery'] ?? [], 'media_id'), ...array_column($payload['timeline'] ?? [], 'media_id')];
        $media = Media::whereIn('id', array_filter($ids))->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->get()->keyBy('id');
        $equipment = ContentEntry::publishedItems('equipment')->whereIn('id', $payload['equipment_ids'] ?? []);
        $related = self::items()->whereIn('id', $payload['related_ids'] ?? []);

        return view('projects.show', CorporateContent::layout(($payload['seo_title'] ?? null) ?: $payload['title'], $payload['seo_description'] ?? '') + compact('entry', 'payload', 'media', 'equipment', 'related', 'preview') + ['canonical' => route('projects.show', $entry->slug)]);
    }
}
