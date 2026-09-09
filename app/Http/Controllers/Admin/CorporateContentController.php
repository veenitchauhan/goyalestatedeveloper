<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCorporateContentRequest;
use App\Models\ContentEntry;
use App\Models\Media;
use App\Services\ContentPublisher;
use App\Services\CorporateContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CorporateContentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['type' => ['nullable', 'in:'.implode(',', array_keys(config('corporate')))], 'q' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', 'in:draft,review,approved,published,scheduled,unpublished,archived']]);
        $entries = ContentEntry::whereIn('type', array_keys(config('corporate')))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('title', 'like', '%'.$search.'%'))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.corporate-index', compact('entries', 'filters'));
    }

    public function create(Request $request): View
    {
        $data = $request->validate(['type' => ['sometimes', 'string', 'in:'.implode(',', array_keys(config('corporate')))]]);
        $type = $data['type'] ?? 'company_page';
        CorporateContent::definition($type);

        return $this->editor(new ContentEntry(['type' => $type]));
    }

    public function edit(ContentEntry $entry): View
    {
        CorporateContent::definition($entry->type);

        return $this->editor($entry);
    }

    private function editor(ContentEntry $entry): View
    {
        $definition = CorporateContent::definition($entry->type);
        $revision = $entry->exists ? $entry->revisions()->latest('version')->firstOrFail() : null;
        $payload = $revision?->payload ?? ['order' => 0, 'featured' => false];
        $references = [];
        foreach ($definition['fields'] as $field => $options) {
            if ($options['kind'] === 'relation') {
                $references[$field] = CorporateContent::items($options['related_type']);
            }
        }

        return view('admin.corporate-edit', compact('entry', 'definition', 'revision', 'payload', 'references') + [
            'media' => Media::where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->orderBy('title')->get(),
            'ctas' => ContentEntry::publishedItems('cta'),
            'related' => ContentEntry::whereIn('type', array_keys(config('corporate')))->whereNotNull('published_revision_id')->where('id', '!=', $entry->id ?? 0)->with('publishedRevision')->get(),
        ]);
    }

    public function store(SaveCorporateContentRequest $request, ContentPublisher $publisher): RedirectResponse
    {
        $data = $request->validated();
        $entry = DB::transaction(function () use ($data, $publisher) {
            $entry = ContentEntry::create(['type' => $data['type'], 'slug' => $data['slug'], 'title' => $data['title'], 'author_id' => auth()->id()]);
            $publisher->save($entry, $data, 0);

            return $entry;
        });

        return redirect()->route('admin.corporate.edit', $entry)->with('status', ContentPublisher::immediate() ? 'Saved. Changes are live immediately.' : 'Draft created. Add verified company information before submitting for approval.');
    }

    public function update(SaveCorporateContentRequest $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        CorporateContent::definition($entry->type);
        $data = $request->validated();
        abort_unless($data['type'] === $entry->type && $data['slug'] === $entry->slug, 422);
        DB::transaction(function () use ($entry, $data, $publisher) {
            $publisher->save($entry, $data, (int) $data['version']);
            $entry->update(['title' => $data['title']]);
        });

        return back()->with('status', ContentPublisher::immediate() ? 'Saved. Changes are live immediately.' : 'Draft saved. The live page stays unchanged until the new version is approved and published.');
    }
}
