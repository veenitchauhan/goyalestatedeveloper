<?php

namespace App\Services;

use App\Models\ContentEntry;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationContent
{
    public const LEVELS = ['country', 'state', 'region', 'city'];

    public static function rules(?ContentEntry $entry, array $payload, bool $publishing = false): array
    {
        $level = is_string($payload['level'] ?? null) ? $payload['level'] : '';
        $rank = array_search($level, self::LEVELS, true);

        return [
            'title' => ['required', 'string', 'max:180'], 'slug' => ['required', 'alpha_dash:ascii', 'max:150', Rule::unique('content_entries')->where('type', 'location')->ignore($entry)],
            'level' => ['required', Rule::in(self::LEVELS)], 'presence' => ['required', Rule::in(['current', 'future'])],
            'parent_id' => ['bail', $level === 'country' ? 'prohibited' : 'required', 'nullable', 'integer', function (string $attribute, mixed $value, \Closure $fail) use ($rank, $entry): void {
                $parent = ContentEntry::where('type', 'location')->whereNotNull('published_revision_id')->with('publishedRevision')->find($value);
                if (! $parent || $parent->id === $entry?->id || $rank === false || $rank < 1 || ($parent->publishedRevision->payload['level'] ?? null) !== self::LEVELS[$rank - 1]) {
                    $fail('Choose a published parent at the preceding level: country → state → region → city.');
                }
            }],
            'summary' => [$publishing ? 'required' : 'nullable', 'string', 'max:600'],
            'body' => [$publishing ? 'required' : 'nullable', 'string', ...($publishing ? ['min:80'] : []), 'max:15000'],
            'industries' => ['nullable', 'string', 'max:3000'], 'areas_served' => ['nullable', 'string', 'max:3000'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'source_note' => [$publishing ? 'required' : 'nullable', 'string', 'max:2000'], 'verified' => [$publishing ? 'accepted' : 'nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:180'], 'seo_description' => ['nullable', 'string', 'max:500'],
            'service_ids' => ['sometimes', 'array', 'max:30'], 'service_ids.*' => ['integer', 'distinct', Rule::exists('content_entries', 'id')->where('type', 'service')->whereNotNull('published_revision_id')],
        ];
    }

    public static function published(): Collection
    {
        return ContentEntry::where('type', 'location')->whereNotNull('published_revision_id')->with('publishedRevision')->get()->map(fn (ContentEntry $entry) => ['id' => $entry->id, ...$entry->publishedRevision->payload, 'url' => route('locations.show', $entry->slug)])->keyBy('id');
    }

    public static function active(): Collection
    {
        $all = self::published();

        return $all->filter(function (array $item) use ($all): bool {
            $rank = array_search($item['level'], self::LEVELS, true);
            if ($rank === false) {
                return false;
            }
            for ($i = $rank; $i >= 0; $i--) {
                if (($item['presence'] ?? null) !== 'current' || ! ($item['verified'] ?? false) || $item['level'] !== self::LEVELS[$i]) {
                    return false;
                }
                if ($i > 0) {
                    $item = $all->get($item['parent_id'] ?? null);
                    if (! $item) {
                        return false;
                    }
                }
            }

            return true;
        })->sortBy('title');
    }

    public static function detail(ContentEntry $entry, array $payload, bool $preview = false): View
    {
        $active = self::active();
        $children = $active->where('parent_id', $entry->id);
        $ids = collect([$entry->id]);
        for ($i = 0; $i < 3; $i++) {
            $ids = $ids->merge($active->whereIn('parent_id', $ids->all())->pluck('id'))->unique();
        }
        $projects = ProjectContent::items()->whereIn('location_entry_id', $ids->all());
        $services = CorporateContent::items('service')->whereIn('entry_id', $payload['service_ids'] ?? []);
        $ancestors = collect();
        $parent = $active->get($payload['parent_id'] ?? null);
        for ($i = 0; $parent && $i < 3; $i++) {
            $ancestors->prepend($parent);
            $parent = $active->get($parent['parent_id'] ?? null);
        }

        return view('locations.show', CorporateContent::layout(($payload['seo_title'] ?? '') ?: $payload['title'], $payload['seo_description'] ?? '') + compact('entry', 'payload', 'children', 'projects', 'services', 'ancestors', 'preview') + ['canonical' => route('locations.show', $entry->slug)]);
    }
}
