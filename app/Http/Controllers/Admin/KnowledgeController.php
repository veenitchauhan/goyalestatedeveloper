<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveKnowledgeRequest;
use App\Models\ContentEntry;
use App\Services\ContentPublisher;
use App\Services\KnowledgeContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['q' => 'nullable|string|max:150', 'type' => ['nullable', Rule::in(array_keys(KnowledgeContent::TYPES))]]);

        return view('admin.knowledge.index', ['entries' => ContentEntry::whereIn('type', array_keys(KnowledgeContent::TYPES))->when(request('q'), fn ($query) => $query->where('title', 'like', '%'.request('q').'%'))->when(request('type'), fn ($query) => $query->where('type', request('type')))->latest()->paginate(30)->withQueryString()]);
    }

    public function create(): View
    {
        return $this->editor(new ContentEntry(['type' => 'knowledge']));
    }

    public function edit(ContentEntry $entry): View
    {
        abort_unless(KnowledgeContent::supports($entry->type), 404);

        return $this->editor($entry);
    }

    private function editor(ContentEntry $entry): View
    {
        $revision = $entry->exists ? $entry->revisions()->latest('version')->firstOrFail() : null;

        return view('admin.knowledge.edit', ['entry' => $entry, 'revision' => $revision, 'payload' => $revision?->payload ?? [], 'options' => KnowledgeContent::relatedOptions()->reject(fn ($item) => $item['id'] === $entry->id)]);
    }

    public function store(SaveKnowledgeRequest $request, ContentPublisher $publisher): RedirectResponse
    {
        $entry = DB::transaction(function () use ($request, $publisher) {
            $entry = ContentEntry::create(['type' => $request->validated('type'), 'slug' => $request->validated('slug'), 'title' => $request->validated('title'), 'author_id' => auth()->id()]);
            $publisher->save($entry, $request->validated(), 0);

            return $entry;
        });

        return redirect()->route('admin.knowledge.edit', $entry)->with('status', 'Draft created. Add verified content before publication.');
    }

    public function update(SaveKnowledgeRequest $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless(KnowledgeContent::supports($entry->type) && $entry->slug === $request->validated('slug') && $entry->type === $request->validated('type'), 422);
        DB::transaction(function () use ($request, $entry, $publisher) {
            $publisher->save($entry, $request->validated(), $request->integer('version'));
            $entry->update(['title' => $request->validated('title')]);
        });

        return back()->with('status', 'Draft saved. The published version is unchanged.');
    }

    public function preview(ContentEntry $entry): View
    {
        abort_unless(KnowledgeContent::supports($entry->type), 404);

        return KnowledgeContent::detail($entry, $entry->revisions()->latest('version')->firstOrFail()->payload, true);
    }
}
