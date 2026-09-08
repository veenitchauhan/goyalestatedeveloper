<?php

namespace App\Policies;

use App\Models\ContentEntry;
use App\Models\User;

class ContentEntryPolicy
{
    private function permitted(User $user, ContentEntry $entry, string $action): bool
    {
        $domain = match ($entry->type) {
            'project' => 'projects', 'job' => 'jobs', 'page','article','knowledge','faq','company_page','business_unit','service','capability','equipment','team_member','company_milestone','employee_story' => 'pages', default => null
        };
        if (! $domain || ! $user->is_active) {
            return false;
        }

        return $user->hasPermission($domain.'.'.$action) || ($user->hasPermission($domain.'.'.$action.'-assigned') && $entry->assignees()->where('users.id', $user->id)->exists());
    }

    public function view(User $user, ContentEntry $entry): bool
    {
        return $this->permitted($user, $entry, 'view');
    }

    public function update(User $user, ContentEntry $entry): bool
    {
        return $this->permitted($user, $entry, 'edit');
    }

    public function publish(User $user, ContentEntry $entry): bool
    {
        return $this->permitted($user, $entry, 'publish');
    }
}
