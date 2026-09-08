<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentEntry;
use App\Models\Homepage;
use App\Models\Media;
use App\Services\AuditRecorder;
use App\Services\ContentPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function edit(): View
    {
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $revision = $entry->revisions()->latest('version')->firstOrFail();

        return view('admin.homepage', ['entry' => $entry, 'revision' => $revision, 'fields' => Arr::dot($revision->payload), 'media' => Media::where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', 'image/%'))->get()]);
    }

    public function update(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $page = Homepage::main();
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $request->validate(['version' => 'required|integer|min:1', 'hero_media_id' => ['nullable', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', 'image/%'))]]);
        $flat = Arr::dot($page->content);
        $rules = [];
        foreach ($flat as $key => $value) {
            if (str_ends_with($key, '.id') || str_ends_with($key, '.media_id')) {
                continue;
            }
            $rules['content.'.$key] = is_bool($value) ? ['required', 'boolean'] : (is_int($value) ? ['required', 'integer', 'min:0', 'max:1000'] : ['nullable', 'string', 'max:5000']);
        }
        $rules['content.contact.phone'] = ['nullable', 'regex:/^\+?[0-9 ()-]{7,25}$/'];
        $rules['content.contact.whatsapp'] = ['nullable', 'regex:/^[0-9]{7,15}$/'];
        $rules['content.contact.email'] = ['nullable', 'email', 'max:254'];
        foreach (['hero.line_one', 'hero.line_two', 'hero.line_three', 'seo.title', 'seo.description'] as $key) {
            $rules['content.'.$key] = ['required', 'string', 'max:500'];
        }
        $validated = $request->validate($rules)['content'];
        $updated = $page->content;
        foreach (Arr::dot($validated) as $key => $value) {
            if (! array_key_exists($key, Arr::dot($page->content)) || str_ends_with($key, '.id')) {
                continue;
            }
            $original = Arr::get($updated, $key);
            Arr::set($updated, $key, is_bool($original) ? (bool) $value : (is_int($original) ? (int) $value : ($value ?? '')));
        }
        $updated['hero']['media_id'] = $request->integer('hero_media_id') ?: null;
        app(ContentPublisher::class)->save($entry, $updated, $request->integer('version'));

        return back()->with('status', 'Homepage draft saved. Preview it, then submit for review or publish.');
    }
}
