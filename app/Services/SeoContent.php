<?php

namespace App\Services;

use App\Models\ContentEntry;
use App\Models\Homepage;
use App\Models\SeoPage;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;

class SeoContent
{
    public static function absolute(string $path): string
    {
        return rtrim(config('app.url'), '/').'/'.ltrim($path, '/');
    }

    public static function inventory(): Collection
    {
        $items = collect();
        $home = Homepage::main()->content;
        foreach (['/' => $home['seo']['title'], '/about' => 'About us', '/business' => 'Business', '/capabilities' => 'Capabilities', '/capabilities/equipment' => 'Equipment', '/about/leadership' => 'Leadership', '/about/journey' => 'Our journey', '/about/employee-stories' => 'Employee stories', '/projects' => 'Projects', '/locations' => 'Locations', '/careers' => 'Careers', '/insights' => 'Insights', '/knowledge-bank' => 'Knowledge Bank', '/faqs' => 'FAQs', '/contact' => 'Contact'] as $path => $title) {
            $items->put($path, ['path' => $path, 'title' => $title, 'description' => $path === '/' ? $home['seo']['description'] : '', 'type' => 'hub']);
        }
        $records = KnowledgeContent::items();
        foreach (array_keys(config('corporate')) as $type) {
            $records = $records->concat(CorporateContent::items($type)->map(fn ($item) => [...$item, 'type' => $type]));
        }
        $records = $records->concat(ProjectContent::items()->map(fn ($item) => [...$item, 'type' => 'project']))->concat(LocationContent::active()->map(fn ($item) => [...$item, 'type' => 'location']));
        foreach (['job' => CareerContent::openings(), 'page' => ContentEntry::publishedItems('page'), 'campaign' => ContentEntry::publishedItems('campaign')] as $type => $entries) {
            foreach ($entries as $item) {
                $records->push([...$item, 'type' => $type, 'url' => route(match ($type) {
                    'job' => 'careers.show', 'campaign' => 'campaigns.show', default => 'pages.show'
                }, $item['slug'])]);
            }
        }
        foreach ($records as $record) {
            $path = parse_url($record['url'], PHP_URL_PATH);
            $items->put($path, ['path' => $path, 'title' => ($record['seo_title'] ?? '') ?: $record['title'], 'description' => ($record['seo_description'] ?? '') ?: ($record['short_answer'] ?? $record['summary'] ?? $record['description'] ?? ''), 'type' => $record['type'], 'cover_media_id' => $record['cover_media_id'] ?? null]);
        }

        return $items;
    }

    public static function globallyIndexable(): bool
    {
        return app()->isProduction() && ! str_contains(SiteSetting::current()['seo']['robots'], 'noindex');
    }

    public static function metadata(array $content, ?string $canonical = null, bool $preview = false): array
    {
        $path = parse_url($canonical ?? request()->url(), PHP_URL_PATH) ?: '/';
        $saved = $preview ? [] : (SeoPage::where('path', $path)->first()?->data ?? []);
        $settings = SiteSetting::current();
        $title = ($saved['title'] ?? '') ?: ($content['seo']['title'] ?: $settings['seo']['title']);
        $description = ($saved['description'] ?? '') ?: ($content['seo']['description'] ?: $settings['seo']['description']);
        $canonicalPath = ($saved['canonical_path'] ?? '') ?: $path;
        if ($canonicalPath !== $path && ! self::inventory()->has($canonicalPath)) {
            $canonicalPath = $path;
        }
        $index = ! $preview && self::globallyIndexable() && ($saved['indexable'] ?? true) && ! request()->routeIs('search', 'privacy.preferences');
        $image = SiteSetting::image(($saved['og_image_id'] ?? null) ?: $settings['branding']['og_image_id']);

        return ['title' => $title, 'description' => $description, 'canonical' => self::absolute($canonicalPath), 'robots' => $index ? 'index,follow' : 'noindex,nofollow', 'og_title' => ($saved['og_title'] ?? '') ?: $title, 'og_description' => ($saved['og_description'] ?? '') ?: $description, 'image' => $image ? route('media.show', $image) : null, 'schema_enabled' => $saved['schema_enabled'] ?? true];
    }

    public static function schema(array $meta, ?ContentEntry $entry = null, array $payload = [], bool $preview = false): array
    {
        if ($preview || ! $meta['schema_enabled'] || request()->routeIs('search', 'privacy.preferences')) {
            return [];
        }
        $settings = SiteSetting::current();
        $organization = ['@type' => 'Organization', '@id' => self::absolute('/').'#organization', 'name' => config('app.name'), 'url' => self::absolute('/')];
        foreach (['phone' => 'telephone', 'email' => 'email', 'address' => 'address'] as $key => $property) {
            if ($settings['contact'][$key]) {
                $organization[$property] = $settings['contact'][$key];
            }
        }
        $organization['sameAs'] = array_values(array_filter($settings['social']));
        $graph = [$organization, ['@type' => 'WebPage', '@id' => $meta['canonical'].'#webpage', 'url' => $meta['canonical'], 'name' => $meta['title'], 'description' => $meta['description']]];
        if ($entry && ! empty($payload['title'])) {
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => self::absolute('/')], ['@type' => 'ListItem', 'position' => 2, 'name' => $payload['title'], 'item' => $meta['canonical']]]];
        }
        if ($entry && in_array($entry->type, ['article', 'knowledge']) && ! empty($payload['author'])) {
            $article = ['@type' => 'Article', 'headline' => $payload['title'], 'description' => $payload['short_answer'] ?? '', 'author' => ['@type' => 'Person', 'name' => $payload['author']], 'publisher' => ['@id' => $organization['@id']], 'mainEntityOfPage' => $meta['canonical'], 'datePublished' => $entry->published_at?->toIso8601String(), 'dateModified' => $entry->publishedRevision?->created_at->toIso8601String()];
            if ($meta['image']) {
                $article['image'] = $meta['image'];
            }
            $graph[] = $article;
        }
        if ($entry?->type === 'faq' && ($payload['schema_enabled'] ?? false)) {
            $graph[] = ['@type' => 'FAQPage', 'mainEntity' => [['@type' => 'Question', 'name' => $payload['title'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(($payload['short_answer'] ?? '')."\n".($payload['body'] ?? ''))]]]];
        }
        if ($entry?->type === 'service') {
            $graph[] = ['@type' => 'Service', 'name' => $payload['title'], 'description' => $payload['summary'] ?? '', 'provider' => ['@id' => $organization['@id']], 'url' => $meta['canonical']];
        }
        if ($entry?->type === 'job' && ! empty($payload['address_country']) && ! empty($payload['address_locality']) && ! empty($payload['deadline']) && $payload['deadline'] >= now()->toDateString()) {
            $address = ['@type' => 'PostalAddress', 'addressCountry' => $payload['address_country'], 'addressLocality' => $payload['address_locality']];
            foreach (['address_region' => 'addressRegion', 'postal_code' => 'postalCode', 'street_address' => 'streetAddress'] as $key => $field) {
                if (! empty($payload[$key])) {
                    $address[$field] = $payload[$key];
                }
            }
            $graph[] = ['@type' => 'JobPosting', 'title' => $payload['title'], 'description' => implode("\n", array_filter([$payload['description'] ?? '', $payload['responsibilities'] ?? '', $payload['requirements'] ?? ''])), 'datePosted' => $entry->published_at?->toIso8601String(), 'validThrough' => $payload['deadline'].'T23:59:59+00:00', 'employmentType' => match ($payload['employment_type']) {
                'Part-time' => 'PART_TIME', 'Temporary' => 'TEMPORARY', default => 'FULL_TIME'
            }, 'hiringOrganization' => $organization, 'jobLocation' => ['@type' => 'Place', 'address' => $address]];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
