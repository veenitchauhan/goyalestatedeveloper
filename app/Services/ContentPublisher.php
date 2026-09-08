<?php

namespace App\Services;

use App\Models\ContentEntry;
use App\Models\ContentRevision;
use App\Models\Homepage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentPublisher
{
    public function save(ContentEntry $entry, array $payload, int $expectedVersion): ContentRevision
    {
        return DB::transaction(function () use ($entry, $payload, $expectedVersion) {
            $entry = ContentEntry::lockForUpdate()->findOrFail($entry->id);
            $version = (int) $entry->revisions()->max('version');
            if ($version !== $expectedVersion) {
                throw ValidationException::withMessages(['version' => 'Someone saved a newer version. Reload the editor before saving.']);
            }
            $revision = $entry->revisions()->create(['version' => $version + 1, 'payload' => $payload, 'author_id' => auth()->id()]);
            $entry->update(['status' => 'draft', 'scheduled_at' => null, 'scheduled_revision_id' => null]);
            app(AuditRecorder::class)->record('content.draft_saved', $entry);

            return $revision;
        });
    }

    public function transition(ContentEntry $entry, string $action, int $version, ?string $scheduledAt = null, ?string $note = null): void
    {
        DB::transaction(function () use ($entry, $action, $version, $scheduledAt, $note) {
            $entry = ContentEntry::lockForUpdate()->findOrFail($entry->id);
            $revision = $entry->revisions()->latest('version')->firstOrFail();
            if ($revision->version !== $version) {
                throw ValidationException::withMessages(['version' => 'The draft has changed. Review the latest version first.']);
            }
            if ($action === 'publish') {
                $this->apply($entry, $revision);
            } elseif ($action === 'schedule') {
                $entry->update(['status' => 'scheduled', 'scheduled_at' => $scheduledAt, 'scheduled_revision_id' => $revision->id, 'approver_id' => auth()->id()]);
            } elseif ($action === 'unpublish') {
                if ($entry->type === 'homepage') {
                    throw ValidationException::withMessages(['action' => 'The homepage must remain available. Publish a revised version instead.']);
                }
                $entry->update(['published_revision_id' => null, 'published_at' => null, 'status' => 'draft', 'scheduled_at' => null, 'scheduled_revision_id' => null]);
            } else {
                $entry->update(['status' => $action === 'review' ? 'review' : 'draft', 'scheduled_at' => null, 'scheduled_revision_id' => null]);
            }
            DB::table('approval_events')->insert(['content_revision_id' => $revision->id, 'actor_id' => auth()->id(), 'action' => $action, 'note' => $note, 'created_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record('content.'.$action, $entry);
        });
    }

    public function apply(ContentEntry $entry, ContentRevision $revision): void
    {
        if ($entry->type === 'homepage') {
            Homepage::main()->update(['content' => $revision->payload]);
        }
        $entry->update(['published_revision_id' => $revision->id, 'published_at' => now(), 'status' => 'published', 'scheduled_at' => null, 'scheduled_revision_id' => null, 'approver_id' => auth()->id() ?? $entry->approver_id]);
    }

    public function publishDue(): int
    {
        $count = 0;
        foreach (ContentEntry::where('status', 'scheduled')->where('scheduled_at', '<=', now())->pluck('id') as $id) {
            $count += DB::transaction(function () use ($id) {
                $entry = ContentEntry::lockForUpdate()->findOrFail($id);
                if ($entry->status !== 'scheduled' || ! $entry->scheduled_at?->isPast()) {
                    return 0;
                }
                $revision = $entry->revisions()->findOrFail($entry->scheduled_revision_id);
                $this->apply($entry, $revision);
                DB::table('approval_events')->insert(['content_revision_id' => $revision->id, 'action' => 'scheduled_publish', 'created_at' => now(), 'updated_at' => now()]);
                app(AuditRecorder::class)->record('content.scheduled_publish', $entry);

                return 1;
            });
        }

        return $count;
    }
}
