<?php

namespace App\Services;

use App\Models\ContentEntry;
use App\Models\ContentRevision;
use App\Models\Homepage;
use App\Models\SiteSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ContentPublisher
{
    public static function immediate(): bool
    {
        $user = auth()->user();

        return $user && $user->is_active && ($user->hasRole('super-admin') || $user->hasRole('admin'));
    }

    public function save(ContentEntry $entry, array $payload, int $expectedVersion): ContentRevision
    {
        return DB::transaction(function () use ($entry, $payload, $expectedVersion) {
            $entry = ContentEntry::lockForUpdate()->findOrFail($entry->id);
            $version = (int) $entry->revisions()->max('version');
            if ($version !== $expectedVersion) {
                throw ValidationException::withMessages(['version' => 'Someone saved a newer version. Reload the editor before saving.']);
            }
            if ($entry->status === 'archived') {
                throw ValidationException::withMessages(['status' => 'Restore this archived record before editing it.']);
            }
            $beforePayload = $entry->revisions()->latest('version')->first()?->payload ?? [];
            $revision = $entry->revisions()->create(['version' => $version + 1, 'payload' => $payload, 'author_id' => auth()->id()]);
            $entry->update(['status' => 'draft', 'approver_id' => null, 'scheduled_at' => null, 'scheduled_revision_id' => null]);
            app(AuditRecorder::class)->record('content.draft_saved', $entry, ['before_revision' => $version, 'after_revision' => $revision->version, 'content_diff' => $this->difference($beforePayload, $payload)]);

            if (static::immediate()) {
                $this->apply($entry, $revision);
                app(AuditRecorder::class)->record('content.published_on_save', $entry, ['revision' => $revision->version]);
            }

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
            $beforeStatus = $entry->status;
            $beforeRevision = $entry->publishedRevision?->version;
            $allowed = [
                'review' => ['draft', 'unpublished'], 'approve' => ['review'], 'publish' => ['approved'],
                'schedule' => ['approved'], 'return' => ['review', 'approved', 'scheduled'],
                'unpublish' => ['published', 'draft', 'review', 'approved', 'scheduled'],
                'archive' => ['draft', 'review', 'approved', 'scheduled', 'published', 'unpublished'], 'restore' => ['archived'],
            ];
            if (static::immediate()) {
                $allowed['publish'] = ['draft', 'review', 'approved', 'scheduled', 'unpublished', 'published'];
            }
            if (! in_array($entry->status, $allowed[$action] ?? [])) {
                throw ValidationException::withMessages(['action' => 'This action is not available for the current status. Reload and follow draft → review → approval → publication.']);
            }
            if ($action === 'approve') {
                $entry->update(['status' => 'approved', 'approver_id' => auth()->id()]);
            } elseif ($action === 'publish') {
                $this->apply($entry, $revision);
            } elseif ($action === 'schedule') {
                $entry->update(['status' => 'scheduled', 'scheduled_at' => $scheduledAt, 'scheduled_revision_id' => $revision->id]);
            } elseif ($action === 'unpublish') {
                if (in_array($entry->type, ['homepage', 'settings'])) {
                    throw ValidationException::withMessages(['action' => 'The homepage must remain available. Publish a revised version instead.']);
                }
                $entry->update(['published_revision_id' => null, 'published_at' => null, 'status' => 'unpublished', 'scheduled_at' => null, 'scheduled_revision_id' => null]);
            } elseif ($action === 'archive') {
                if (in_array($entry->type, ['homepage', 'settings'])) {
                    throw ValidationException::withMessages(['action' => 'The homepage and global settings must remain available.']);
                }
                $entry->update(['status' => 'archived', 'published_revision_id' => null, 'published_at' => null, 'scheduled_at' => null, 'scheduled_revision_id' => null]);
            } else {
                $entry->update(['status' => $action === 'review' ? 'review' : 'draft', 'scheduled_at' => null, 'scheduled_revision_id' => null]);
            }
            DB::table('approval_events')->insert(['content_revision_id' => $revision->id, 'actor_id' => auth()->id(), 'action' => $action, 'note' => $note, 'created_at' => now(), 'updated_at' => now()]);
            app(AuditRecorder::class)->record('content.'.$action, $entry, ['before_status' => $beforeStatus, 'after_status' => $entry->status, 'before_revision' => $beforeRevision, 'after_revision' => $revision->version]);
        });
    }

    public function apply(ContentEntry $entry, ContentRevision $revision): void
    {
        if ($entry->type === 'development') {
            Validator::make($revision->payload, DevelopmentContent::rules($entry, true))->validate();
        }
        if ($entry->type === 'campaign') {
            Validator::make($revision->payload, CampaignContent::rules($entry, true))->validate();
        }
        if (KnowledgeContent::supports($entry->type)) {
            Validator::make($revision->payload, KnowledgeContent::rules($entry, true))->validate();
        }
        if ($entry->type === 'job') {
            Validator::make($revision->payload, CareerContent::rules($entry, true))->validate();
        }
        if ($entry->type === 'location') {
            Validator::make($revision->payload, LocationContent::rules($entry, $revision->payload, true))->validate();
        }
        if ($entry->type === 'project') {
            Validator::make($revision->payload, ProjectContent::rules($entry, true, $revision->payload))->validate();
        }
        if (CorporateContent::supports($entry->type)) {
            app(CorporateContent::class)->apply($entry, $revision);
        }
        if ($entry->type === 'settings') {
            SiteSetting::where('key', 'global')->firstOrFail()->update(['data' => $revision->payload]);
        }
        if ($entry->type === 'homepage') {
            Homepage::main()->update(['content' => $revision->payload]);
        }
        $entry->update(['published_revision_id' => $revision->id, 'published_at' => now(), 'status' => 'published', 'scheduled_at' => null, 'scheduled_revision_id' => null]);
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
                try {
                    $this->apply($entry, $revision);
                } catch (ValidationException $exception) {
                    $entry->update(['status' => 'draft', 'approver_id' => null, 'scheduled_at' => null, 'scheduled_revision_id' => null]);
                    DB::table('approval_events')->insert(['content_revision_id' => $revision->id, 'action' => 'publication_blocked', 'note' => implode(' ', $exception->validator->errors()->all()), 'created_at' => now(), 'updated_at' => now()]);
                    app(AuditRecorder::class)->record('content.publication_blocked', $entry);

                    return 0;
                }
                DB::table('approval_events')->insert(['content_revision_id' => $revision->id, 'action' => 'scheduled_publish', 'created_at' => now(), 'updated_at' => now()]);
                app(AuditRecorder::class)->record('content.scheduled_publish', $entry);

                return 1;
            });
        }

        return $count;
    }

    private function difference(array $before, array $after): array
    {
        $changes = [];
        $left = Arr::dot($before);
        $right = Arr::dot($after);
        foreach (array_unique([...array_keys($left), ...array_keys($right)]) as $key) {
            if (preg_match('/password|secret|token|recovery/i', $key)) {
                continue;
            }
            if (($left[$key] ?? null) !== ($right[$key] ?? null)) {
                $changes[] = ['field' => $key, 'before' => $left[$key] ?? null, 'after' => $right[$key] ?? null];
            }
        }

        return $changes;
    }
}
