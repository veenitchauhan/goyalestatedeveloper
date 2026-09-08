<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Models\Candidate;
use App\Models\ContentEntry;
use App\Services\CareerContent;
use App\Services\CorporateContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CareerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['department' => 'nullable|string|max:255', 'location' => 'nullable|string|max:255', 'experience' => 'nullable|string|max:255', 'employment_type' => 'nullable|string|max:60', 'job_type' => 'nullable|string|max:60']);
        $all = CareerContent::openings();
        $items = $all->filter(fn (array $job) => collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->every(fn ($value, $key) => ($job[$key] ?? '') === $value));

        return view('careers.index', CorporateContent::layout('Build your career with us') + compact('items', 'all', 'filters') + ['canonical' => route('careers.index'), 'stories' => CorporateContent::items('employee_story')]);
    }

    public function show(string $slug): View
    {
        $entry = ContentEntry::where('type', 'job')->where('slug', $slug)->whereNotNull('published_revision_id')->with('publishedRevision')->firstOrFail();
        abort_unless(CareerContent::openings()->contains('id', $entry->id), 404);

        return CareerContent::detail($entry, $entry->publishedRevision->payload);
    }

    public function apply(StoreApplicationRequest $request, ?string $slug = null): RedirectResponse
    {
        $file = $request->file('resume');
        $handle = fopen($file->getPathname(), 'rb');
        $signature = fread($handle, 5);
        fclose($handle);
        if ($file->getMimeType() !== 'application/pdf' || $signature !== '%PDF-') {
            throw ValidationException::withMessages(['resume' => 'Upload a valid PDF résumé.']);
        }
        $path = null;
        try {
            DB::transaction(function () use ($request, $slug, $file, &$path) {
                $entry = $slug ? ContentEntry::where('type', 'job')->where('slug', $slug)->lockForUpdate()->firstOrFail() : null;
                if ($entry) {
                    abort_unless($entry->published_revision_id, 410, 'This opening is no longer accepting applications.');
                    $job = $entry->publishedRevision->payload;
                    abort_if(! empty($job['deadline']) && $job['deadline'] < now()->toDateString(), 410, 'The application deadline has passed.');
                }
                $path = $file->store('recruitment/resumes', 'local');
                Candidate::create(['content_entry_id' => $entry?->id, 'job_title' => $job['title'] ?? 'General application', 'name' => $request->validated('name'), 'email' => strtolower($request->validated('email')), 'phone' => $request->validated('phone'), 'profile' => $request->safe()->except(['resume', 'name', 'email', 'phone', 'consent', 'website']), 'resume_path' => $path, 'consented_at' => now()]);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return redirect()->route('careers.index')->with('application_received', true);
    }
}
