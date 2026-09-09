<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveJobRequest;
use App\Models\ContentEntry;
use App\Services\CareerContent;
use App\Services\ContentPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(): View
    {
        return view('admin.jobs.index', ['entries' => ContentEntry::where('type', 'job')->latest()->paginate(30)]);
    }

    public function create(): View
    {
        return $this->editor(new ContentEntry(['type' => 'job']));
    }

    public function edit(ContentEntry $entry): View
    {
        abort_unless($entry->type === 'job', 404);

        return $this->editor($entry);
    }

    private function editor(ContentEntry $entry): View
    {
        $revision = $entry->exists ? $entry->revisions()->latest('version')->firstOrFail() : null;

        return view('admin.jobs.edit', ['entry' => $entry, 'revision' => $revision, 'payload' => $revision?->payload ?? []]);
    }

    public function store(SaveJobRequest $request, ContentPublisher $publisher): RedirectResponse
    {
        $entry = DB::transaction(function () use ($request, $publisher) {
            $entry = ContentEntry::create(['type' => 'job', 'slug' => $request->validated('slug'), 'title' => $request->validated('title'), 'author_id' => auth()->id()]);
            $publisher->save($entry, $request->validated(), 0);

            return $entry;
        });

        return redirect()->route('admin.jobs.edit', $entry)->with('status', ContentPublisher::immediate() ? 'Saved. Changes are live immediately.' : 'Job draft created. Add verified information before publication.');
    }

    public function update(SaveJobRequest $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless($entry->type === 'job' && $entry->slug === $request->validated('slug'), 422);
        DB::transaction(function () use ($request, $entry, $publisher) {
            $publisher->save($entry, $request->validated(), $request->integer('version'));
            $entry->update(['title' => $request->validated('title')]);
        });

        return back()->with('status', ContentPublisher::immediate() ? 'Saved. Changes are live immediately.' : 'Job draft saved. Public information changes only after publication.');
    }

    public function transition(Request $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless($entry->type === 'job', 404);
        $data = $request->validate(['action' => 'required|in:review,approve,publish,schedule,return,unpublish,archive,restore', 'version' => 'required|integer', 'scheduled_at' => 'nullable|required_if:action,schedule|date|after:now', 'note' => 'nullable|string|max:1000']);
        abort_unless($request->user()->can($data['action'] === 'review' ? 'jobs.edit' : 'jobs.publish'), 403);
        $publisher->transition($entry, $data['action'], $data['version'], $data['scheduled_at'] ?? null, $data['note'] ?? null);

        return back()->with('status', 'Job publication updated. Unpublished jobs no longer accept applications.');
    }

    public function preview(ContentEntry $entry): View
    {
        abort_unless($entry->type === 'job', 404);

        return CareerContent::detail($entry, $entry->revisions()->latest('version')->firstOrFail()->payload, true);
    }
}
