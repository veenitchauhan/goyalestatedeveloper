<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveLocationRequest;
use App\Models\ContentEntry;
use App\Services\ContentPublisher;
use App\Services\CorporateContent;
use App\Services\LocationContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        return view('admin.locations.index', ['entries' => ContentEntry::where('type', 'location')->latest()->paginate(30)]);
    }

    public function create(): View
    {
        return $this->editor(new ContentEntry(['type' => 'location']));
    }

    public function edit(ContentEntry $entry): View
    {
        abort_unless($entry->type === 'location', 404);

        return $this->editor($entry);
    }

    private function editor(ContentEntry $entry): View
    {
        $revision = $entry->exists ? $entry->revisions()->latest('version')->firstOrFail() : null;

        return view('admin.locations.edit', ['entry' => $entry, 'revision' => $revision, 'payload' => $revision?->payload ?? [], 'parents' => LocationContent::published()->where('id', '!=', $entry->id), 'services' => CorporateContent::items('service')]);
    }

    public function store(SaveLocationRequest $request, ContentPublisher $publisher): RedirectResponse
    {
        $entry = DB::transaction(function () use ($request, $publisher) {
            $entry = ContentEntry::create(['type' => 'location', 'slug' => $request->validated('slug'), 'title' => $request->validated('title'), 'author_id' => auth()->id()]);
            $publisher->save($entry, $request->validated(), 0);

            return $entry;
        });

        return redirect()->route('admin.locations.edit', $entry)->with('status', 'Location draft created. Add verified information before publication.');
    }

    public function update(SaveLocationRequest $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless($entry->type === 'location' && $entry->slug === $request->validated('slug'), 422);
        $previous = $entry->revisions()->latest('version')->firstOrFail()->payload;
        abort_unless($request->validated('level') === $previous['level'], 422, 'Create a separate record for a different hierarchy level.');
        DB::transaction(function () use ($request, $entry, $publisher) {
            $publisher->save($entry, $request->validated(), $request->integer('version'));
            $entry->update(['title' => $request->validated('title')]);
        });

        return back()->with('status', 'Location draft saved. Public information changes only after publication.');
    }
}
