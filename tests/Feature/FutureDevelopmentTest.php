<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\DevelopmentSpace;
use App\Models\Enquiry;
use App\Models\Role;
use App\Models\User;
use App\Services\SeoContent;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FutureDevelopmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, HomepageSeeder::class]);
    }

    private function login(string $role = 'super-admin'): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->firstOrFail());
        $this->actingAs($user);
    }

    private function payload(): array
    {
        return ['title' => 'Approved test development', 'slug' => 'test-development', 'category' => 'Residential', 'location' => 'Test location', 'summary' => 'Approved summary', 'body' => 'Approved development description', 'source_note' => 'PRIVATE EVIDENCE', 'verified' => true, 'progress' => 10, 'version' => 0];
    }

    private function development(): ContentEntry
    {
        $this->login();
        $this->post('/admin/developments', $this->payload())->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'development')->firstOrFail();
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post('/admin/developments/'.$entry->id.'/status', ['action' => $action, 'version' => 1])->assertSessionHasNoErrors();
        }

        return $entry;
    }

    private function space(ContentEntry $entry, string $kind, ?DevelopmentSpace $parent = null): DevelopmentSpace
    {
        $this->post('/admin/developments/'.$entry->id.'/spaces', ['kind' => $kind, 'parent_id' => $parent?->id, 'name' => 'Test '.$kind, 'status' => 'Available', 'version' => 0, 'is_public' => true, 'verified' => 1, 'details' => ['price_public' => false, 'price' => 987654, 'currency' => 'INR']])->assertSessionHasNoErrors();

        return DevelopmentSpace::where('content_entry_id', $entry->id)->where('kind', $kind)->firstOrFail();
    }

    public function test_disabled_module_hides_published_records_and_toggle_restores_visibility(): void
    {
        $entry = $this->development();
        $this->get('/developments')->assertNotFound();
        $this->get('/developments/test-development')->assertNotFound();
        $this->get('/search?q=Approved')->assertDontSee('Approved test development');
        $this->assertFalse(SeoContent::inventory()->has('/developments/test-development'));
        $this->get('/admin/developments/'.$entry->id.'/edit')->assertOk();
        $this->get('/admin/developments/'.$entry->id.'/preview')->assertOk()->assertDontSee('Request site visit')->assertDontSee('PRIVATE EVIDENCE');
        $this->post('/admin/developments/visibility', ['enabled' => true, 'version' => 0])->assertSessionHasNoErrors();
        $this->get('/developments/test-development')->assertOk()->assertSee('Approved test development');
        $this->assertTrue(SeoContent::inventory()->has('/developments/test-development'));
        $this->post('/admin/developments/visibility', ['enabled' => false, 'version' => 1])->assertSessionHasNoErrors();
        $this->get('/developments')->assertNotFound();
    }

    public function test_inventory_hierarchy_private_prices_and_site_visit_request(): void
    {
        $entry = $this->development();
        $tower = $this->space($entry, 'tower');
        $floor = $this->space($entry, 'floor', $tower);
        $unit = $this->space($entry, 'unit', $floor);
        $this->post('/admin/developments/visibility', ['enabled' => true, 'version' => 0])->assertSessionHasNoErrors();
        $this->get('/developments/test-development')->assertOk()->assertSee('Test unit')->assertDontSee('987654');
        $this->post('/developments/test-development/visit', ['name' => 'Test visitor', 'email' => 'visitor@example.test', 'phone' => '1234567890', 'preferred_at' => now()->addDay()->format('Y-m-d H:i:s'), 'unit_id' => $unit->id, 'consent' => 1])->assertSessionHasNoErrors();
        $this->assertSame('Test unit', Enquiry::firstOrFail()->details['unit']);
        $this->assertSame('Available', $unit->fresh()->status);
        $tower->update(['is_public' => false]);
        $this->get('/developments/test-development')->assertDontSee('Test unit');
        $this->post('/developments/test-development/visit', ['name' => 'Test visitor', 'email' => 'visitor@example.test', 'phone' => '1234567890', 'preferred_at' => now()->addDay()->format('Y-m-d H:i:s'), 'unit_id' => $unit->id, 'consent' => 1])->assertStatus(422);
        $this->assertDatabaseCount('enquiries', 1);
    }

    public function test_invalid_hierarchy_stale_inventory_and_unverified_public_inventory_are_rejected(): void
    {
        $entry = $this->development();
        $tower = $this->space($entry, 'tower');
        $data = ['kind' => 'unit', 'parent_id' => $tower->id, 'name' => 'Wrong unit', 'status' => 'Available', 'version' => 0, 'is_public' => false, 'details' => ['price_public' => false]];
        $this->post('/admin/developments/'.$entry->id.'/spaces', $data)->assertSessionHasErrors('parent_id');
        $this->post('/admin/developments/'.$entry->id.'/spaces', array_replace($data, ['kind' => 'floor', 'is_public' => true]))->assertSessionHasErrors('verified');
        $this->put('/admin/developments/'.$entry->id.'/spaces/'.$tower->id, array_replace($data, ['kind' => 'tower', 'parent_id' => null]))->assertSessionHasErrors('version');
        $this->assertDatabaseCount('development_spaces', 1);
    }

    public function test_non_administrator_cannot_manage_module_and_disabled_post_cannot_capture_lead(): void
    {
        $entry = $this->development();
        $this->login('project-manager');
        $this->get('/admin/developments')->assertForbidden();
        $this->post('/admin/developments/visibility', ['enabled' => true, 'version' => 0])->assertForbidden();
        $this->put('/admin/developments/'.$entry->id, $this->payload())->assertForbidden();
        $this->post('/developments/test-development/visit', [])->assertNotFound();
        $this->assertDatabaseCount('enquiries', 0);
    }
}
