<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveContentRequest;
use App\Models\ContentEntry;
use App\Models\Homepage;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Services\ContentPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.content.index', ['entries' => ContentEntry::whereIn('type', ['page', 'block', 'statistic', 'menu', 'cta'])->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))->latest()->paginate(30)->withQueryString()]);
    }

    public function create(Request $request): View
    {
        $type = $request->input('type', 'page');
        abort_unless(in_array($type, ['page', 'block', 'statistic', 'menu', 'cta']), 404);

        return $this->editor(new ContentEntry(['type' => $type]));
    }

    public function edit(ContentEntry $entry): View
    {
        $this->checkType($entry);

        return $this->editor($entry);
    }

    private function editor(ContentEntry $entry): View
    {
        $revision = $entry->exists ? $entry->revisions()->latest('version')->first() : null;

        return view('admin.content.edit', ['entry' => $entry, 'revision' => $revision, 'payload' => $revision?->payload ?? [], 'blocks' => ContentEntry::where('type', 'block')->get(), 'documents' => Media::where('mime', 'application/pdf')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->get(), 'ctas' => ContentEntry::where('type', 'cta')->where('status', '!=', 'archived')->get(), 'statistics' => ContentEntry::where('type', 'statistic')->get()]);
    }

    private function checkType(ContentEntry $entry): void
    {
        abort_unless(in_array($entry->type, ['page', 'block', 'statistic', 'menu', 'cta']), 404);
    }

    public function store(SaveContentRequest $request, ContentPublisher $publisher): RedirectResponse
    {
        $data = $request->validated();
        $entry = DB::transaction(function () use ($data, $publisher) {
            $entry = ContentEntry::create(['type' => $data['type'], 'slug' => $data['slug'], 'title' => $data['title'], 'author_id' => auth()->id()]);
            $publisher->save($entry, $data, 0);

            return $entry;
        });

        return redirect()->route('admin.content.edit', $entry)->with('status', 'Draft created. It is not visible to visitors.');
    }

    public function update(SaveContentRequest $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        $this->checkType($entry);
        $data = $request->validated();
        abort_unless($data['type'] === $entry->type && $data['slug'] === $entry->slug, 422);
        DB::transaction(function () use ($entry, $data, $publisher) {
            $publisher->save($entry, $data, (int) $data['version']);
            $entry->update(['title' => $data['title']]);
        });

        return back()->with('status', 'Draft saved. The published version is unchanged.');
    }

    public function transition(Request $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless(in_array($entry->type, ['homepage', 'settings', 'page', 'block', 'statistic', 'menu', 'cta']), 404);
        if ($entry->type === 'settings') {
            abort_unless($request->user()->can('settings.manage'), 403);
        }
        $data = $request->validate(['action' => 'required|in:review,approve,publish,schedule,return,unpublish,archive,restore', 'version' => 'required|integer', 'scheduled_at' => 'nullable|required_if:action,schedule|date|after:now', 'note' => 'nullable|string|max:1000']);
        abort_unless($request->user()->can(match ($data['action']) {
            'review','restore' => 'pages.edit', 'approve','return' => 'pages.approve', 'unpublish' => 'pages.unpublish', 'archive' => 'pages.archive', default => 'pages.publish'
        }), 403);
        $publisher->transition($entry, $data['action'], (int) $data['version'], $data['scheduled_at'] ?? null, $data['note'] ?? null);

        return back()->with('status', 'Content status updated.');
    }

    public function restore(Request $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless(in_array($entry->type, ['homepage', 'settings', 'page', 'block', 'statistic', 'menu', 'cta']), 404);
        if ($entry->type === 'settings') {
            abort_unless($request->user()->can('settings.manage'), 403);
        }
        $data = $request->validate(['revision_id' => 'required|integer', 'version' => 'required|integer']);
        $revision = $entry->revisions()->findOrFail($data['revision_id']);
        $publisher->save($entry, $revision->payload, (int) $data['version']);

        return back()->with('status', 'Previous revision restored as a new draft. Review before publishing.');
    }

    public function preview(ContentEntry $entry): View
    {
        abort_unless(in_array($entry->type, ['homepage', 'settings', 'page', 'block', 'statistic', 'menu', 'cta']), 404);
        if ($entry->type === 'settings') {
            abort_unless(auth()->user()->can('settings.manage'), 403);
        }
        $payload = $entry->revisions()->latest('version')->firstOrFail()->payload;
        $content = Homepage::main()->content;
        if ($entry->type === 'homepage') {
            $content = $payload;
        }
        $siteSettings = $entry->type === 'settings' ? $payload : SiteSetting::current();
        $content = SiteSetting::applyTo($content, $siteSettings);
        if ($entry->type === 'settings') {
            $payload = ['title' => 'Website settings preview', 'body' => implode("\n", $siteSettings['contact'])];
        }
        $sections = collect($content['sections'])->where('enabled', true)->sortBy('order');

        return view($entry->type === 'homepage' ? 'home' : 'page', compact('content', 'sections', 'payload', 'entry', 'siteSettings') + ['preview' => true]);
    }
}
