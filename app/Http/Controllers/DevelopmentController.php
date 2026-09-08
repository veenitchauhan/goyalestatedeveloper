<?php

namespace App\Http\Controllers;

use App\Models\ContentEntry;
use App\Models\DevelopmentSpace;
use App\Models\Enquiry;
use App\Services\CorporateContent;
use App\Services\DevelopmentContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DevelopmentController extends Controller
{
    public function index(): View
    {
        abort_unless(DevelopmentContent::enabled(), 404);

        return view('developments.index', CorporateContent::layout('Developments') + ['items' => DevelopmentContent::items(), 'canonical' => route('developments.index')]);
    }

    private function entry(string $slug): ContentEntry
    {
        abort_unless(DevelopmentContent::enabled(), 404);

        return ContentEntry::where('type', 'development')->where('slug', $slug)->whereNotNull('published_revision_id')->with('publishedRevision')->firstOrFail();
    }

    public function show(string $slug): View
    {
        $entry = $this->entry($slug);

        return DevelopmentContent::detail($entry, $entry->publishedRevision->payload);
    }

    public function visit(Request $request, string $slug): RedirectResponse
    {
        $entry = $this->entry($slug);
        $data = $request->validate(['name' => 'required|string|max:150', 'email' => 'required|email|max:254', 'phone' => 'required|string|max:25', 'preferred_at' => 'required|date|after:now', 'unit_id' => 'nullable|integer', 'message' => 'nullable|string|max:5000', 'consent' => 'accepted', 'website' => 'prohibited']);
        DB::transaction(function () use ($entry, $data) {
            $entry = ContentEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            abort_unless(DevelopmentContent::enabled() && $entry->published_revision_id, 404);
            $unit = ! empty($data['unit_id']) ? DevelopmentSpace::whereKey($data['unit_id'])->lockForUpdate()->first() : null;
            if (! empty($data['unit_id'])) {
                abort_unless($unit && $unit->content_entry_id === $entry->id && $unit->kind === 'unit' && $unit->status === 'Available' && DevelopmentContent::spaces($entry)->has($unit->id), 422);
            }
            Enquiry::create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'type' => 'Development', 'location' => $entry->publishedRevision->payload['location'], 'message' => ($data['message'] ?? '') ?: 'Site visit request', 'content_entry_id' => $entry->id, 'details' => ['development' => $entry->publishedRevision->payload['title'], 'unit' => $unit?->name, 'preferred_visit' => $data['preferred_at']], 'consented_at' => now(), 'consent_version' => 'site-visit-v1', 'status' => 'new', 'attribution' => ['channel' => 'Site visit form']]);
        });

        return back()->with('status', 'Site visit request received. Our team must confirm the appointment; no unit has been reserved.');
    }
}
