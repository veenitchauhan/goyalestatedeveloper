<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['resume_path'];

    protected function casts(): array
    {
        return ['profile' => 'array', 'consented_at' => 'datetime', 'interview_at' => 'datetime'];
    }

    public const STATUSES = ['New', 'Screening', 'Shortlisted', 'Interview', 'Second Interview', 'Selected', 'Rejected', 'On Hold', 'Joined'];

    public function accessibleBy(User $user, string $action): bool
    {
        return $user->is_active && ($user->can('candidates.'.$action) || ($user->can('candidates.'.$action.'-assigned') && $this->assigned_to === $user->id));
    }
}
