<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveDevelopmentRequest;
use App\Models\ContentEntry;
use App\Models\DevelopmentSpace;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Services\AuditRecorder;
use App\Services\ContentPublisher;
use App\Services\DevelopmentContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DevelopmentController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['q' => 'nullable|string|max:150']);

        return view('admin.developments.index', ['entries' => ContentEntry::where('type', 'development')->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->input('q').'%'))->latest()->paginate(30)->withQueryString()]);
    }

    public function create(): View
    {
        return $this->editor(new ContentEntry(['type' => 'development']));
    }

    public function edit(ContentEntry $entry): View
    {
        abort_unless($entry->type === 'development', 404);

        return $this->editor($entry);
    }

    private function editor(ContentEntry $entry): View
    {
        $revision = $entry->exists ? $entry->revisions()->latest('version')->firstOrFail() : null;

        return view('admin.developments.edit', ['entry' => $entry, 'revision' => $revision, 'payload' => $revision?->payload ?? [], 'spaces' => DevelopmentSpace::where('content_entry_id', $entry->id)->get(), 'mediaItems' => Media::where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->get()]);
    }

    public function store(SaveDevelopmentRequest $request, ContentPublisher $publisher): RedirectResponse
    {
        $entry = DB::transaction(function () use ($request, $publisher) {
            $entry = ContentEntry::create(['type' => 'development', 'slug' => $request->validated('slug'), 'title' => $request->validated('title'), 'author_id' => auth()->id()]);
            $publisher->save($entry, $request->validated(), 0);

            return $entry;
        });

        return redirect()->route('admin.developments.edit', $entry)->with('status', ContentPublisher::immediate() ? 'Saved. Changes are live immediately.' : 'Draft created. Add verified content before publication.');
    }

    public function update(SaveDevelopmentRequest $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless($entry->type === 'development' && $entry->slug === $request->validated('slug'), 422);
        DB::transaction(function () use ($request, $entry, $publisher) {
            $publisher->save($entry, $request->validated(), $request->integer('version'));
            $entry->update(['title' => $request->validated('title')]);
        });

        return back()->with('status', ContentPublisher::immediate() ? 'Saved. Changes are live immediately.' : 'Draft saved. The published version is unchanged.');
    }

    public function preview(ContentEntry $entry): View
    {
        abort_unless($entry->type === 'development', 404);

        return DevelopmentContent::detail($entry, $entry->revisions()->latest('version')->firstOrFail()->payload, true);
    }

    public function transition(Request $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless($entry->type === 'development', 404);
        $data = $request->validate(['action' => 'required|in:review,approve,publish,schedule,return,unpublish,archive,restore', 'version' => 'required|integer', 'scheduled_at' => 'nullable|required_if:action,schedule|date|after:now', 'note' => 'nullable|string|max:1000']);
        if (! in_array($data['action'], ['review', 'restore'])) {
            abort_unless($request->user()->can(match ($data['action']) {
                'approve', 'return' => 'pages.approve', 'unpublish' => 'pages.unpublish', 'archive' => 'pages.archive', default => 'pages.publish'
            }), 403);
        }
        $publisher->transition($entry, $data['action'], $data['version'], $data['scheduled_at'] ?? null, $data['note'] ?? null);

        return back()->with('status', 'Development publication updated.');
    }

    public function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate(['enabled' => 'required|boolean', 'version' => 'required|integer']);
        DB::transaction(function () use ($data) {
            SiteSetting::firstOrCreate(['key' => 'future_developments'], ['data' => ['enabled' => false, 'version' => 0]]);
            $setting = SiteSetting::where('key', 'future_developments')->lockForUpdate()->firstOrFail();
            if (($setting->data['version'] ?? 0) !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'Visibility changed. Reload before saving.']);
            }
            $setting->update(['data' => ['enabled' => (bool) $data['enabled'], 'version' => (int) $data['version'] + 1]]);
            app(AuditRecorder::class)->record('development.visibility_updated', $setting);
        });

        return back()->with('status', 'Public development visibility updated.');
    }

    public function space(Request $request, ContentEntry $entry, ?DevelopmentSpace $space = null): RedirectResponse
    {
        abort_unless($entry->type === 'development', 404);
        if ($space?->exists) {
            abort_unless($space->content_entry_id === $entry->id, 404);
        }
        $data = $request->validate(['version' => 'required|integer|min:0', 'kind' => 'required|in:tower,floor,unit', 'parent_id' => 'nullable|integer', 'name' => 'required|string|max:100', 'status' => ['required', Rule::in(DevelopmentSpace::STATUSES)], 'is_public' => 'required|boolean', 'verified' => $request->boolean('is_public') ? 'required|accepted' : 'nullable|boolean', 'details' => 'required|array', 'details.type' => 'nullable|string|max:100', 'details.area' => 'nullable|numeric|min:0|max:100000000', 'details.area_unit' => 'nullable|required_with:details.area|in:sq ft,sq m,acres', 'details.facing' => 'nullable|string|max:50', 'details.bedrooms' => 'nullable|integer|min:0|max:100', 'details.bathrooms' => 'nullable|integer|min:0|max:100', 'details.price' => 'nullable|numeric|min:0|max:999999999999', 'details.currency' => 'nullable|required_with:details.price|regex:/^[A-Z]{3}$/', 'details.price_public' => 'required|boolean', 'details.x' => 'nullable|numeric|min:0|max:100', 'details.y' => 'nullable|numeric|min:0|max:100', 'details.plan_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where(fn ($query) => $query->where('mime', 'like', 'image/%')->orWhere('mime', 'application/pdf'))]]);
        DB::transaction(function () use ($entry, $space, $data) {
            ContentEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $record = $space?->exists ? DevelopmentSpace::whereKey($space->id)->lockForUpdate()->firstOrFail() : new DevelopmentSpace(['content_entry_id' => $entry->id]);
            if (($record->exists ? $record->version : 0) !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'Inventory changed. Reload before saving.']);
            }
            $parent = ! empty($data['parent_id']) ? DevelopmentSpace::where('content_entry_id', $entry->id)->find($data['parent_id']) : null;
            $validParent = $data['kind'] === 'tower' ? empty($data['parent_id']) : ($parent && $parent->kind === ($data['kind'] === 'floor' ? 'tower' : 'floor'));
            if (! $validParent || ($record->exists && ($record->kind !== $data['kind'] || $record->parent_id !== ($parent?->id)))) {
                throw ValidationException::withMessages(['parent_id' => 'Use development → tower → floor → unit. Existing records cannot be moved.']);
            }
            if (DevelopmentSpace::where('content_entry_id', $entry->id)->where('parent_id', $parent?->id)->where('kind', $data['kind'])->where('name', $data['name'])->when($record->exists, fn ($query) => $query->where('id', '!=', $record->id))->exists()) {
                throw ValidationException::withMessages(['name' => 'This name already exists under the selected parent.']);
            }
            $record->fill(collect($data)->only(['kind', 'parent_id', 'name', 'status', 'is_public', 'details'])->all());
            $record->details = collect($data['details'])->only(['type', 'area', 'area_unit', 'facing', 'bedrooms', 'bathrooms', 'price', 'currency', 'price_public', 'x', 'y', 'plan_id'])->all();
            $record->version = ($record->exists ? $record->version : 0) + 1;
            $record->save();
            app(AuditRecorder::class)->record('development.inventory_updated', $record, ['after_revision' => $record->version, 'after_status' => $record->status]);
        });

        return back()->with('status', 'Inventory saved. Only approved public records and their public parents appear to visitors.');
    }
}
