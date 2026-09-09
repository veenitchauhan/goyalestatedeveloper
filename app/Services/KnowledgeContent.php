<?php

namespace App\Services;

use App\Models\ContentEntry;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KnowledgeContent
{
    public const TYPES = ['blog' => 'Blog', 'knowledge' => 'Knowledge Bank', 'faq' => 'FAQs'];

    public static function supports(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    public static function rules(?ContentEntry $entry, bool $publishing = false): array
    {
        $rules = [
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'title' => 'required|string|max:180',
            'slug' => ['required', 'alpha_dash:ascii', 'max:150', Rule::unique('content_entries')->where('type', $entry?->type ?? request('type'))->ignore($entry)],
            'category' => 'required|string|max:100',
            'topic' => 'nullable|string|max:150',
            'featured' => 'required|boolean',
            'schema_enabled' => 'required|boolean',
            'order' => 'required|integer|min:0|max:9999',
            'verified' => [$publishing && ! ContentPublisher::immediate() ? 'accepted' : 'nullable', 'boolean'],
            'related_ids' => 'sometimes|array|max:40',
            'related_ids.*' => ['integer', 'distinct', Rule::in(self::relatedOptions()->pluck('id')->reject(fn ($id) => $id === $entry?->id)->all())],
        ];
        foreach (['short_answer' => 1500, 'body' => 40000, 'explanation' => 20000, 'author' => 150, 'reviewer' => 150, 'source_note' => 5000, 'seo_title' => 180, 'seo_description' => 300] as $field => $limit) {
            $rules[$field] = [$publishing && in_array($field, ContentPublisher::immediate() ? ['short_answer', 'body'] : ['short_answer', 'body', 'author', 'reviewer', 'source_note']) ? 'required' : 'nullable', 'string', 'max:'.$limit];
        }

        return $rules;
    }

    public static function items(?string $type = null): Collection
    {
        return ContentEntry::whereIn('type', $type ? [$type] : array_keys(self::TYPES))->whereNotNull('published_revision_id')->with('publishedRevision')->get()->map(fn ($entry) => [
            ...$entry->publishedRevision->payload, 'id' => $entry->id, 'type' => $entry->type, 'slug' => $entry->slug,
            'url' => route('knowledge.'.$entry->type.'.show', $entry->slug),
            'published_at' => $entry->published_at?->toDateString(), 'updated_at' => $entry->publishedRevision->created_at->toDateString(),
        ])->sortBy('order');
    }

    public static function relatedOptions(): Collection
    {
        $items = self::items();
        foreach (['project' => ProjectContent::items(), 'location' => LocationContent::active(), 'service' => CorporateContent::items('service'), 'page' => ContentEntry::publishedItems('page')] as $type => $records) {
            foreach ($records as $record) {
                $id = $type === 'service' ? $record['entry_id'] : $record['id'];
                $items->push(['id' => $id, 'title' => $record['title'], 'type' => $type, 'url' => $record['url'] ?? route('pages.show', $record['slug'])]);
            }
        }

        return $items;
    }

    public static function relatedTo(ContentEntry $entry): Collection
    {
        return self::items()->filter(fn ($item) => in_array($entry->id, $item['related_ids'] ?? []));
    }

    public static function detail(ContentEntry $entry, array $payload, bool $preview = false): View
    {
        return view('knowledge.show', CorporateContent::layout(($payload['seo_title'] ?? '') ?: $payload['title'], ($payload['seo_description'] ?? '') ?: ($payload['short_answer'] ?? '')) + compact('entry', 'payload', 'preview') + [
            'canonical' => route('knowledge.'.$entry->type.'.show', $entry->slug),
            'relatedItems' => self::relatedOptions()->whereIn('id', $payload['related_ids'] ?? []),
            'publishedDate' => $entry->published_at?->toDateString(),
            'updatedDate' => ($preview ? $entry->revisions()->latest('version')->first() : $entry->publishedRevision)?->created_at->toDateString(),
        ]);
    }
}
