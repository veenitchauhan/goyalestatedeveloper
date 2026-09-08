<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\ContentEntry;
use App\Models\Enquiry;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CampaignAnalyticsTest extends TestCase
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

    private function payload(): array
    {
        return ['title' => 'Test campaign', 'slug' => 'test-campaign', 'headline' => 'Approved campaign headline', 'subheadline' => 'Approved summary', 'body' => 'Verified campaign body.', 'source_note' => 'PRIVATE CAMPAIGN EVIDENCE', 'form_type' => 'Construction', 'cta_label' => 'Discuss your project', 'verified' => true, 'version' => 0];
    }

    private function campaign(): ContentEntry
    {
        $this->login();
        $this->post('/admin/campaigns', $this->payload())->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'campaign')->firstOrFail();
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post('/admin/campaigns/'.$entry->id.'/status', ['action' => $action, 'version' => 1])->assertSessionHasNoErrors();
        }

        return $entry;
    }

    public function test_campaign_draft_isolation_publication_and_marketer_approval_boundary(): void
    {
        $entry = $this->campaign();
        $this->get('/campaign/test-campaign')->assertOk()->assertSee('Approved campaign headline')->assertDontSee('PRIVATE CAMPAIGN EVIDENCE')->assertSee('utm_campaign=test-campaign', false);
        $this->get('/admin/campaigns/'.$entry->id.'/edit')->assertOk();
        $this->get('/admin/campaigns/'.$entry->id.'/preview')->assertOk()->assertSee('noindex,nofollow', false);
        $this->login('digital-marketing-manager');
        $this->put('/admin/campaigns/'.$entry->id, array_replace($this->payload(), ['version' => 1, 'headline' => 'PRIVATE NEW HEADLINE']))->assertSessionHasNoErrors();
        $this->get('/campaign/test-campaign')->assertDontSee('PRIVATE NEW HEADLINE');
        $this->post('/admin/campaigns/'.$entry->id.'/status', ['action' => 'approve', 'version' => 2])->assertForbidden();
        $this->post('/admin/campaigns/'.$entry->id.'/status', ['action' => 'publish', 'version' => 2])->assertForbidden();
        $this->login('viewer');
        $this->get('/admin/campaigns')->assertForbidden();
        $this->get('/admin/analytics')->assertForbidden();
        $this->get('/admin/integrations/knowledge')->assertForbidden();
    }

    public function test_consent_controls_page_views_attribution_conversion_and_withdrawal(): void
    {
        $this->campaign();
        $this->post('/logout');
        $this->get('/campaign/test-campaign?utm_source=first-source')->assertOk();
        $this->assertDatabaseCount('analytics_events', 0);
        $this->post('/privacy-preferences', ['analytics' => 1])->assertRedirect('/privacy-preferences');
        $this->get('/campaign/test-campaign?utm_source=first-source')->assertOk();
        $this->get('/contact?utm_source=second-source')->assertOk();
        $this->post('/contact', ['name' => 'Private Person', 'email' => 'private@example.test', 'type' => 'Construction', 'location' => 'Test city', 'message' => 'An approved test requirement.', 'consent' => 1])->assertSessionHasNoErrors();
        $lead = Enquiry::firstOrFail();
        $this->assertSame('first-source', $lead->attribution['first_touch']['utm_source']);
        $this->assertSame('second-source', $lead->attribution['last_touch']['utm_source']);
        $this->assertSame(1, AnalyticsEvent::where('event', 'enquiry')->count());
        $this->assertStringNotContainsString('private@example.test', AnalyticsEvent::all()->toJson());
        $this->post('/privacy-preferences', ['analytics' => 0])->assertRedirect('/privacy-preferences');
        $this->get('/')->assertOk();
        $this->assertDatabaseCount('analytics_events', 0);
        $this->assertDatabaseCount('enquiries', 1);
    }

    public function test_preview_errors_and_private_routes_do_not_generate_analytics(): void
    {
        $entry = $this->campaign();
        $this->withSession(['analytics_consent' => true]);
        $this->get('/admin/campaigns/'.$entry->id.'/preview')->assertOk();
        $this->get('/campaign/missing')->assertNotFound();
        $this->get('/admin/analytics')->assertOk();
        $this->assertDatabaseCount('analytics_events', 0);
        $this->get('/privacy-preferences')->assertOk()->assertSee('Decline analytics');
    }

    private function signed(array $payload, ?int $timestamp = null, ?string $signature = null): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp ??= time();
        $signature ??= hash_hmac('sha256', $timestamp.'.'.$body, str_repeat('k', 32));

        return $this->call('POST', '/integrations/lead-events', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_BRIDGE_TIMESTAMP' => (string) $timestamp, 'HTTP_X_BRIDGE_SIGNATURE' => $signature], $body);
    }

    private function providerPayload(): array
    {
        return ['event_id' => 'provider-lead-1', 'channel' => 'whatsapp', 'name' => 'Provider prospect', 'email' => 'prospect@example.test', 'location' => 'Test city', 'message' => 'Test final lead submission', 'consent' => true];
    }

    public function test_signed_adapter_rejects_invalid_stale_and_changed_replays(): void
    {
        $payload = $this->providerPayload();
        $this->signed($payload)->assertNotFound();
        config(['services.lead_bridge.enabled' => true, 'services.lead_bridge.secret' => str_repeat('k', 32)]);
        $this->signed($payload, signature: 'invalid')->assertUnauthorized();
        $this->signed($payload, time() - 301)->assertUnauthorized();
        $this->signed($payload)->assertCreated()->assertJson(['duplicate' => false]);
        $this->signed($payload)->assertOk()->assertJson(['duplicate' => true]);
        $this->signed(array_replace($payload, ['message' => 'Changed event']))->assertStatus(409);
        $this->assertDatabaseCount('enquiries', 1);
        $this->assertDatabaseCount('integration_events', 1);
        $this->signed(array_replace($payload, ['event_id' => 'provider-lead-2', 'consent' => false]))->assertUnprocessable();
        $this->assertDatabaseCount('enquiries', 1);
        Enquiry::firstOrFail()->delete();
        $this->signed($payload)->assertOk()->assertJson(['duplicate' => true]);
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_analytics_exposes_aggregates_not_lead_contacts_and_knowledge_index_is_published_only(): void
    {
        Enquiry::factory()->create(['name' => 'PRIVATE CUSTOMER', 'email' => 'secret@example.test']);
        $this->login('digital-marketing-manager');
        $this->get('/admin/analytics')->assertOk()->assertDontSee('PRIVATE CUSTOMER')->assertDontSee('secret@example.test');
        $this->get('/admin/integrations')->assertForbidden();
        $this->login();
        $draft = ContentEntry::factory()->create(['type' => 'knowledge']);
        $draft->revisions()->create(['version' => 1, 'payload' => ['title' => 'PRIVATE KNOWLEDGE']]);
        $this->get('/admin/integrations/knowledge')->assertOk()->assertDontSee('PRIVATE KNOWLEDGE');
    }
}
