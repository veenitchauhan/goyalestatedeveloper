<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaRequest;
use App\Models\ContentEntry;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Services\AuditRecorder;
use App\Services\MediaImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:80', 'location' => 'nullable|string|max:100', 'project' => 'nullable|string|max:100', 'mime' => 'nullable|string|max:100', 'date' => 'nullable|date', 'uploaded_by' => 'nullable|integer']);
        $query = Media::query()->when(! $request->boolean('archived'), fn ($q) => $q->whereNull('archived_at'));
        if ($filters['q'] ?? null) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%'.$filters['q'].'%')->orWhere('alt', 'like', '%'.$filters['q'].'%');
            });
        }
        foreach (['category', 'location', 'project', 'mime', 'uploaded_by'] as $key) {
            if ($filters[$key] ?? null) {
                $query->where($key, $filters[$key]);
            }
        }
        if ($filters['date'] ?? null) {
            $query->whereDate('created_at', $filters['date']);
        }

        return view('admin.media.index', ['items' => $query->orderBy('sort_order')->latest()->paginate(24)->withQueryString()]);
    }

    public function edit(Media $media): View
    {
        return view('admin.media.edit', compact('media'));
    }

    public function create(): View
    {
        return view('admin.media.edit', ['media' => new Media(['category' => 'Company', 'sort_order' => 0, 'is_public' => false, 'publication_status' => 'draft', 'watermark' => SiteSetting::current()['watermark']])]);
    }

    public function store(StoreMediaRequest $request, AuditRecorder $audit): RedirectResponse|JsonResponse
    {
        $file = $request->file('file');
        $data = $request->safe()->except(['file', 'project_entry_id']);
        abort_if(($request->boolean('is_public') || $request->input('publication_status') === 'published') && ! $request->user()->can('media.publish'), 403);
        $project = $request->filled('project_entry_id') ? ContentEntry::where('type', 'project')->findOrFail($request->integer('project_entry_id')) : null;
        if ($project) {
            Gate::authorize('update', $project);
            $data['project'] = $project->slug;
        }
        $mime = $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'video/mp4'])) {
            throw ValidationException::withMessages(['file' => 'Unsupported file contents.']);
        }
        if (str_starts_with($mime, 'image/')) {
            app(MediaImages::class)->checkImage($file->getPathname(), $data['alt'] ?? '');
        }
        $path = $file->store('media/originals', 'local');
        $media = new Media([...$data, 'original_name' => $file->getClientOriginalName(), 'original_path' => $path, 'mime' => $mime, 'uploaded_by' => auth()->id()]);
        try {
            app(MediaImages::class)->derive($media);
            $media->save();
        } catch (\Throwable $e) {
            Storage::disk('local')->delete(array_filter([$path, $media->web_path]));
            throw $e;
        }
        $audit->record('media.uploaded', $media);

        if ($request->expectsJson()) {
            return response()->json(['id' => $media->id, 'title' => $media->title, 'mime' => $media->mime, 'selectable' => $media->is_public && $media->publication_status === 'published'], 201);
        }
        if ($project) {
            return redirect()->route('admin.projects.edit', $project)->with('status', 'Media uploaded. Select the approved file in the project gallery and save your draft.');
        }

        return redirect()->route('admin.media.edit', $media)->with('status', 'Media uploaded. The original is preserved privately.');
    }

    public function update(StoreMediaRequest $request, Media $media, AuditRecorder $audit): RedirectResponse
    {
        abort_if($media->archived_at, 422, 'Restore archived media before editing.');
        $data = $request->safe()->except(['file', 'project_entry_id']);
        if (str_starts_with($media->mime, 'image/')) {
            app(MediaImages::class)->checkImage(Storage::disk('local')->path($media->original_path), $data['alt'] ?? '');
        }
        abort_if(($request->boolean('is_public') !== $media->is_public || $request->input('publication_status') !== $media->publication_status) && ! $request->user()->can('media.publish'), 403);
        $changes = ['before_title' => $media->title, 'before_public' => $media->is_public, 'before_order' => $media->sort_order, 'before_alt' => $media->alt, 'before_watermark' => $media->watermark];
        $oldPath = $media->web_path;
        $media->fill($data);
        app(MediaImages::class)->derive($media);
        $media->save();
        if ($oldPath && $oldPath !== $media->web_path) {
            Storage::disk('local')->delete($oldPath);
        }
        $audit->record('media.updated', $media, [...$changes, 'after_title' => $media->title, 'after_public' => $media->is_public, 'after_order' => $media->sort_order, 'after_alt' => $media->alt, 'after_watermark' => $media->watermark]);

        return back()->with('status', 'Media settings saved; the web version was regenerated from the original.');
    }

    public function archive(Request $request, Media $media, AuditRecorder $audit): RedirectResponse
    {
        $request->validate(['action' => 'required|in:archive,restore']);
        abort_if($media->is_public && ! $request->user()->can('media.publish'), 403);
        $before = $media->archived_at ? 'archived' : $media->publication_status;
        $media->update(['archived_at' => $request->input('action') === 'archive' ? now() : null, 'publication_status' => 'draft']);
        $audit->record('media.'.$request->input('action'), $media, ['before_status' => $before, 'after_status' => $media->archived_at ? 'archived' : 'draft']);

        return back()->with('status', 'Media status updated. Restored files remain drafts until published.');
    }

    public function original(Media $media): StreamedResponse
    {
        return Storage::disk('local')->download($media->original_path, $media->original_name, ['Content-Type' => 'application/octet-stream']);
    }

    public function show(Request $request, Media $media): StreamedResponse
    {
        abort_unless(($media->is_public && $media->publication_status === 'published' && ! $media->archived_at) || ($request->user()?->can('media.manage') || $request->user()?->can('pages.edit')), 404);
        $path = $media->web_path ?? $media->original_path;
        if ($media->mime === 'application/pdf') {
            return Storage::disk('local')->download($path, 'document-'.$media->id.'.pdf', ['Content-Type' => 'application/pdf']);
        }

        return Storage::disk('local')->response($path, null, ['Content-Type' => $media->web_path ? 'image/webp' : $media->mime]);
    }
}
