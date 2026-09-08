<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_editor_is_scoped_to_assigned_projects_and_cannot_publish(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $editor = User::factory()->create();
        $editor->roles()->attach(Role::where('name', 'project-editor')->firstOrFail());
        $assigned = ContentEntry::factory()->create();
        $other = ContentEntry::factory()->create();
        $assigned->assignees()->attach($editor);
        $this->assertTrue($editor->can('view', $assigned));
        $this->assertTrue($editor->can('update', $assigned));
        $this->assertFalse($editor->can('view', $other));
        $this->assertFalse($editor->can('update', $other));
        $this->assertFalse($editor->can('publish', $assigned));
        $editor->forceFill(['is_active' => false])->save();
        $this->assertFalse($editor->can('update', $assigned));
    }

    public function test_unrecognized_content_type_is_denied_even_to_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $entry = ContentEntry::factory()->create(['type' => 'private-candidate']);
        $this->assertFalse($user->can('view', $entry));
    }
}
