<?php

namespace App\Services;

use App\Models\ContentEntry;
use App\Models\Media;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CampaignContent
{
    public static function rules(?ContentEntry $entry, bool $publishing = false): array
    {
        return [
            'title' => 'required|string|max:180',
            'slug' => ['required', 'alpha_dash:ascii', 'max:150', Rule::unique('content_entries')->where('type', 'campaign')->ignore($entry)],
            'headline' => 'required|string|max:180', 'subheadline' => 'nullable|string|max:1000',
            'body' => [$publishing ? 'required' : 'nullable', 'string', 'max:20000'],
            'source_note' => [$publishing ? 'required' : 'nullable', 'string', 'max:5000'],
            'verified' => [$publishing ? 'accepted' : 'nullable', 'boolean'],
            'seo_title' => 'nullable|string|max:180', 'seo_description' => 'nullable|string|max:300',
            'form_type' => ['required', Rule::in(array_keys(EnquiryForms::enabled()))],
            'cta_label' => 'required|string|max:100',
            'related_ids' => 'sometimes|array|max:30',
            'related_ids.*' => ['integer', 'distinct', Rule::in(KnowledgeContent::relatedOptions()->pluck('id')->all())],
            'statistic_ids' => 'sometimes|array|max:12',
            'statistic_ids.*' => ['integer', 'distinct', Rule::exists('content_entries', 'id')->where('type', 'statistic')->whereNotNull('published_revision_id')],
            'cover_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', 'image/%')->orWhere('mime', 'video/mp4'))],
        ];
    }

    public static function detail(ContentEntry $entry, array $payload, bool $preview = false): View
    {
        $media = Media::whereKey($payload['cover_media_id'] ?? null)->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->first();

        return view('campaigns.show', CorporateContent::layout(($payload['seo_title'] ?? '') ?: $payload['headline'], ($payload['seo_description'] ?? '') ?: ($payload['subheadline'] ?? '')) + compact('entry', 'payload', 'preview', 'media') + [
            'canonical' => route('campaigns.show', $entry->slug),
            'relatedItems' => KnowledgeContent::relatedOptions()->whereIn('id', $payload['related_ids'] ?? []),
            'statistics' => ContentEntry::publishedItems('statistic')->whereIn('id', $payload['statistic_ids'] ?? []),
        ]);
    }
}
