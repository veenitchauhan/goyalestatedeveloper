<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveHomepageRequest;
use App\Models\ContentEntry;
use App\Models\Homepage;
use App\Models\Media;
use App\Services\ContentPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function edit(): View
    {
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $revision = $entry->revisions()->latest('version')->firstOrFail();

        return view('admin.homepage', [
            'entry' => $entry, 'revision' => $revision,
            'fields' => Arr::dot(Homepage::editableContent($revision->payload)),
            'media' => Media::where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', 'image/%')->orWhere('mime', 'video/mp4'))->get(),
            'statistics' => ContentEntry::publishedItems('statistic'),
            'ctas' => ContentEntry::publishedItems('cta'),
        ]);
    }

    public function update(SaveHomepageRequest $request, ContentPublisher $publisher): RedirectResponse
    {
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $updated = Homepage::editableContent($entry->revisions()->latest('version')->firstOrFail()->payload);
        $fields = Arr::dot($updated);
        foreach (Arr::dot($request->validated('content', [])) as $key => $value) {
            if (! array_key_exists($key, $fields) || str_ends_with($key, '.id') || str_ends_with($key, '_id') || str_starts_with($key, 'statistics.')) {
                continue;
            }
            $original = $fields[$key];
            Arr::set($updated, $key, is_bool($original) ? (bool) $value : (is_int($original) ? (int) $value : ($value ?? '')));
        }
        foreach (['hero_media_id' => 'media_id', 'hero_video_id' => 'video_id', 'primary_cta_id' => 'primary_cta_id', 'secondary_cta_id' => 'secondary_cta_id'] as $input => $key) {
            if ($request->exists($input)) {
                $updated['hero'][$key] = $request->integer($input) ?: null;
            }
        }
        if ($request->exists('statistics_mode')) {
            $updated['statistics'] = ['mode' => $request->validated('statistics_mode'), 'ids' => array_map('intval', $request->validated('statistic_ids', []))];
        }
        foreach ($request->validated('section_assets', []) as $index => $assets) {
            foreach ($assets as $key => $id) {
                $updated['sections'][$index][$key] = $id ? (int) $id : null;
            }
        }
        $publisher->save($entry, $updated, $request->integer('version'));

        return back()->with('status', ContentPublisher::immediate() ? 'Saved. Changes are live immediately.' : 'Homepage draft saved. Preview it, then submit for review and approval before publishing.');
    }
}
