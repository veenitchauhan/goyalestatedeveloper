<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveSettingsRequest;
use App\Models\ContentEntry;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Services\ContentPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $entry = ContentEntry::where('type', 'settings')->where('slug', 'global')->firstOrFail();
        $revision = $entry->revisions()->latest('version')->firstOrFail();

        return view('admin.settings', ['entry' => $entry, 'revision' => $revision, 'settings' => $revision->payload, 'media' => Media::where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where('mime', 'like', 'image/%')->orderBy('title')->get()]);
    }

    public function update(SaveSettingsRequest $request, ContentPublisher $publisher): RedirectResponse
    {
        $entry = ContentEntry::where('type', 'settings')->where('slug', 'global')->firstOrFail();
        $data = SiteSetting::current();
        foreach (Arr::dot($request->validated('settings')) as $key => $value) {
            Arr::set($data, $key, $value ?? '');
        }
        $data['watermark']['enabled'] = $request->boolean('settings.watermark.enabled');
        $publisher->save($entry, $data, $request->integer('version'));

        return back()->with('status','Settings draft saved. Submit for review, approve and publish to update the website.');
    }
}
