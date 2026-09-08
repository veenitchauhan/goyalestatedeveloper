<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\User;
use App\Services\AuditRecorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateController extends Controller
{
    private function query(Request $request): Builder
    {
        abort_unless($request->user()->can('candidates.view') || $request->user()->can('candidates.view-assigned'), 403);
        $filters = $request->validate(['q' => 'nullable|string|max:150', 'status' => ['nullable', Rule::in(Candidate::STATUSES)]]);

        return Candidate::query()->when(! $request->user()->can('candidates.view'), fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('job_title', 'like', '%'.$search.'%')));
    }

    public function index(Request $request): View
    {
        return view('admin.candidates.index', ['candidates' => $this->query($request)->latest()->paginate(30)->withQueryString()]);
    }

    public function show(Request $request, Candidate $candidate): View
    {
        abort_unless($candidate->accessibleBy($request->user(), 'view'), 403);
        $users = $request->user()->can('candidates.edit') ? User::where('is_active', true)->whereHas('roles.permissions', fn ($q) => $q->whereIn('name', ['candidates.view', 'candidates.view-assigned']))->orderBy('name')->get() : collect();

        return view('admin.candidates.show', compact('candidate', 'users'));
    }

    public function update(Request $request, Candidate $candidate, AuditRecorder $audit): RedirectResponse
    {
        abort_unless($candidate->accessibleBy($request->user(), 'edit'), 403);
        $data = $request->validate(['version' => 'required|integer', 'status' => ['required', Rule::in(Candidate::STATUSES)], 'internal_notes' => 'nullable|string|max:15000', 'interview_notes' => 'nullable|string|max:15000', 'interview_at' => 'nullable|date', 'assigned_to' => [$request->user()->can('candidates.edit') ? 'nullable' : 'prohibited', 'integer', Rule::exists('users', 'id')->where('is_active', true)]]);
        if (! empty($data['assigned_to'])) {
            $assigned = User::findOrFail($data['assigned_to']);
            abort_unless($assigned->can('candidates.view') || $assigned->can('candidates.view-assigned'), 422);
        }
        DB::transaction(function () use ($request, $candidate, $data, $audit) {
            $candidate = Candidate::lockForUpdate()->findOrFail($candidate->id);
            abort_unless($candidate->accessibleBy($request->user(), 'edit'), 403);
            if ($candidate->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'Another HR user updated this application. Reload before saving.']);
            }
            $before = $candidate->status;
            $assignment = $candidate->assigned_to;
            $candidate->fill($data);
            $candidate->version++;
            $candidate->save();
            $audit->record('candidate.updated', $candidate, ['before_status' => $before, 'after_status' => $candidate->status, 'before_assignees' => [$assignment], 'after_assignees' => [$candidate->assigned_to]]);
        });

        return back()->with('status', 'Candidate record updated.');
    }

    public function resume(Request $request, Candidate $candidate, AuditRecorder $audit): StreamedResponse
    {
        abort_unless($candidate->accessibleBy($request->user(), 'view'), 403);
        abort_unless(Storage::disk('local')->exists($candidate->resume_path), 404);
        $audit->record('candidate.resume_downloaded', $candidate);

        return Storage::disk('local')->download($candidate->resume_path, 'resume-'.$candidate->id.'.pdf', ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function export(Request $request, AuditRecorder $audit): StreamedResponse
    {
        abort_unless($request->user()->can('candidates.export'), 403);
        $query = $this->query($request);
        $audit->record('candidate.exported');

        return response()->streamDownload(function () use ($query) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['ID', 'Name', 'Email', 'Phone', 'Opening', 'Status', 'Applied'], ',', '"', '');
            foreach ($query->orderBy('id')->cursor() as $candidate) {
                $values = [$candidate->id, $candidate->name, $candidate->email, $candidate->phone, $candidate->job_title, $candidate->status, $candidate->created_at];
                $values = array_map(fn ($value) => preg_match('/^[\s]*[=+@\-]/u', (string) $value) ? "'".$value : (string) $value, $values);
                fputcsv($stream, $values, ',', '"', '');
            }
            fclose($stream);
        }, 'candidates.csv', ['Content-Type' => 'text/csv', 'Cache-Control' => 'private, no-store']);
    }
}
