<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Media;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user);
    }

    public function test_settings_require_review_and_approval_and_only_publish_changes_afterwards(): void
    {
        $this->get('/admin/settings')->assertOk()->assertSee('Website settings.');
        $settings = SiteSetting::current();
        $settings['contact']['email'] = 'office@example.test';
        $this->put('/admin/settings', ['version' => 1, 'settings' => $settings])->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('office@example.test');
        $entry = ContentEntry::where('type', 'settings')->firstOrFail();
        $this->post(route('admin.content.transition', $entry), ['version' => 2, 'action' => 'publish'])->assertSessionHasErrors('action');
        $this->get(route('admin.content.preview', $entry))->assertOk()->assertSee('office@example.test');
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post(route('admin.content.transition', $entry), ['version' => 2, 'action' => $action])->assertSessionHasNoErrors();
        }
        $this->get('/')->assertOk()->assertSee('office@example.test');
        $this->get('/admin/audit')->assertOk()->assertSee('office@example.test');
        $this->put('/admin/settings', ['version' => 1, 'settings' => $settings])->assertSessionHasErrors('version');
    }

    public function test_private_images_and_invalid_contact_urls_are_rejected_in_settings(): void
    {
        $settings = SiteSetting::current();
        $settings['branding']['logo_id'] = Media::factory()->create(['is_public' => false])->id;
        $settings['social']['linkedin'] = 'javascript:alert(1)';
        $this->put('/admin/settings', ['version' => 1, 'settings' => $settings])->assertSessionHasErrors(['settings.branding.logo_id', 'settings.social.linkedin']);
    }

    public function test_archiving_content_removes_it_and_restoring_does_not_republish_it(): void
    {
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $this->post(route('admin.content.transition', $entry), ['version' => 1, 'action' => 'archive'])->assertSessionHasErrors('action');
        $this->post('/admin/content', ['version' => 0, 'type' => 'page', 'slug' => 'archive-test', 'title' => 'Archive test', 'body' => 'Published body', 'placement' => 'both', 'order' => 0])->assertSessionHasNoErrors();
        $entry = ContentEntry::where('slug', 'archive-test')->firstOrFail();
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post(route('admin.content.transition', $entry), ['version' => 1, 'action' => $action])->assertSessionHasNoErrors();
        }
        $this->get('/pages/archive-test')->assertOk();
        foreach (['archive', 'restore'] as $action) {
            $this->post(route('admin.content.transition', $entry), ['version' => 1, 'action' => $action])->assertSessionHasNoErrors();
            $this->get('/pages/archive-test')->assertNotFound();
        }
        $this->assertSame('draft', $entry->fresh()->status);
    }

    public function test_shared_cta_updates_all_referencing_pages_and_documents_require_publication(): void
    {
        $base = ['version' => 0, 'placement' => 'both', 'order' => 0];
        $this->post('/admin/content', $base + ['type' => 'cta', 'slug' => 'shared-contact', 'title' => 'Discuss your project', 'url' => '/#contact'])->assertSessionHasNoErrors();
        $cta = ContentEntry::where('slug', 'shared-contact')->firstOrFail();
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post(route('admin.content.transition', $cta), ['version' => 1, 'action' => $action])->assertSessionHasNoErrors();
        }
        $private = Media::factory()->create(['mime' => 'application/pdf', 'is_public' => false]);
        $page = $base + ['type' => 'page', 'slug' => 'reusable-test', 'title' => 'Reuse test', 'body' => 'Our information', 'cta_ids' => [$cta->id], 'document_ids' => [$private->id]];
        $this->post('/admin/content', $page)->assertSessionHasErrors('document_ids.0');
        unset($page['document_ids']);
        $this->post('/admin/content', $page)->assertSessionHasNoErrors();
        $entry = ContentEntry::where('slug', 'reusable-test')->firstOrFail();
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post(route('admin.content.transition', $entry), ['version' => 1, 'action' => $action])->assertSessionHasNoErrors();
        }
        $this->get('/pages/reusable-test')->assertSee('Discuss your project');
        $this->post(route('admin.content.transition', $cta), ['version' => 1, 'action' => 'archive'])->assertSessionHasNoErrors();
        $this->get('/pages/reusable-test')->assertDontSee('Discuss your project');
        foreach (['page', 'block', 'statistic', 'menu', 'cta'] as $type) {
            $this->get('/admin/content/create?type='.$type)->assertOk();
        }
    }

    public function test_media_archive_preserves_original_reference_and_restore_requires_republication(): void
    {
        $media = Media::factory()->create(['is_public' => true, 'publication_status' => 'published']);
        $original = $media->original_path;
        $this->post(route('admin.media.archive', $media), ['action' => 'archive'])->assertSessionHasNoErrors();
        $this->assertNotNull($media->fresh()->archived_at);
        auth()->logout();
        $this->get(route('media.show', $media))->assertNotFound();
        $user = User::firstOrFail();
        $this->actingAs($user);
        $this->post(route('admin.media.archive', $media), ['action' => 'restore'])->assertSessionHasNoErrors();
        $this->assertSame('draft', $media->fresh()->publication_status);
        $this->assertSame($original, $media->fresh()->original_path);
        $this->assertNull(SiteSetting::image($media->id));
    }
}
