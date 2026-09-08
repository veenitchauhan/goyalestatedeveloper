<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Homepage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
    }

    private function enquiry(): array
    {
        return ['name' => 'Website test', 'email' => 'test@example.test', 'type' => 'Construction', 'location' => 'Test location', 'message' => 'An isolated automated test enquiry.', 'consent' => 1];
    }

    public function test_homepage_matches_brief_and_does_not_expose_development(): void
    {
        $this->get('/')->assertOk()->assertViewIs('home')->assertSee('BUILDING TODAY.')->assertSee('TOMORROW.')->assertSee('Start a project')->assertSee('From plan')->assertSee('Our work')->assertDontSee('DELIVERY PROGRESS')->assertDontSee('Local development')->assertDontSee('Open administration')->assertDontSee('Laravel foundation')->assertDontSee('Total modules');
        $this->get('/admin/development')->assertRedirect('/login');
    }

    public function test_homepage_uses_database_copy_and_hides_empty_contact_actions(): void
    {
        $page = Homepage::main();
        $content = $page->content;
        $content['hero']['line_one'] = 'AN EDITED HEADLINE';
        $page->update(['content' => $content, 'version' => 1]);
        $this->get('/')->assertSee('AN EDITED HEADLINE')->assertDontSee('href="tel:', false)->assertDontSee('https://wa.me/');
    }

    public function test_hidden_sections_are_removed_from_navigation_and_content(): void
    {
        $page = Homepage::main();
        $content = $page->content;
        $content['sections'][3]['enabled'] = false;
        $page->update(['content' => $content, 'version' => 1]);
        $this->get('/')->assertDontSee('id="projects"', false)->assertDontSee('href="#projects"', false);
    }

    public function test_enquiry_is_stored_with_consent_and_confirmation(): void
    {
        $this->post('/enquiries', $this->enquiry())->assertRedirect(route('home').'#contact')->assertSessionHas('enquiry_sent', true);
        $this->assertDatabaseHas('enquiries', ['email' => 'test@example.test', 'status' => 'new', 'consent_version' => 'enquiry-v1']);
    }

    public function test_invalid_or_spam_enquiries_are_not_saved(): void
    {
        $this->post('/enquiries', [...$this->enquiry(), 'consent' => 0])->assertSessionHasErrors('consent');
        $this->post('/enquiries', [...$this->enquiry(), 'website' => 'spam'])->assertSessionHasErrors('website');
        $this->post('/enquiries', [...$this->enquiry(), 'email' => 'bad', 'type' => 'Invalid'])->assertSessionHasErrors(['email', 'type']);
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_enquiry_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/enquiries', $this->enquiry());
        }
        $this->post('/enquiries', $this->enquiry())->assertStatus(429);
        $this->assertDatabaseCount('enquiries', 5);
    }

    public function test_viewer_cannot_read_enquiries_or_edit_homepage(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'viewer')->first());
        $this->actingAs($user)->get('/admin/enquiries')->assertForbidden();
        $this->get('/admin/homepage')->assertForbidden();
        $this->put('/admin/homepage', [])->assertForbidden();
    }

    public function test_authorized_homepage_edit_is_validated_and_audited(): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->first());
        $this->actingAs($user)->get('/admin/homepage')->assertOk();
        $content = Homepage::main()->content;
        $content['hero']['line_one'] = 'APPROVED HEADING';
        $this->put('/admin/homepage', ['content' => $content, 'version' => 1])->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('APPROVED HEADING');
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        foreach (['review', 'approve'] as $action) {
            $this->post(route('admin.content.transition', $entry), ['action' => $action, 'version' => 2])->assertSessionHasNoErrors();
        }
        $this->post(route('admin.content.transition', $entry), ['action' => 'publish', 'version' => 2])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('APPROVED HEADING');
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.draft_saved', 'actor_id' => $user->id]);
        $content['contact']['whatsapp'] = 'javascript:alert(1)';
        $this->put('/admin/homepage', ['content' => $content, 'version' => 1])->assertSessionHasErrors('content.contact.whatsapp');
    }
}
