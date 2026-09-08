<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\SeoPage;
use App\Models\SeoRedirect;
use App\Services\AuditRecorder;
use App\Services\SeoContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SeoController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->validate(['q' => 'nullable|string|max:150'])['q'] ?? '';
        $saved = SeoPage::all()->keyBy('path');
        $inventory = SeoContent::inventory();
        $titles = $inventory->map(fn ($item) => mb_strtolower(($saved->get($item['path'])?->data['title'] ?? '') ?: $item['title']))->countBy();
        $items = $inventory->filter(fn ($item) => ! $q || str_contains(mb_strtolower($item['title'].' '.$item['path']), mb_strtolower($q)))->map(function ($item) use ($saved, $titles) {
            $data = $saved->get($item['path'])?->data ?? [];
            $title = ($data['title'] ?? '') ?: $item['title'];
            $description = ($data['description'] ?? '') ?: $item['description'];
            $warnings = [];
            if (! $description) {
                $warnings[] = 'Missing meta description';
            }
            if (mb_strlen($title) > 70) {
                $warnings[] = 'Review long search title';
            }
            if (($titles[mb_strtolower($title)] ?? 0) > 1) {
                $warnings[] = 'Duplicate search title';
            }
            if (! ($data['focus_topic'] ?? null)) {
                $warnings[] = 'Focus topic not set';
            }
            if (! empty($item['cover_media_id']) && ! (Media::find($item['cover_media_id'])?->alt)) {
                $warnings[] = 'Review cover image alt text';
            }

            return [...$item, 'title' => $title, 'warnings' => $warnings, 'indexable' => SeoContent::globallyIndexable() && ($data['indexable'] ?? true)];
        });

        return view('admin.seo.index', ['items' => $items, 'redirects' => SeoRedirect::orderBy('source')->get(), 'destinations' => $inventory]);
    }

    public function edit(Request $request): View
    {
        $data = $request->validate(['path' => ['required', Rule::in(SeoContent::inventory()->keys()->all())]]);
        $page = SeoPage::where('path', $data['path'])->first();

        return view('admin.seo.edit', ['page' => $page, 'item' => SeoContent::inventory()->get($data['path']), 'data' => $page?->data ?? [], 'destinations' => SeoContent::inventory(), 'media' => Media::where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where('mime', 'like', 'image/%')->get()]);
    }

    public function update(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $inventory = SeoContent::inventory();
        $data = $request->validate(['path' => ['required', Rule::in($inventory->keys()->all())], 'version' => 'required|integer|min:0', 'title' => 'nullable|string|max:180', 'description' => 'nullable|string|max:300', 'focus_topic' => 'nullable|string|max:150', 'og_title' => 'nullable|string|max:180', 'og_description' => 'nullable|string|max:300', 'canonical_path' => ['nullable', Rule::in($inventory->keys()->all())], 'indexable' => 'required|boolean', 'schema_enabled' => 'required|boolean', 'og_image_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where('is_public', true)->where('publication_status', 'published')->whereNull('archived_at')->where('mime', 'like', 'image/%')]]);
        DB::transaction(function () use ($data, $audit) {
            SeoPage::firstOrCreate(['path' => $data['path']], ['data' => [], 'version' => 0]);
            $page = SeoPage::where('path', $data['path'])->lockForUpdate()->firstOrFail();
            if ($page->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'SEO settings changed. Reload before saving.']);
            }
            if (! empty($data['canonical_path']) && $data['canonical_path'] !== $data['path']) {
                $target = SeoPage::where('path', $data['canonical_path'])->first()?->data ?? [];
                if (! ($target['indexable'] ?? true) || (! empty($target['canonical_path']) && $target['canonical_path'] !== $data['canonical_path'])) {
                    throw ValidationException::withMessages(['canonical_path' => 'Choose an indexable page with its own canonical URL.']);
                }
            }
            $page->update(['data' => collect($data)->except(['path', 'version'])->all(), 'version' => $page->version + 1]);
            $audit->record('seo.updated', $page, ['after_revision' => $page->version]);
        });

        return back()->with('status', 'SEO settings saved for the published page. Local indexing remains disabled.');
    }

    public function redirect(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $inventory = SeoContent::inventory();
        $data = $request->validate(['source' => ['required', 'string', 'max:255', 'regex:~^/[a-z0-9_-]+(?:/[a-z0-9_-]+)*$~', Rule::notIn($inventory->keys()->all()), Rule::unique('seo_redirects', 'source')], 'destination' => ['required', Rule::in($inventory->keys()->all())], 'status' => ['required', Rule::in([301, 302])]]);
        if (preg_match('~^/(admin|integrations|assets|media|storage|login|logout|user|privacy-preferences|search)(/|$)~', $data['source'])) {
            throw ValidationException::withMessages(['source' => 'System and private routes cannot be redirected here.']);
        }
        $redirect = SeoRedirect::create($data);
        $audit->record('seo.redirect_created', $redirect);

        return back()->with('status', 'Redirect added.');
    }

    public function deleteRedirect(SeoRedirect $redirect, AuditRecorder $audit): RedirectResponse
    {
        $audit->record('seo.redirect_deleted', $redirect);
        $redirect->delete();

        return back()->with('status', 'Redirect removed.');
    }
}
