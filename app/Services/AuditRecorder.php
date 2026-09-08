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
            'changes' => array_intersect_key($changes, array_flip(['before_roles', 'after_roles', 'before_active', 'after_active'])), 'created_at' => now()]);
    }
}
