<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditRecorder
{
    /** @param array<string, mixed> $changes */
    public function record(string $action, ?Model $subject = null, array $changes = [], ?int $actorId = null): void
    {
        AuditLog::create(['actor_id' => $actorId ?? auth()->id(), 'action' => $action,
            'subject_type' => $subject?->getMorphClass(), 'subject_id' => $subject?->getKey(),
            'changes' => array_intersect_key($changes, array_flip(['before_roles', 'after_roles', 'before_active', 'after_active', 'before_label', 'after_label', 'before_permissions', 'after_permissions', 'before_status', 'after_status', 'before_revision', 'after_revision', 'before_title', 'after_title', 'before_public', 'after_public', 'before_order', 'after_order', 'before_alt', 'after_alt', 'before_watermark', 'after_watermark'])), 'created_at' => now()]);
    }
}
