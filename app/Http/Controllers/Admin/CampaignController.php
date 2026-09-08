<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCampaignRequest;
use App\Models\ContentEntry;
use App\Models\Media;
use App\Services\CampaignContent;
use App\Services\ContentPublisher;
use App\Services\KnowledgeContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['q' => 'nullable|string|max:150']);

        return view('admin.campaigns.index', ['entries' => ContentEntry::where('type', 'campaign')->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->input('q').'%'))->latest()->paginate(30)->withQueryString()]);
    }

    public function create(): View
    {
        return $this->editor(new ContentEntry(['type' => 'campaign']));
    }

    public function edit(ContentEntry $entry): View
    {
        abort_unless($entry->type === 'campaign', 404);

        return $this->editor($entry);
    }

    private function editor(ContentEntry $entry): View
    {
        $revision = $entry->exists ? $entry->revisions()->latest('version')->firstOrFail() : null;

        return view('admin.campaigns.edit', ['entry' => $entry, 'revision' => $revision, 'payload' => $revision?->payload ?? [], 'options' => KnowledgeContent::relatedOptions(), 'statistics' => ContentEntry::publishedItems('statistic'), 'mediaItems' => Media::where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', 'image/%')->orWhere('mime', 'video/mp4'))->get()]);
    }

    public function store(SaveCampaignRequest $request, ContentPublisher $publisher): RedirectResponse
    {
        $entry = DB::transaction(function () use ($request, $publisher) {
            $entry = ContentEntry::create(['type' => 'campaign', 'slug' => $request->validated('slug'), 'title' => $request->validated('title'), 'author_id' => auth()->id()]);
            $publisher->save($entry, $request->validated(), 0);

            return $entry;
        });

        return redirect()->route('admin.campaigns.edit', $entry)->with('status', 'Draft created. Add verified content before publication.');
    }

    public function update(SaveCampaignRequest $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless($entry->type === 'campaign' && $entry->slug === $request->validated('slug'), 422);
        DB::transaction(function () use ($request, $entry, $publisher) {
            $publisher->save($entry, $request->validated(), $request->integer('version'));
            $entry->update(['title' => $request->validated('title')]);
        });

        return back()->with('status', 'Draft saved. The published version is unchanged.');
    }

    public function preview(ContentEntry $entry): View
    {
        abort_unless($entry->type === 'campaign', 404);

        return CampaignContent::detail($entry, $entry->revisions()->latest('version')->firstOrFail()->payload, true);
    }

    public function transition(Request $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless($entry->type === 'campaign', 404);
        $data = $request->validate(['action' => 'required|in:review,approve,publish,schedule,return,unpublish,archive,restore', 'version' => 'required|integer', 'scheduled_at' => 'nullable|required_if:action,schedule|date|after:now', 'note' => 'nullable|string|max:1000']);
        if (! in_array($data['action'], ['review', 'restore'])) {
            abort_unless($request->user()->can(match ($data['action']) {
                'approve', 'return' => 'pages.approve', 'unpublish' => 'pages.unpublish', 'archive' => 'pages.archive', default => 'pages.publish'
            }), 403);
        }
        $publisher->transition($entry, $data['action'], $data['version'], $data['scheduled_at'] ?? null, $data['note'] ?? null);

        return back()->with('status', 'Campaign publication updated.');
    }
}
