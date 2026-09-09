<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ContentEntry;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use App\Services\ContentPublisher;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, HomepageSeeder::class]);
    }

    private function login(string $role = 'super-admin'): User
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['version' => 0, 'title' => 'Verified test project', 'slug' => 'verified-test-project', 'project_type' => 'Civil works', 'status' => 'Ongoing', 'sector' => 'Buildings', 'stage' => 'Structure', 'progress' => 72, 'featured' => false, 'client_approved' => false, 'value_approved' => false, 'verified' => true, 'source_note' => 'Test-only approved specification', 'city' => 'Test city', 'progress_date' => now()->toDateString(), 'description' => 'Verified project description for automated tests.', 'scope' => 'Approved civil works for this test project.', 'client' => 'Confidential client', 'project_value' => 'Confidential value', 'timeline' => [['name' => 'Foundation', 'progress' => 100, 'milestone' => 'Foundation complete']], 'faqs' => [['question' => 'What is the scope?', 'answer' => 'Civil works.']]], $overrides);
    }

    private function createProject(array $overrides = []): ContentEntry
    {
        $payload = $this->payload($overrides);
        $this->post('/admin/projects', $payload)->assertSessionHasNoErrors()->assertRedirect();

        return ContentEntry::where('slug', $payload['slug'])->firstOrFail();
    }

    private function publish(ContentEntry $entry, int $version = 1): void
    {
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post('/admin/projects/'.$entry->id.'/status', compact('action', 'version'))->assertSessionHasNoErrors();
        }
    }

    public function test_project_workflow_preserves_live_revision_and_hides_unapproved_facts(): void
    {
        $this->login();
        $entry = $this->createProject();
        $this->get('/projects/'.$entry->slug)->assertNotFound();
        $this->get('/admin/projects/'.$entry->id.'/edit')->assertOk()->assertDontSee('Media & relationships')->assertDontSee('Construction stages')->assertDontSee('Progress last updated')->assertDontSee('Expected completion')->assertSee('Project gallery');
        $this->get('/admin/projects/'.$entry->id.'/preview')->assertOk()->assertSee('Foundation complete')->assertSee('noindex');
        $this->publish($entry);
        $this->get('/projects/'.$entry->slug)->assertOk()->assertSee('72% complete')->assertSee('Civil works.')->assertDontSee('Confidential client')->assertDontSee('Confidential value')->assertDontSee('Test-only approved specification');
        $this->put('/admin/projects/'.$entry->id, $this->payload(['version' => 1, 'progress' => 80]))->assertSessionHasNoErrors();
        $this->get('/projects/'.$entry->slug)->assertSee('72% complete')->assertDontSee('80% complete');
        $this->publish($entry, 2);
        $this->get('/projects/'.$entry->slug)->assertSee('80% complete');
        $this->post('/admin/projects/'.$entry->id.'/status', ['action' => 'unpublish', 'version' => 2])->assertSessionHasNoErrors();
        $this->get('/projects/'.$entry->slug)->assertNotFound();
    }

    public function test_assigned_editors_cannot_access_other_projects_or_publish_or_assign(): void
    {
        $this->login();
        $entry = $this->createProject();
        $other = $this->createProject(['slug' => 'other-project', 'title' => 'Unassigned project']);
        $editor = $this->login('project-editor');
        $entry->assignees()->attach($editor);
        $this->get('/admin/projects')->assertOk()->assertSee($entry->title)->assertDontSee($other->title);
        $this->get('/admin/projects/'.$entry->id.'/edit')->assertOk();
        $this->get('/admin/projects/'.$other->id.'/edit')->assertForbidden();
        $this->get('/admin/projects/'.$other->id.'/preview')->assertForbidden();
        $this->put('/admin/projects/'.$other->id, $this->payload(['slug' => $other->slug, 'version' => 1]))->assertForbidden();
        $this->post('/admin/projects/'.$entry->id.'/status', ['action' => 'publish', 'version' => 1])->assertForbidden();
        $this->post('/admin/projects/'.$entry->id.'/assign', ['assignees' => [$editor->id]])->assertForbidden();
        $this->get('/admin/projects/create')->assertForbidden();
    }

    public function test_filters_only_use_published_project_data(): void
    {
        $this->login();
        $entry = $this->createProject();
        $this->createProject(['slug' => 'future-private', 'city' => 'Private future city']);
        $this->publish($entry);
        $this->get('/projects?city=Test%20city&status=Ongoing')->assertOk()->assertSee($entry->title)->assertDontSee('Private future city');
        $this->get('/projects?status=Completed')->assertOk()->assertDontSee($entry->title)->assertSee('No matching projects');
    }

    public function test_publication_rejects_unverified_project_and_stale_save(): void
    {
        $this->login();
        $entry = $this->createProject(['verified' => false]);
        foreach (['review', 'approve'] as $action) {
            $this->post('/admin/projects/'.$entry->id.'/status', ['action' => $action, 'version' => 1])->assertSessionHasNoErrors();
        }
        $this->post('/admin/projects/'.$entry->id.'/status', ['action' => 'publish', 'version' => 1])->assertSessionHasErrors('verified');
        $this->assertNull($entry->fresh()->published_revision_id);
        $this->put('/admin/projects/'.$entry->id, $this->payload(['version' => 0]))->assertSessionHasErrors('version');
    }

    public function test_invalid_progress_and_private_media_are_rejected(): void
    {
        $this->login();
        $this->post('/admin/projects', $this->payload(['progress' => 101, 'cover_media_id' => 999999, 'gallery' => [['media_id' => 999999, 'category' => 'Drone', 'visible' => true]]]))->assertSessionHasErrors(['progress', 'cover_media_id', 'gallery.0.media_id']);
        $this->assertDatabaseMissing('content_entries', ['type' => 'project']);
    }

    public function test_viewer_and_guest_cannot_use_project_admin(): void
    {
        $this->get('/admin/projects')->assertRedirect('/login');
        $this->login('viewer');
        $this->get('/admin/projects')->assertForbidden();
        $this->post('/admin/projects', $this->payload())->assertForbidden();
    }

    public function test_media_is_rechecked_on_scheduled_publication_and_hidden_after_revocation(): void
    {
        $this->login();
        $media = Media::factory()->create(['is_public' => true, 'publication_status' => 'published']);
        $entry = $this->createProject(['cover_media_id' => $media->id]);
        foreach (['review', 'approve'] as $action) {
            $this->post('/admin/projects/'.$entry->id.'/status', ['action' => $action, 'version' => 1])->assertSessionHasNoErrors();
        }
        $this->post('/admin/projects/'.$entry->id.'/status', ['action' => 'schedule', 'version' => 1, 'scheduled_at' => now()->addMinute()->format('Y-m-d H:i:s')])->assertSessionHasNoErrors();
        $media->update(['is_public' => false]);
        $this->travel(2)->minutes();
        $this->assertSame(0, app(ContentPublisher::class)->publishDue());
        $this->assertNull($entry->fresh()->published_revision_id);
        $media->update(['is_public' => true]);
        $this->publish($entry);
        $this->get('/projects/'.$entry->slug)->assertSee(route('media.show', $media));
        $media->update(['is_public' => false]);
        $this->get('/projects/'.$entry->slug)->assertDontSee(route('media.show', $media));
    }

    public function test_assignment_changes_are_audited_and_revocation_takes_effect(): void
    {
        $admin = $this->login();
        $entry = $this->createProject();
        $editor = $this->login('project-editor');
        $this->actingAs($admin)->post('/admin/projects/'.$entry->id.'/assign', ['assignees' => [$editor->id]])->assertSessionHasNoErrors();
        $this->actingAs($editor)->get('/admin/projects/'.$entry->id.'/edit')->assertOk();
        $this->actingAs($admin)->post('/admin/projects/'.$entry->id.'/assign', [])->assertSessionHasNoErrors();
        $this->actingAs($editor)->get('/admin/projects/'.$entry->id.'/edit')->assertForbidden();
        $audit = AuditLog::where('action', 'project.assignment_updated')->latest('id')->firstOrFail();
        $this->assertSame([$editor->id], $audit->changes['before_assignees']);
        $this->assertSame([], $audit->changes['after_assignees']);
    }

    public function test_simplified_editor_generates_stable_urls_and_preserves_removed_fields(): void
    {
        $this->login();
        $data = $this->payload();
        unset($data['slug'], $data['project_type'], $data['progress_date'], $data['timeline']);
        $this->post('/admin/projects', $data)->assertSessionHasNoErrors();
        $entry = ContentEntry::where('slug', 'verified-test-project')->firstOrFail();
        $this->assertSame('Buildings', $entry->revisions()->firstOrFail()->payload['project_type']);
        $this->post('/admin/projects', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('content_entries', ['slug' => 'verified-test-project-2']);
        $media = Media::factory()->create(['is_public' => true, 'publication_status' => 'published']);
        $legacy = $this->createProject(['slug' => 'legacy-project', 'manager' => 'Existing manager', 'expected_completion' => '2027-01-01', 'cover_media_id' => $media->id]);
        $old = $legacy->revisions()->firstOrFail()->payload;
        $this->travel(1)->days();
        $this->put('/admin/projects/'.$legacy->id, array_replace($data, ['version' => 1, 'title' => 'Renamed project']))->assertSessionHasNoErrors();
        $saved = $legacy->revisions()->latest('version')->firstOrFail()->payload;
        foreach (['timeline', 'manager', 'expected_completion', 'project_type', 'progress_date', 'cover_media_id'] as $field) {
            $this->assertSame($old[$field], $saved[$field]);
        }
        $this->assertSame('legacy-project', $legacy->fresh()->slug);
        $this->put('/admin/projects/'.$legacy->id, array_replace($data, ['version' => 2, 'progress' => 85]))->assertSessionHasNoErrors();
        $this->assertSame(now()->toDateString(), $legacy->revisions()->latest('version')->firstOrFail()->payload['progress_date']);
        $this->publish($legacy, 3);
        $this->get('/projects/legacy-project')->assertOk()->assertSee('85% complete');
    }

    public function test_dates_and_completed_progress_must_be_consistent(): void
    {
        $this->login();
        $this->post('/admin/projects', $this->payload(['status' => 'Completed', 'progress' => 50, 'start_date' => '2026-01-01', 'actual_completion' => '2025-12-01']))->assertSessionHasErrors(['progress', 'actual_completion']);
    }
}
