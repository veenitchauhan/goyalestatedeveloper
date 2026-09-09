<?php

namespace App\Services;

use App\Models\ContentEntry;
use App\Models\ContentRevision;
use App\Models\CorporateRecord;
use App\Models\Homepage;
use App\Models\Media;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CorporateContent
{
    public static function supports(string $type): bool
    {
        return array_key_exists($type, config('corporate'));
    }

    /**
     * @return array{label: string, singular: string, model: class-string<CorporateRecord>, route: string, group: string, fields: array<string, array{label: string, kind: string, required?: bool, options?: array<string, string>, related_type?: string, min?: int, max?: int}>}
     */
    public static function definition(string $type): array
    {
        abort_unless(static::supports($type), 404);

        return config('corporate.'.$type);
    }

    public static function query(string $type): Builder
    {
        $model = static::definition($type)['model'];
        $query = $model::query()->whereHas('entry', fn (Builder $query) => $query->whereNotNull('published_revision_id'))->with('entry.publishedRevision');
        if ($type === 'service') {
            $query->whereHas('businessUnit.entry', fn (Builder $query) => $query->whereNotNull('published_revision_id'));
        }
        if ($type === 'employee_story') {
            $query->whereHas('teamMember.entry', fn (Builder $query) => $query->whereNotNull('published_revision_id'));
        }

        return $query;
    }

    public static function item(CorporateRecord $record): array
    {
        $entry = $record->entry;

        return [...$entry->publishedRevision->payload, 'id' => $record->id, 'entry_id' => $entry->id, 'url' => route(static::definition($entry->type)['route'], $entry->slug)];
    }

    public static function items(string $type): Collection
    {
        if (! static::supports($type)) {
            return collect();
        }

        return static::query($type)->get()->map(fn (CorporateRecord $record) => static::item($record))->sortBy('order')->sortByDesc('featured');
    }

    public static function rules(string $type, ?ContentEntry $entry = null, bool $publishing = false): array
    {
        $definition = static::definition($type);
        $reserved = match ($type) {
            'company_page' => ['leadership', 'journey', 'employee-stories', 'people', 'milestones'],
            'business_unit' => ['services'], 'capability' => ['equipment'], default => [],
        };
        $rules = [
            'type' => ['required', Rule::in([$type])],
            'slug' => ['required', 'alpha_dash:ascii', 'max:150', Rule::notIn($reserved), Rule::unique('content_entries', 'slug')->where('type', $type)->ignore($entry)],
            'title' => ['required', 'string', 'max:180'],
            'summary' => [$publishing ? 'required' : 'nullable', 'string', 'max:600'],
            'body' => [$publishing ? 'required' : 'nullable', 'string', ...($publishing ? ['min:80'] : []), 'max:30000'],
            'seo_title' => ['nullable', 'string', 'max:180'], 'seo_description' => ['nullable', 'string', 'max:500'],
            'order' => ['required', 'integer', 'min:0', 'max:1000'], 'featured' => ['required', 'boolean'],
            'source_note' => ['nullable', 'string', 'max:2000'],
            'facts' => ['sometimes', count($definition['fields']) ? 'array:'.implode(',', array_keys($definition['fields'])) : 'array', ...(! count($definition['fields']) ? ['max:0'] : [])],
        ];
        foreach ($definition['fields'] as $field => $options) {
            $fieldRules = [($options['required'] ?? false) ? 'required' : 'nullable'];
            $fieldRules = [...$fieldRules, ...match ($options['kind']) {
                'number' => ['integer', 'min:'.$options['min'], 'max:'.$options['max']],
                'date' => ['date_format:Y-m-d', 'before_or_equal:today'],
                'select' => [Rule::in(array_keys($options['options']))],
                'relation' => ['integer', Rule::exists((new (static::definition($options['related_type'])['model']))->getTable(), 'id')->where(fn ($query) => $query->whereIn('content_entry_id', ContentEntry::where('type', $options['related_type'])->whereNotNull('published_revision_id')->select('id')))],
                default => ['string', 'max:'.($options['kind'] === 'textarea' ? 10000 : 255)],
            }];
            $rules['facts.'.$field] = $fieldRules;
        }
        foreach (['cover_media_id' => 'image/%', 'video_media_id' => 'video/mp4', 'gallery_ids.*' => 'image/%', 'document_ids.*' => 'application/pdf'] as $field => $mime) {
            $rules[$field] = ['nullable', 'integer', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', $mime))];
        }
        foreach (['gallery_ids', 'document_ids', 'cta_ids', 'related_ids'] as $field) {
            $rules[$field] = ['sometimes', 'array', 'max:30'];
        }
        $rules['cta_ids.*'] = ['integer', 'distinct', Rule::exists('content_entries', 'id')->where('type', 'cta')->whereNotNull('published_revision_id')];
        $rules['related_ids.*'] = ['integer', 'distinct', Rule::exists('content_entries', 'id')->where(fn ($query) => $query->whereIn('type', array_keys(config('corporate')))->whereNotNull('published_revision_id'))];

        return $rules;
    }

    public function apply(ContentEntry $entry, ContentRevision $revision): void
    {
        $data = Validator::make($revision->payload, static::rules($entry->type, $entry, true))->validate();
        $definition = static::definition($entry->type);
        $facts = array_fill_keys(array_keys($definition['fields']), null);
        $facts = array_replace($facts, Arr::only($data['facts'] ?? [], array_keys($facts)));
        $definition['model']::updateOrCreate(['content_entry_id' => $entry->id], $facts);
    }

    /** @return array{content: array<string, mixed>, sections: Collection<int, array<string, mixed>>} */
    public static function layout(string $title, string $description = ''): array
    {
        $content = SiteSetting::applyTo(Homepage::main()->content);
        $content['seo'] = ['title' => $title.' | '.config('app.name'), 'description' => $description];
        $sections = collect($content['sections'])->where('enabled', true)->sortBy('order');

        return compact('content', 'sections');
    }

    public static function detail(ContentEntry $entry, array $payload, bool $preview = false): View
    {
        $definition = static::definition($entry->type);
        $canonical = route($definition['route'], $entry->slug);
        $mediaIds = [$payload['cover_media_id'] ?? null, $payload['video_media_id'] ?? null, ...($payload['gallery_ids'] ?? []), ...($payload['document_ids'] ?? [])];
        $media = Media::whereIn('id', array_filter($mediaIds))->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->get()->keyBy('id');
        $related = collect();
        foreach (($payload['related_ids'] ?? []) ? array_keys(config('corporate')) : [] as $type) {
            $related = $related->merge(static::query($type)->whereIn('content_entry_id', $payload['related_ids'] ?? [])->get()->map(fn (CorporateRecord $record) => static::item($record)));
        }
        $relations = [];
        foreach ($definition['fields'] as $field => $options) {
            if ($options['kind'] === 'relation' && ($id = $payload['facts'][$field] ?? null)) {
                $record = static::query($options['related_type'])->find($id);
                $relations[$field] = $record ? static::item($record) : null;
            }
        }
        $services = collect();
        if ($entry->type === 'business_unit') {
            $unit = $definition['model']::where('content_entry_id', $entry->id)->first();
            if ($unit) {
                $services = static::query('service')->where('business_unit_id', $unit->id)->get()->map(fn (CorporateRecord $record) => static::item($record))->sortBy('order');
            }
        }
        $layout = static::layout($payload['title'], ($payload['seo_description'] ?? '') ?: ($payload['summary'] ?? ''));
        if ($payload['seo_title'] ?? null) {
            $layout['content']['seo']['title'] = $payload['seo_title'].' | '.config('app.name');
        }

        return view('corporate-detail', $layout + compact('entry', 'payload', 'definition', 'canonical', 'media', 'related', 'relations', 'services', 'preview') + ['ctas' => ContentEntry::publishedItems('cta')->whereIn('id', $payload['cta_ids'] ?? [])]);
    }
}
