<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Role;
use App\Models\User;
use App\Services\ContentPublisher;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeTest extends TestCase
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

    private function payload(string $type = 'knowledge'): array
    {
        return ['type' => $type, 'title' => 'Test construction planning', 'slug' => 'planning-'.$type, 'category' => 'Construction', 'topic' => 'Planning', 'short_answer' => 'A verified public summary', 'body' => 'Detailed approved explanation', 'explanation' => 'Supporting context', 'author' => 'Test Author', 'reviewer' => 'Test Reviewer', 'source_note' => 'PRIVATE APPROVAL REFERENCE', 'verified' => true, 'featured' => true, 'schema_enabled' => false, 'order' => 0, 'version' => 0];
    }

    private function draft(string $type = 'knowledge', array $overrides = []): ContentEntry
    {
        $data = array_replace($this->payload($type), $overrides);
        $this->post('/admin/knowledge', $data)->assertSessionHasNoErrors()->assertRedirect();

        return ContentEntry::where('type', $type)->where('slug', $data['slug'])->firstOrFail();
    }

    private function publish(ContentEntry $entry, int $version = 1): void
    {
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post('/admin/content/'.$entry->id.'/status', ['action' => $action, 'version' => $version])->assertSessionHasNoErrors();
        }
    }

    public function test_editorial_types_publish_with_private_evidence_and_isolated_drafts(): void
    {
        $this->login();
        foreach (['article' => 'insights', 'knowledge' => 'knowledge-bank', 'faq' => 'faqs'] as $type => $path) {
            $entry = $this->draft($type);
            $this->get('/'.$path.'/'.$entry->slug)->assertNotFound();
            $this->get('/admin/knowledge/'.$entry->id.'/edit')->assertOk()->assertSee('PRIVATE APPROVAL REFERENCE');
            $this->get('/admin/knowledge/'.$entry->id.'/preview')->assertOk()->assertSee('noindex,nofollow', false);
            $this->publish($entry);
            $this->get('/'.$path.'/'.$entry->slug)->assertOk()->assertSee('Test Reviewer')->assertSee('Detailed approved explanation')->assertDontSee('PRIVATE APPROVAL REFERENCE');
            $this->put('/admin/knowledge/'.$entry->id, array_replace($this->payload($type), ['version' => 1, 'title' => 'UNPUBLISHED NEW TITLE']))->assertSessionHasNoErrors();
            $this->get('/'.$path.'/'.$entry->slug)->assertOk()->assertSee('Test construction planning')->assertDontSee('UNPUBLISHED NEW TITLE');
            $this->get('/search?q=UNPUBLISHED')->assertOk()->assertDontSee('UNPUBLISHED NEW TITLE');
            $this->post('/admin/content/'.$entry->id.'/status', ['action' => 'unpublish', 'version' => 2])->assertSessionHasNoErrors();
            $this->get('/'.$path.'/'.$entry->slug)->assertNotFound();
        }
    }

    public function test_search_filters_and_related_answers_use_only_published_revisions(): void
    {
        $this->login();
        $faq = $this->draft('faq', ['title' => 'Planning FAQ']);
        $this->publish($faq);
        $entry = $this->draft('knowledge', ['related_ids' => [$faq->id]]);
        $this->publish($entry);
        $this->draft('article', ['title' => 'Hidden planning article']);
        $this->get('/knowledge-bank?category=Construction&topic=Planning&q=verified')->assertOk()->assertSee('Test construction planning');
        $this->get('/knowledge-bank?category=Safety')->assertOk()->assertDontSee('Test construction planning');
        $this->get('/search?q=planning')->assertOk()->assertSee('Planning FAQ')->assertSee('Test construction planning')->assertDontSee('Hidden planning article');
        $this->get('/search?q=PRIVATE')->assertOk()->assertDontSee('Test construction planning');
        $this->get('/faqs/'.$faq->slug)->assertOk()->assertSee('Test construction planning');
        $this->post('/admin/content/'.$faq->id.'/status', ['action' => 'unpublish', 'version' => 1])->assertSessionHasNoErrors();
        $this->get('/knowledge-bank/'.$entry->slug)->assertOk()->assertDontSee('Planning FAQ');
        $this->get('/search?q=planning')->assertDontSee('Planning FAQ');
    }

    public function test_invalid_links_unverified_publication_and_stale_updates_are_rejected(): void
    {
        $this->login();
        $hidden = $this->draft('faq');
        $this->post('/admin/knowledge', array_replace($this->payload(), ['related_ids' => [$hidden->id]]))->assertSessionHasErrors('related_ids.0');
        $entry = $this->draft('knowledge', ['verified' => false]);
        foreach (['review', 'approve'] as $action) {
            $this->post('/admin/content/'.$entry->id.'/status', ['action' => $action, 'version' => 1])->assertSessionHasNoErrors();
        }
        $this->post('/admin/content/'.$entry->id.'/status', ['action' => 'publish', 'version' => 1])->assertSessionHasErrors('verified');
        $this->assertNull($entry->fresh()->published_revision_id);
        $this->put('/admin/knowledge/'.$entry->id, $this->payload())->assertSessionHasErrors('version');
        $this->put('/admin/knowledge/'.$entry->id, array_replace($this->payload(), ['version' => 1, 'type' => 'article']))->assertStatus(422);
        $this->assertSame(1, $entry->revisions()->count());
    }

    public function test_content_manager_cannot_approve_and_hr_cannot_access_editorial_records(): void
    {
        $this->login('content-manager');
        $entry = $this->draft();
        $this->post('/admin/content/'.$entry->id.'/status', ['action' => 'approve', 'version' => 1])->assertForbidden();
        $this->login('hr-executive');
        $this->get('/admin/knowledge')->assertForbidden();
        $this->get('/admin/knowledge/'.$entry->id.'/preview')->assertForbidden();
        $this->put('/admin/knowledge/'.$entry->id, array_replace($this->payload(), ['version' => 1]))->assertForbidden();
        $this->get('/admin/search?q=construction')->assertOk()->assertDontSee('Test construction planning');
        $this->post('/logout');
        $this->get('/admin/knowledge')->assertRedirect('/login');
        $this->get('/admin/search?q=construction')->assertRedirect('/login');
    }

    public function test_rendered_content_is_escaped_and_search_input_is_validated(): void
    {
        $this->login();
        $entry = $this->draft('article', ['body' => '<script>alert("x")</script>']);
        $this->publish($entry);
        $this->get('/insights/'.$entry->slug)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false);
        $this->get('/search?q[]=bad')->assertSessionHasErrors('q');
        $this->get('/admin/knowledge?type=job')->assertSessionHasErrors('type');
    }

    public function test_admin_search_limits_assigned_projects_and_scheduling_rechecks_links(): void
    {
        $editor = $this->login('project-editor');
        $assigned = ContentEntry::factory()->create(['title' => 'Needle assigned project']);
        $assigned->assignees()->attach($editor);
        ContentEntry::factory()->create(['title' => 'Needle private project']);
        $this->get('/admin/search?q=Needle')->assertOk()->assertSee('Needle assigned project')->assertDontSee('Needle private project');
        $this->login();
        $faq = $this->draft('faq');
        $this->publish($faq);
        $entry = $this->draft('knowledge', ['related_ids' => [$faq->id]]);
        foreach (['review', 'approve'] as $action) {
            $this->post('/admin/content/'.$entry->id.'/status', ['action' => $action, 'version' => 1])->assertSessionHasNoErrors();
        }
        $this->post('/admin/content/'.$entry->id.'/status', ['action' => 'schedule', 'version' => 1, 'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s')])->assertSessionHasNoErrors();
        $this->post('/admin/content/'.$faq->id.'/status', ['action' => 'unpublish', 'version' => 1])->assertSessionHasNoErrors();
        $this->travel(2)->hours();
        $this->assertSame(0, app(ContentPublisher::class)->publishDue());
        $this->assertNull($entry->fresh()->published_revision_id);
        $this->assertSame('draft', $entry->fresh()->status);
        $this->travelBack();
    }
}
