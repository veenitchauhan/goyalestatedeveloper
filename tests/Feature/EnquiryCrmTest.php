<?php

namespace Tests\Feature;

use App\Jobs\NotifyEnquiryOwner;
use App\Mail\EnquiryReceived;
use App\Models\ContentEntry;
use App\Models\Enquiry;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\EnquiryForms;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EnquiryCrmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, HomepageSeeder::class]);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->firstOrFail());

        return $user;
    }

    private function payload(): array
    {
        return ['name' => 'Test prospect', 'email' => 'prospect@example.test', 'phone' => '+91 1234567890', 'location' => 'Test location', 'type' => 'Construction', 'message' => 'A test construction requirement.', 'consent' => 1, 'utm_source' => 'test-source', 'utm_campaign' => 'test-campaign', 'cta' => 'contact', 'details' => ['company' => 'Test company']];
    }

    public function test_public_capture_routes_lead_and_queues_only_opted_in_owner(): void
    {
        Queue::fake();
        $owner = $this->user('business-development');
        $settings = EnquiryForms::current();
        $settings['forms']['Construction']['assigned_to'] = $owner->id;
        $settings['forms']['Construction']['notify_owner'] = true;
        SiteSetting::create(['key' => 'enquiry_forms', 'data' => $settings]);
        $this->get('/contact?type=Construction&utm_campaign=test')->assertOk()->assertSee('Construction enquiry');
        $this->post('/contact', $this->payload())->assertRedirect('/contact')->assertSessionHas('enquiry_sent');
        $lead = Enquiry::firstOrFail();
        $this->assertSame($owner->id, $lead->assigned_to);
        $this->assertSame('Test company', $lead->details['company']);
        $this->assertSame('test-campaign', $lead->attribution['utm_campaign']);
        $this->assertSame('new', $lead->status);
        Queue::assertPushed(NotifyEnquiryOwner::class, fn ($job) => $job->enquiryId === $lead->id);
        $this->actingAs($owner)->get('/admin/enquiries/'.$lead->id)->assertOk()->assertSee('Test company');
    }

    public function test_disabled_required_spam_and_private_project_inputs_are_rejected(): void
    {
        Queue::fake();
        $settings = EnquiryForms::current();
        $settings['forms']['Construction']['required_fields'] = ['company'];
        $settings['forms']['Vendor']['enabled'] = false;
        SiteSetting::create(['key' => 'enquiry_forms', 'data' => $settings]);
        $this->post('/contact', array_replace($this->payload(), ['details' => []]))->assertSessionHasErrors('details.company');
        $this->post('/contact', array_replace($this->payload(), ['type' => 'Vendor']))->assertSessionHasErrors('type');
        $this->post('/contact', array_replace($this->payload(), ['website' => 'spam']))->assertSessionHasErrors('website');
        $this->post('/contact', array_replace($this->payload(), ['content_entry_id' => 99999]))->assertSessionHasErrors('content_entry_id');
        $this->post('/contact', array_replace($this->payload(), ['consent' => 0]))->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('enquiries', 0);
        Queue::assertNothingPushed();
    }

    public function test_assigned_user_can_add_history_but_not_access_other_leads_assign_or_export(): void
    {
        $owner = $this->user('business-development');
        $mine = Enquiry::factory()->create(['assigned_to' => $owner->id, 'name' => 'Needle mine']);
        $other = Enquiry::factory()->create(['name' => 'Needle private']);
        $this->actingAs($owner)->get('/admin/enquiries')->assertOk()->assertSee('Needle mine')->assertDontSee('Needle private');
        $this->get('/admin/search?q=Needle')->assertOk()->assertSee('Needle mine')->assertDontSee('Needle private');
        $this->get('/admin/enquiries/'.$other->id)->assertForbidden();
        $update = ['version' => 1, 'status' => 'contacted', 'qualification' => 'pending', 'note' => 'Discussed the scope', 'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s')];
        $this->put('/admin/enquiries/'.$other->id, $update)->assertForbidden();
        $this->put('/admin/enquiries/'.$mine->id, $update + ['assigned_to' => $owner->id])->assertSessionHasErrors('assigned_to');
        $this->put('/admin/enquiries/'.$mine->id, $update)->assertSessionHasNoErrors()->assertRedirect('/admin/enquiries');
        $this->assertSame('contacted', $mine->fresh()->status);
        $this->assertDatabaseHas('enquiry_notes', ['enquiry_id' => $mine->id, 'body' => 'Discussed the scope']);
        $this->put('/admin/enquiries/'.$mine->id, $update)->assertSessionHasErrors('version');
        $this->assertSame(1, $mine->notes()->count());
        $this->get('/admin/enquiries/export')->assertForbidden();
        $this->get('/admin/enquiry-forms')->assertForbidden();
    }

    public function test_admin_reassignment_due_filter_and_export_are_enforced(): void
    {
        $owner = $this->user('business-development');
        $lead = Enquiry::factory()->create(['name' => '=SUM(1)', 'follow_up_at' => now()->subDay()]);
        Enquiry::factory()->create(['name' => 'Future prospect', 'follow_up_at' => now()->addDays(2)]);
        $this->actingAs($this->user('super-admin'));
        $this->get('/admin/enquiries?due=1')->assertOk()->assertSee('=SUM(1)')->assertDontSee('Future prospect');
        $this->put('/admin/enquiries/'.$lead->id, ['version' => 1, 'status' => 'qualified', 'qualification' => 'qualified', 'assigned_to' => $owner->id])->assertSessionHasNoErrors();
        $this->assertSame($owner->id, $lead->fresh()->assigned_to);
        $csv = $this->get('/admin/enquiries/export?q=SUM')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=SUM(1)", $csv);
        $this->assertStringNotContainsString('Future prospect', $csv);
        $this->get('/admin/enquiries/'.$lead->id)->assertOk()->assertSee('Activity & notes', false);
    }

    public function test_form_settings_preserve_validation_and_version_and_notifications_recheck_access(): void
    {
        $owner = $this->user('business-development');
        $this->actingAs($this->user('super-admin'));
        $settings = EnquiryForms::current();
        $settings['forms']['Construction']['assigned_to'] = $owner->id;
        $settings['forms']['Construction']['notify_owner'] = true;
        $this->get('/admin/enquiry-forms')->assertOk();
        $this->put('/admin/enquiry-forms', $settings)->assertSessionHasNoErrors();
        $this->put('/admin/enquiry-forms', $settings)->assertSessionHasErrors('version');
        $lead = Enquiry::factory()->create(['assigned_to' => $owner->id]);
        Mail::fake();
        (new NotifyEnquiryOwner($lead->id))->handle();
        Mail::assertSent(EnquiryReceived::class, fn ($mail) => $mail->hasTo($owner->email));
        $this->assertNotNull($lead->fresh()->notified_at);
        (new NotifyEnquiryOwner($lead->id))->handle();
        Mail::assertSentCount(1);
        $other = Enquiry::factory()->create(['assigned_to' => $owner->id]);
        $owner->forceFill(['is_active' => false])->save();
        (new NotifyEnquiryOwner($other->id))->handle();
        Mail::assertSentCount(1);
        $this->assertNull($other->fresh()->notified_at);
    }

    public function test_project_context_survives_validation_and_capture_ignores_hidden_fields(): void
    {
        Queue::fake();
        $project = ContentEntry::factory()->create(['type' => 'project']);
        $revision = $project->revisions()->create(['version' => 1, 'payload' => ['title' => 'Approved test project']]);
        $project->update(['published_revision_id' => $revision->id, 'status' => 'published']);
        $data = array_replace($this->payload(), ['type' => 'Project delivery', 'content_entry_id' => $project->id, 'consent' => 0]);
        $response = $this->post('/contact', $data)->assertSessionHasErrors('consent');
        $this->assertStringContainsString('project='.$project->id, $response->headers->get('Location'));
        $this->get('/contact?type=Project%20Enquiry&project='.$project->id)->assertOk()->assertSee('Approved test project');
        $data['consent'] = 1;
        $data['details']['ownership'] = 'Hidden field';
        $this->post('/contact', $data)->assertSessionHasNoErrors();
        $lead = Enquiry::firstOrFail();
        $this->assertSame('Project Enquiry', $lead->type);
        $this->assertSame($project->id, $lead->content_entry_id);
        $this->assertSame('Approved test project', $lead->details['project_title']);
        $this->assertArrayNotHasKey('ownership', $lead->details);
        Queue::assertNothingPushed();
        $project->update(['published_revision_id' => null]);
        $this->post('/contact', $data)->assertSessionHasErrors('content_entry_id');
        $this->assertDatabaseCount('enquiries', 1);
    }
}
