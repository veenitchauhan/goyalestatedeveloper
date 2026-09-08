<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enquiry extends Model
{
    use HasFactory;

    public const STATUSES = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost', 'follow-up', 'on-hold'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime', 'follow_up_at' => 'datetime', 'notified_at' => 'datetime', 'details' => 'array', 'attribution' => 'array'];
    }

    public function accessibleBy(User $user, string $action): bool
    {
        return $user->is_active && ($user->can('leads.'.$action) || ($user->can('leads.'.$action.'-assigned') && $this->assigned_to === $user->id));
    }

    public function notes(): HasMany
    {
        return $this->hasMany(EnquiryNote::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
