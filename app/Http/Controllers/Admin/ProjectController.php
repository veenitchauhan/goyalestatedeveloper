<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveProjectRequest;
use App\Models\ContentEntry;
use App\Models\Media;
use App\Models\User;
use App\Services\AuditRecorder;
use App\Services\ContentPublisher;
use App\Services\ProjectContent;
use App\Services\ProjectImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('projects.view') || $request->user()->can('projects.view-assigned'), 403);
        $entries = ContentEntry::where('type', 'project')->when(! $request->user()->can('projects.view'), fn ($q) => $q->whereHas('assignees', fn ($q) => $q->where('users.id', $request->user()->id)))->with('publishedRevision')->latest()->paginate(25);

        return view('admin.projects.index', compact('entries'));
    }

    public function create(): View
    {
        Gate::authorize('projects.edit');

        return $this->editor(new ContentEntry(['type' => 'project']));
    }

    public function edit(ContentEntry $entry): View
    {
        abort_unless($entry->type === 'project', 404);
        Gate::authorize('update', $entry);

        return $this->editor($entry);
    }

    private function editor(ContentEntry $entry): View
    {
        $revision = $entry->exists ? $entry->revisions()->latest('version')->firstOrFail() : null;
        $payload = $revision?->payload ?? ['status' => 'Upcoming', 'sector' => 'Buildings', 'stage' => 'Planning', 'progress' => 0, 'featured' => false, 'client_approved' => false, 'value_approved' => false, 'progress_date' => now()->toDateString()];

        return view('admin.projects.edit', compact('entry', 'revision', 'payload') + [
            'media' => Media::where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->orderBy('title')->get(),
            'equipment' => ContentEntry::publishedItems('equipment'),
            'projectImages' => Media::whereIn('id', ProjectImages::selectedIds($payload))->get()->sortBy(fn ($media) => array_search($media->id, ProjectImages::selectedIds($payload))),
            'users' => auth()->user()->can('projects.publish') ? User::where('is_active', true)->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(SaveProjectRequest $request, ContentPublisher $publisher): RedirectResponse
    {
        $createdPaths = [];
        try {
            $entry = DB::transaction(function () use ($request, $publisher, &$createdPaths) {
                $entry = ContentEntry::create(['type' => 'project', 'slug' => $request->validated('slug'), 'title' => $request->validated('title'), 'author_id' => auth()->id()]);
                $payload = app(ProjectImages::class)->apply($request, $entry, Arr::except($request->validated(), ['images', 'keep_images', 'image_selection']), [], $createdPaths);
                $publisher->save($entry, $payload, 0);

                return $entry;
            });

        } catch (\Throwable $error) {
            Storage::disk('local')->delete(array_filter($createdPaths));
            throw $error;
        }

        return redirect()->route('admin.projects.edit', $entry)->with('status', 'Project draft created. Add approved facts and media before publication.');
    }

    public function update(SaveProjectRequest $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless($entry->type === 'project' && $entry->slug === $request->validated('slug'), 422);
        $createdPaths = [];
        try {
            DB::transaction(function () use ($request, $entry, $publisher, &$createdPaths) {
                $payload = Arr::except($request->validated(), ['images', 'keep_images', 'image_selection']);
                $previous = $entry->revisions()->latest('version')->firstOrFail()->payload;
                foreach (['manager', 'location_entry_id', 'expected_completion', 'cover_media_id', 'panorama_media_id', 'panorama_caption', 'before_media_id', 'after_media_id', 'equipment_ids', 'related_ids', 'document_ids', 'timeline'] as $field) {
                    if (! $request->has($field) && array_key_exists($field, $previous)) {
                        $payload[$field] = $previous[$field];
                    }
                }
                $payload = app(ProjectImages::class)->apply($request, $entry, $payload, $previous, $createdPaths);
                $publisher->save($entry, $payload, $request->integer('version'));
                $entry->update(['title' => $request->validated('title')]);
            });

        } catch (\Throwable $error) {
            Storage::disk('local')->delete(array_filter($createdPaths));
            throw $error;
        }

        return back()->with('status', 'Project draft saved. The published project is unchanged.');
    }

    public function transition(Request $request, ContentEntry $entry, ContentPublisher $publisher): RedirectResponse
    {
        abort_unless($entry->type === 'project', 404);
        $data = $request->validate(['action' => ['required', Rule::in(['review', 'approve', 'publish', 'schedule', 'return', 'unpublish', 'archive', 'restore'])], 'version' => 'required|integer', 'scheduled_at' => 'nullable|required_if:action,schedule|date|after:now', 'note' => 'nullable|string|max:1000']);
        Gate::authorize(in_array($data['action'], ['review']) ? 'update' : 'publish', $entry);
        $publisher->transition($entry, $data['action'], $data['version'], $data['scheduled_at'] ?? null, $data['note'] ?? null);

        return back()->with('status', 'Project publication status updated.');
    }

    public function preview(ContentEntry $entry): View
    {
        abort_unless($entry->type === 'project', 404);
        Gate::authorize('view', $entry);

        return ProjectContent::detail($entry, $entry->revisions()->latest('version')->firstOrFail()->payload, true);
    }

    public function assign(Request $request, ContentEntry $entry, AuditRecorder $audit): RedirectResponse
    {
        abort_unless($entry->type === 'project', 404);
        Gate::authorize('publish', $entry);
        $data = $request->validate(['assignees' => 'sometimes|array|max:30', 'assignees.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('is_active', true)]]);
        DB::transaction(function () use ($entry, $data, $audit) {
            $before = $entry->assignees()->pluck('users.id')->all();
            $entry->assignees()->sync($data['assignees'] ?? []);
            $audit->record('project.assignment_updated', $entry, ['before_assignees' => $before, 'after_assignees' => $data['assignees'] ?? []]);
        });

        return back()->with('status', 'Project assignments updated.');
    }
}
