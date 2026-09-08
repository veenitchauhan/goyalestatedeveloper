<?php

namespace Tests\Feature;

use App\Models\BusinessUnit;
use App\Models\ContentEntry;
use App\Models\Media;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\ContentPublisher;
use Database\Seeders\CorporateContentSeeder;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CorporateContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
    }

    private function signIn(string $role = 'super-admin'): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->firstOrFail());
        $this->actingAs($user);
    }

    private function data(string $type, string $slug = 'approved-record', array $facts = []): array
    {
        return ['type' => $type, 'slug' => $slug, 'title' => 'Verified '.$slug, 'summary' => 'A specific introduction supplied for an isolated test.', 'body' => 'This approved record describes the scope and requirements of the work in detail. Its facts are isolated test content, not company claims.', 'facts' => $facts, 'order' => 10, 'featured' => 0, 'source_note' => 'Private approval reference', 'version' => 0];
    }

    private function createDraft(array $data): ContentEntry
    {
        $this->post(route('admin.corporate.store'), $data)->assertSessionHasNoErrors();

        return ContentEntry::where('type', $data['type'])->where('slug', $data['slug'])->firstOrFail();
    }

    private function publish(ContentEntry $entry, int $version = 1): void
    {
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post(route('admin.content.transition', $entry), ['action' => $action, 'version' => $version])->assertSessionHasNoErrors();
        }
    }

    public static function standaloneTypes(): array
    {
        return [
            'company page' => ['company_page', ['topic' => 'story'], '/about/approved-record'],
            'business' => ['business_unit', [], '/business/approved-record'],
            'capability' => ['capability', ['focus_area' => 'Planning'], '/capabilities/approved-record'],
            'equipment' => ['equipment', ['category' => 'Lifting', 'quantity' => 2], '/capabilities/equipment/approved-record'],
            'person' => ['team_member', ['designation' => 'Engineer'], '/about/people/approved-record'],
            'milestone' => ['company_milestone', ['occurred_on' => '2025-01-01', 'timeline' => 'leadership'], '/about/milestones/approved-record'],
        ];
    }

    #[DataProvider('standaloneTypes')]
    public function test_corporate_content_has_private_drafts_and_dedicated_published_pages(string $type, array $facts, string $url): void
    {
        $this->signIn();
        $this->get(route('admin.corporate.create', ['type' => $type]))->assertOk();
        $data = $this->data($type, facts: $facts);
        $entry = $this->createDraft($data);
        $this->get(route('admin.corporate.edit', $entry))->assertOk()->assertSee('Review & publication', false);
        $this->get($url)->assertNotFound();
        $this->get(route('admin.content.preview', $entry))->assertSee($data['title'])->assertDontSee('Private approval reference');
        $this->publish($entry);
        $this->get($url)->assertOk()->assertSee($data['title'])->assertSee('href="'.url($url).'"', false)->assertDontSee('Private approval reference');
        $data['title'] = 'Private replacement title';
        $data['version'] = 1;
        $this->put(route('admin.corporate.update', $entry), $data)->assertSessionHasNoErrors();
        $this->get($url)->assertDontSee('Private replacement title');
        $this->get(route('admin.content.preview', $entry))->assertSee('Private replacement title');
        $this->post(route('admin.content.transition', $entry), ['action' => 'archive', 'version' => 2])->assertSessionHasNoErrors();
        $this->get($url)->assertNotFound();
    }

    public function test_services_and_employee_stories_follow_published_parent_records(): void
    {
        $this->signIn();
        $business = $this->createDraft($this->data('business_unit', 'construction'));
        $this->publish($business);
        $unit = BusinessUnit::where('content_entry_id', $business->id)->firstOrFail();
        $this->get(route('admin.corporate.create', ['type' => 'service']))->assertSee('Verified construction');
        $service = $this->createDraft($this->data('service', 'structural-work', ['business_unit_id' => $unit->id, 'delivery_scope' => 'Approved structural scope']));
        $this->publish($service);
        $this->assertDatabaseHas('services', ['content_entry_id' => $service->id, 'business_unit_id' => $unit->id]);
        $this->get('/business/construction')->assertSee('Verified structural-work');
        $this->get('/business/services/structural-work')->assertSee('Approved structural scope')->assertSee('Verified construction');
        $person = $this->createDraft($this->data('team_member', 'engineer', ['designation' => 'Engineer']));
        $this->publish($person);
        $member = TeamMember::where('content_entry_id', $person->id)->firstOrFail();
        $this->get(route('admin.corporate.create', ['type' => 'employee_story']))->assertSee('Verified engineer');
        $story = $this->createDraft($this->data('employee_story', 'site-story', ['team_member_id' => $member->id]));
        $this->publish($story);
        $this->get('/about/employee-stories/site-story')->assertSee('Verified engineer');
        foreach ([$business, $person] as $parent) {
            $this->post(route('admin.content.transition', $parent), ['action' => 'unpublish', 'version' => 1])->assertSessionHasNoErrors();
        }
        $this->get('/business/services/structural-work')->assertNotFound();
        $this->get('/about/employee-stories/site-story')->assertNotFound();
        $this->get('/business')->assertDontSee('Verified structural-work');
    }

    public function test_equipment_draft_edits_do_not_change_live_facts_and_withdrawn_media_is_hidden(): void
    {
        $this->signIn();
        $image = Media::factory()->create(['is_public' => true, 'publication_status' => 'published', 'alt' => 'Approved equipment photograph']);
        $data = $this->data('equipment', 'tower-crane', ['category' => 'Tower cranes', 'quantity' => 2]);
        $data['cover_media_id'] = $image->id;
        $entry = $this->createDraft($data);
        $this->publish($entry);
        $data['facts']['quantity'] = 5;
        $data['version'] = 1;
        $this->put(route('admin.corporate.update', $entry), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('equipment', ['content_entry_id' => $entry->id, 'quantity' => 2]);
        $this->publish($entry, 2);
        $this->assertDatabaseHas('equipment', ['content_entry_id' => $entry->id, 'quantity' => 5]);
        $this->get('/capabilities/equipment/tower-crane')->assertSee('Approved equipment photograph');
        $image->update(['is_public' => false]);
        $this->get('/capabilities/equipment/tower-crane')->assertDontSee('Approved equipment photograph');
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.draft_saved', 'subject_id' => $entry->id]);
    }

    public function test_public_catalogues_render_without_invented_records(): void
    {
        $this->seed(CorporateContentSeeder::class);
        foreach (['/about', '/business', '/capabilities', '/capabilities/equipment', '/about/leadership', '/about/journey', '/about/employee-stories'] as $url) {
            $this->get($url)->assertOk()->assertSee('More to share.')->assertDontSee('Draft outline');
        }
        $this->assertDatabaseCount('equipment', 0);
        $this->assertDatabaseCount('team_members', 0);
    }

    public function test_invalid_fields_media_and_route_collisions_do_not_create_records(): void
    {
        $this->signIn();
        $private = Media::factory()->create(['is_public' => false]);
        $data = $this->data('equipment', facts: ['category' => 'Lifting', 'quantity' => -1, 'invented_field' => 'injected']);
        $data['cover_media_id'] = $private->id;
        $this->post(route('admin.corporate.store'), $data)->assertSessionHasErrors(['facts', 'facts.quantity', 'cover_media_id']);
        $this->post(route('admin.corporate.store'), $this->data('company_page', 'leadership', ['topic' => 'story']))->assertSessionHasErrors('slug');
        $this->post(route('admin.corporate.store'), $this->data('service', facts: ['business_unit_id' => 999, 'delivery_scope' => 'Scope']))->assertSessionHasErrors('facts.business_unit_id');
        $this->assertSame(0, ContentEntry::whereIn('type', ['equipment', 'company_page', 'service'])->count());
    }

    public function test_incomplete_outlines_cannot_publish_and_seed_reruns_preserve_edits(): void
    {
        $this->signIn();
        $this->seed(CorporateContentSeeder::class);
        $entry = ContentEntry::where('type', 'business_unit')->where('slug', 'construction')->firstOrFail();
        foreach (['review', 'approve'] as $action) {
            $this->post(route('admin.content.transition', $entry), ['action' => $action, 'version' => 1])->assertSessionHasNoErrors();
        }
        $this->post(route('admin.content.transition', $entry), ['action' => 'publish', 'version' => 1])->assertSessionHasErrors(['summary', 'body']);
        $this->assertNull($entry->fresh()->published_revision_id);
        $this->get('/business/construction')->assertNotFound();
        $this->seed(CorporateContentSeeder::class);
        $this->assertSame(1, $entry->revisions()->count());
        $this->assertDatabaseCount('business_units', 0);
    }

    public function test_editor_can_draft_but_cannot_publish_and_viewer_cannot_modify_records(): void
    {
        $this->signIn('content-manager');
        $entry = $this->createDraft($this->data('business_unit'));
        $this->post(route('admin.content.transition', $entry), ['action' => 'approve', 'version' => 1])->assertForbidden();
        $this->post(route('admin.content.transition', $entry), ['action' => 'publish', 'version' => 1])->assertForbidden();
        $this->signIn('viewer');
        $this->get(route('admin.corporate.index'))->assertForbidden();
        $this->get(route('admin.corporate.edit', $entry))->assertForbidden();
        $this->put(route('admin.corporate.update', $entry), $this->data('business_unit'))->assertForbidden();
        $this->get(route('admin.content.preview', $entry))->assertForbidden();
        $this->assertNull($entry->fresh()->published_revision_id);
    }

    public function test_shared_homepage_business_cards_use_published_versions_and_sort_featured_records_first(): void
    {
        $this->signIn();
        $first = $this->createDraft($this->data('business_unit', 'first-area'));
        $secondData = $this->data('business_unit', 'featured-area');
        $secondData['featured'] = 1;
        $secondData['order'] = 50;
        $second = $this->createDraft($secondData);
        $this->publish($first);
        $this->publish($second);
        $this->get('/')->assertSeeInOrder(['Verified featured-area', 'Verified first-area']);
        $this->get('/business')->assertSeeInOrder(['Verified featured-area', 'Verified first-area']);
        $secondData['version'] = 1;
        $secondData['title'] = 'Unpublished business title';
        $this->put(route('admin.corporate.update', $second), $secondData)->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('Unpublished business title');
        $this->get('/business')->assertDontSee('Unpublished business title');
    }

    public function test_invalid_types_and_immutable_slugs_do_not_change_existing_records(): void
    {
        $this->signIn();
        $this->post(route('admin.corporate.store'), ['type' => ['equipment']])->assertSessionHasErrors('type');
        $this->get(route('admin.corporate.create', ['type' => ['equipment']]))->assertSessionHasErrors('type');
        $entry = $this->createDraft($this->data('business_unit'));
        $data = $this->data('business_unit', 'changed-slug');
        $data['version'] = 1;
        $this->put(route('admin.corporate.update', $entry), $data)->assertUnprocessable();
        $this->assertSame('approved-record', $entry->fresh()->slug);
        $this->assertSame(1, $entry->revisions()->count());
    }

    public function test_stale_updates_are_rejected_and_public_copy_is_escaped(): void
    {
        $this->signIn();
        $data = $this->data('business_unit');
        $data['summary'] = '<script>alert(1)</script>';
        $entry = $this->createDraft($data);
        $this->publish($entry);
        $this->get('/business/approved-record')->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
        $this->put(route('admin.corporate.update', $entry), $data)->assertSessionHasErrors('version');
        $this->assertSame(1, $entry->revisions()->count());
    }

    public function test_scheduled_publication_rechecks_media_and_does_not_block_other_schedules(): void
    {
        $this->freezeTime();
        $this->signIn();
        $image = Media::factory()->create(['is_public' => true, 'publication_status' => 'published']);
        $first = $this->createDraft($this->data('capability', 'first') + ['cover_media_id' => $image->id]);
        $second = $this->createDraft($this->data('capability', 'second'));
        foreach ([$first, $second] as $entry) {
            foreach (['review', 'approve', 'schedule'] as $action) {
                $this->post(route('admin.content.transition', $entry), ['action' => $action, 'version' => 1, 'scheduled_at' => now()->addMinute()->format('Y-m-d H:i:s')])->assertSessionHasNoErrors();
            }
        }
        $image->update(['publication_status' => 'draft']);
        $this->travel(2)->minutes();
        $this->assertSame(1, app(ContentPublisher::class)->publishDue());
        $this->assertSame('draft', $first->fresh()->status);
        $this->assertNull($first->fresh()->published_revision_id);
        $this->get('/capabilities/first')->assertNotFound();
        $this->get('/capabilities/second')->assertSee('Verified second');
    }
}
