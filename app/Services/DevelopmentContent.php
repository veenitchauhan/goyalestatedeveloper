<?php

namespace App\Services;

use App\Models\ContentEntry;
use App\Models\DevelopmentSpace;
use App\Models\Media;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DevelopmentContent
{
    public static function enabled(): bool
    {
        return (bool) (SiteSetting::where('key', 'future_developments')->first()?->data['enabled'] ?? false);
    }

    public static function rules(?ContentEntry $entry, bool $publishing = false): array
    {
        return ['title' => 'required|string|max:180', 'slug' => ['required', 'alpha_dash:ascii', 'max:150', Rule::unique('content_entries')->where('type', 'development')->ignore($entry)], 'category' => ['required', Rule::in(['Residential', 'Commercial', 'Mixed Use', 'Plotted Development', 'Land Development', 'Joint Development', 'Other'])], 'location' => 'required|string|max:255', 'summary' => 'nullable|string|max:1000', 'body' => [$publishing ? 'required' : 'nullable', 'string', 'max:20000'], 'amenities' => 'nullable|string|max:10000', 'progress' => 'required|integer|min:0|max:100', 'source_note' => [$publishing ? 'required' : 'nullable', 'string', 'max:5000'], 'verified' => [$publishing ? 'accepted' : 'nullable', 'boolean'], 'seo_title' => 'nullable|string|max:180', 'seo_description' => 'nullable|string|max:300', 'gallery_ids' => 'sometimes|array|max:30', 'gallery_ids.*' => ['integer', 'distinct', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', 'image/%')->orWhere('mime', 'video/mp4'))], 'masterplan_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', 'image/%'))], 'brochure_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where('mime', 'application/pdf')]];
    }

    public static function items(): Collection
    {
        return self::enabled() ? ContentEntry::publishedItems('development')->map(fn ($item) => [...$item, 'type' => 'development', 'url' => route('developments.show', $item['slug'])]) : collect();
    }

    public static function spaces(ContentEntry $entry): Collection
    {
        $all = DevelopmentSpace::where('content_entry_id', $entry->id)->get()->keyBy('id');

        return $all->filter(function ($space) use ($all) {
            for ($depth = 0; $depth < 3; $depth++) {
                if (! $space->is_public) {
                    return false;
                }
                if (! $space->parent_id) {
                    return $space->kind === 'tower';
                }
                $space = $all->get($space->parent_id);
                if (! $space) {
                    return false;
                }
            }

            return false;
        });
    }

    public static function detail(ContentEntry $entry, array $payload, bool $preview = false): View
    {
        $spaces = self::spaces($entry);
        $ids = [$payload['masterplan_id'] ?? null, $payload['brochure_id'] ?? null, ...($payload['gallery_ids'] ?? []), ...$spaces->pluck('details.plan_id')->filter()->all()];
        $media = Media::whereIn('id', array_filter($ids))->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->get()->keyBy('id');

        return view('developments.show', CorporateContent::layout(($payload['seo_title'] ?? '') ?: $payload['title'], ($payload['seo_description'] ?? '') ?: ($payload['summary'] ?? '')) + compact('entry', 'payload', 'preview', 'spaces', 'media') + ['canonical' => route('developments.show', $entry->slug)]);
    }
}
