<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Role;
use App\Models\User;
use App\Services\LocationContent;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, HomepageSeeder::class]);
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user);
    }

    private function payload(string $level, ?ContentEntry $parent = null, array $extra = []): array
    {
        return array_replace(['title' => 'Test '.$level, 'slug' => 'test-'.$level, 'level' => $level, 'parent_id' => $parent?->id, 'presence' => 'current', 'summary' => 'Approved location introduction.', 'body' => str_repeat('Verified local experience and meaningful project information. ', 3), 'verified' => 1, 'source_note' => 'Internal approval reference', 'version' => 0], $extra);
    }

    private function location(string $level, ?ContentEntry $parent = null, array $extra = []): ContentEntry
    {
        $data = $this->payload($level, $parent, $extra);
        $this->post('/admin/locations', $data)->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'location')->where('slug', $data['slug'])->firstOrFail();
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post('/admin/content/'.$entry->id.'/status', ['version' => 1, 'action' => $action])->assertSessionHasNoErrors();
        }

        return $entry;
    }

    public function test_current_hierarchy_is_public_and_parent_unpublication_hides_descendants(): void
    {
        $country = $this->location('country');
        $state = $this->location('state', $country);
        $region = $this->location('region', $state);
        $city = $this->location('city', $region, ['latitude' => 30.5, 'longitude' => 76.5]);
        $this->get('/locations')->assertOk()->assertSee('Test city');
        $this->get('/locations/test-city')->assertOk()->assertSee('Test region')->assertSee('Open detailed map')->assertDontSee('Internal approval reference');
        $this->get('/admin/locations/'.$city->id.'/edit')->assertOk()->assertSee('Review & publication', false);
        $this->post('/admin/content/'.$state->id.'/status', ['version' => 1, 'action' => 'unpublish'])->assertSessionHasNoErrors();
        $this->get('/locations/test-city')->assertNotFound();
        $this->get('/locations')->assertDontSee('Test city');
    }

    public function test_future_locations_and_their_children_never_appear_as_active(): void
    {
        $country = $this->location('country', null, ['presence' => 'future']);
        $this->location('state', $country);
        $this->assertCount(0, LocationContent::active());
        $this->get('/locations/test-country')->assertNotFound();
        $this->get('/locations/test-state')->assertNotFound();
        $this->get('/admin/content/'.$country->id.'/preview')->assertOk()->assertSee('Planned location')->assertSee('noindex');
    }

    public function test_hierarchy_coordinates_and_publication_evidence_are_validated(): void
    {
        $country = $this->location('country');
        $this->post('/admin/locations', $this->payload('city', $country))->assertSessionHasErrors('parent_id');
        $this->post('/admin/locations', $this->payload('state', $country, ['latitude' => 91, 'longitude' => 200]))->assertSessionHasErrors(['latitude', 'longitude']);
        $data = $this->payload('state', $country, ['verified' => 0]);
        $this->post('/admin/locations', $data)->assertSessionHasNoErrors();
        $entry = ContentEntry::where('slug', 'test-state')->firstOrFail();
        foreach (['review', 'approve'] as $action) {
            $this->post('/admin/content/'.$entry->id.'/status', ['version' => 1, 'action' => $action])->assertSessionHasNoErrors();
        }
        $this->post('/admin/content/'.$entry->id.'/status', ['version' => 1, 'action' => 'publish'])->assertSessionHasErrors('verified');
        $this->get('/locations/test-state')->assertNotFound();
    }

    public function test_location_links_only_published_projects_and_rolls_up_to_parent(): void
    {
        $country = $this->location('country');
        $state = $this->location('state', $country);
        $region = $this->location('region', $state);
        $city = $this->location('city', $region);
        $project = ContentEntry::factory()->create(['title' => 'Visible linked project']);
        $revision = $project->revisions()->create(['version' => 1, 'payload' => ['title' => $project->title, 'location_entry_id' => $city->id, 'stage' => 'Structure', 'progress' => 50, 'featured' => false]]);
        $project->update(['published_revision_id' => $revision->id]);
        $this->get('/locations/test-city')->assertOk()->assertSee('Visible linked project');
        $this->get('/locations/test-country')->assertOk()->assertSee('Visible linked project');
        $project->update(['published_revision_id' => null]);
        $this->get('/locations/test-city')->assertDontSee('Visible linked project');
    }

    public function test_viewer_cannot_manage_locations_and_drafts_preserve_published_content(): void
    {
        $country = $this->location('country');
        $this->put('/admin/locations/'.$country->id, $this->payload('country', null, ['version' => 1, 'title' => 'Unpublished rename']))->assertSessionHasNoErrors();
        $this->get('/locations/test-country')->assertSee('Test country')->assertDontSee('Unpublished rename');
        $this->put('/admin/locations/'.$country->id, $this->payload('country', null, ['version' => 1]))->assertSessionHasErrors('version');
        $viewer = User::factory()->create();
        $viewer->roles()->attach(Role::where('name', 'viewer')->firstOrFail());
        $this->actingAs($viewer)->get('/admin/locations')->assertForbidden();
        $this->post('/admin/locations', $this->payload('country'))->assertForbidden();
    }

    public function test_project_location_link_uses_verified_hierarchy_and_rejects_future_city(): void
    {
        $country = $this->location('country');
        $state = $this->location('state', $country);
        $region = $this->location('region', $state);
        $city = $this->location('city', $region);
        $data = ['title' => 'Linked project', 'slug' => 'linked-project', 'version' => 0, 'project_type' => 'Construction', 'status' => 'Ongoing', 'sector' => 'Buildings', 'stage' => 'Structure', 'progress' => 50, 'featured' => 0, 'client_approved' => 0, 'value_approved' => 0, 'progress_date' => now()->toDateString(), 'location_entry_id' => $city->id, 'city' => 'Wrong city'];
        $this->post('/admin/projects', $data)->assertSessionHasNoErrors();
        $project = ContentEntry::where('slug', 'linked-project')->firstOrFail();
        $payload = $project->revisions()->firstOrFail()->payload;
        $this->assertSame('Test city', $payload['city']);
        $this->assertSame('Test state', $payload['state']);
        $this->assertSame('Test country', $payload['country']);
        $future = $this->location('city', $region, ['slug' => 'future-city', 'presence' => 'future']);
        $this->post('/admin/projects', array_replace($data, ['slug' => 'future-project', 'location_entry_id' => $future->id]))->assertSessionHasErrors('location_entry_id');
    }
}
