<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveContentRequest;
use App\Models\ContentEntry;
use App\Models\Homepage;
use App\Services\ContentPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(): View
    {
        return view('admin.content.index', ['entries' => ContentEntry::whereIn('type', ['page', 'block', 'statistic', 'menu'])->latest()->paginate(30)]);
    }

    public function create(): View
    {
        return $this->editor(new ContentEntry);
    }

    public function edit(ContentEntry $entry): View
    {
        $this->checkType($entry);

        return $this->editor($entry);
    }

    private function editor(ContentEntry $entry): View
    {
        $revision = $entry->exists ? $entry->revisions()->latest('version')->first() : null;

        return view('admin.content.edit', ['entry' => $entry, 'revision' => $revision, 'payload' => $revision?->payload ?? [], 'blocks' => ContentEntry::where('type', 'block')->get(), 'statistics' => ContentEntry::where('type', 'statistic')->get()]);
    }

    private function checkType(ContentEntry $entry): void
    {
        abort_unless(in_array($entry->type, ['page', 'block', 'statistic', 'menu']), 404);
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
        abort_unless(in_array($entry->type, ['homepage', 'page', 'block', 'statistic', 'menu']), 404);
        $data = $request->validate(['action' => 'required|in:review,publish,schedule,return,unpublish', 'version' => 'required|integer', 'scheduled_at' => 'nullable|required_if:action,schedule|date|after:now', 'note' => 'nullable|string|max:1000']);
        abort_unless($request->user()->can(match ($data['action']) {
            'review' => 'pages.edit', 'unpublish' => 'pages.unpublish', default => 'pages.publish'
        }), 403);
        $publisher->transition($entry, $data['action'], (int) $data['version'], $data['scheduled_at'] ?? null, $data['note'] ?? null);

        return back()->with('status', 'Content status updated.');
    }

    public function restore(Request $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless(in_array($entry->type, ['homepage', 'page', 'block', 'statistic', 'menu']), 404);
        $data = $request->validate(['revision_id' => 'required|integer', 'version' => 'required|integer']);
        $revision = $entry->revisions()->findOrFail($data['revision_id']);
        $publisher->save($entry, $revision->payload, (int) $data['version']);

        return back()->with('status', 'Previous revision restored as a new draft. Review before publishing.');
    }

    public function preview(ContentEntry $entry): View
    {
        abort_unless(in_array($entry->type, ['homepage', 'page', 'block', 'statistic', 'menu']), 404);
        $payload = $entry->revisions()->latest('version')->firstOrFail()->payload;
        $content = Homepage::main()->content;
        if ($entry->type === 'homepage') {
            $content = $payload;
        }
        $sections = collect($content['sections'])->where('enabled', true)->sortBy('order');

        return view($entry->type === 'homepage' ? 'home' : 'page', compact('content', 'sections', 'payload', 'entry') + ['preview' => true]);
    }
}
