<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\User;
use App\Services\AuditRecorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnquiryController extends Controller
{
    private function query(Request $request): Builder
    {
        abort_unless($request->user()->can('leads.view') || $request->user()->can('leads.view-assigned'), 403);
        $filters = $request->validate(['q' => 'nullable|string|max:150', 'status' => ['nullable', Rule::in(Enquiry::STATUSES)], 'due' => 'nullable|in:1']);

        return Enquiry::query()->when(! $request->user()->can('leads.view'), fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['due'] ?? null, fn ($query) => $query->where('follow_up_at', '<=', now())->whereNotIn('status', ['won', 'lost']))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%')->orWhere('location', 'like', '%'.$q.'%')->orWhere('type', 'like', '%'.$q.'%')));
    }

    public function index(Request $request): View
    {
        return view('admin.enquiries', ['enquiries' => $this->query($request)->with('owner')->latest()->paginate(30)->withQueryString()]);
    }

    public function show(Request $request, Enquiry $enquiry): View
    {
        abort_unless($enquiry->accessibleBy($request->user(), 'view'), 403);
        $users = $request->user()->can('leads.assign') ? User::where('is_active', true)->get()->filter(fn ($user) => $user->can('leads.view') || $user->can('leads.view-assigned')) : collect();
        $enquiry->load(['owner', 'notes.author']);

        return view('admin.enquiries.show', compact('enquiry', 'users'));
    }

    public function update(Request $request, Enquiry $enquiry, AuditRecorder $audit): RedirectResponse
    {
        abort_unless($enquiry->accessibleBy($request->user(), 'view') && $enquiry->accessibleBy($request->user(), 'edit'), 403);
        $data = $request->validate(['version' => 'required|integer', 'status' => ['required', Rule::in(Enquiry::STATUSES)], 'qualification' => 'required|in:pending,qualified,unqualified', 'follow_up_at' => 'nullable|date', 'note' => 'nullable|string|max:10000', 'assigned_to' => [$request->user()->can('leads.assign') ? 'nullable' : 'prohibited', 'integer', Rule::exists('users', 'id')->where('is_active', true)]]);
        if (! empty($data['assigned_to'])) {
            $owner = User::findOrFail($data['assigned_to']);
            if (! $owner->can('leads.view') && ! $owner->can('leads.view-assigned')) {
                throw ValidationException::withMessages(['assigned_to' => 'Choose an active user with lead access.']);
            }
        }
        DB::transaction(function () use ($request, $enquiry, $data, $audit) {
            $enquiry = Enquiry::lockForUpdate()->findOrFail($enquiry->id);
            abort_unless($enquiry->accessibleBy($request->user(), 'view') && $enquiry->accessibleBy($request->user(), 'edit'), 403);
            if ($enquiry->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'Another team member updated this lead. Reload before saving.']);
            }
            $before = $enquiry->only(['status', 'qualification', 'assigned_to', 'follow_up_at']);
            $enquiry->fill(collect($data)->except(['note', 'version'])->all());
            $enquiry->version++;
            $enquiry->save();
            $changes = [];
            foreach ($before as $key => $value) {
                if ((string) $value !== (string) $enquiry->$key) {
                    $changes[$key] = ['before' => $value, 'after' => $enquiry->$key];
                }
            }
            if ($changes || ! empty($data['note'])) {
                $enquiry->notes()->create(['user_id' => $request->user()->id, 'body' => $data['note'] ?? null, 'changes' => $changes]);
            }
            $audit->record('enquiry.updated', $enquiry, ['before_status' => $before['status'], 'after_status' => $enquiry->status, 'before_assignees' => [$before['assigned_to']], 'after_assignees' => [$enquiry->assigned_to]]);
        });

        return redirect()->route('admin.enquiries')->with('status', 'Lead updated. Notes and changes are saved in its history.');
    }

    public function export(Request $request, AuditRecorder $audit): StreamedResponse
    {
        abort_unless($request->user()->can('leads.export'), 403);
        $query = $this->query($request);
        $audit->record('enquiry.exported');

        return response()->streamDownload(function () use ($query) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['ID', 'Name', 'Email', 'Phone', 'Type', 'Location', 'Status', 'Qualification', 'Follow-up', 'Source', 'Campaign', 'Created'], ',', '"', '');
            foreach ($query->orderBy('id')->cursor() as $lead) {
                $values = [$lead->id, $lead->name, $lead->email, $lead->phone, $lead->type, $lead->location, $lead->status, $lead->qualification, $lead->follow_up_at, $lead->attribution['utm_source'] ?? '', $lead->attribution['utm_campaign'] ?? '', $lead->created_at];
                fputcsv($stream, array_map(fn ($value) => preg_match('/^[\s]*[=+@\-]/u', (string) $value) ? "'".$value : (string) $value, $values), ',', '"', '');
            }
            fclose($stream);
        }, 'enquiries.csv', ['Content-Type' => 'text/csv', 'Cache-Control' => 'private, no-store']);
    }
}
