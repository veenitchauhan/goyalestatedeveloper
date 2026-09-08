<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Role;
use App\Models\SeoPage;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, HomepageSeeder::class]);
    }

    private function login(string $role = 'seo-manager'): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->firstOrFail());
        $this->actingAs($user);
    }

    private function published(string $type, string $slug, array $payload): ContentEntry
    {
        $entry = ContentEntry::factory()->create(['type' => $type, 'slug' => $slug, 'title' => $payload['title']]);
        $revision = $entry->revisions()->create(['version' => 1, 'payload' => ['slug' => $slug, ...$payload]]);
        $entry->update(['published_revision_id' => $revision->id, 'status' => 'published', 'published_at' => now()]);

        return $entry;
    }

    private function enableProductionIndexing(): void
    {
        $this->app->instance('env', 'production');
        $settings = SiteSetting::current();
        $settings['seo']['robots'] = 'index,follow';
        SiteSetting::updateOrCreate(['key' => 'global'], ['data' => $settings]);
    }

    public function test_local_indexing_stays_disabled_even_when_page_allows_it(): void
    {
        $this->login();
        $this->put('/admin/seo', ['path' => '/', 'version' => 0, 'title' => 'Approved SEO title', 'description' => 'Approved description', 'indexable' => true, 'schema_enabled' => true])->assertSessionHasNoErrors();
        $this->get('/')->assertOk()->assertSee('<title>Approved SEO title</title>', false)->assertSee('noindex,nofollow', false)->assertSee('twitter:card', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('<loc>', false);
        $this->get('/admin/seo')->assertOk();
        $this->get('/admin/seo/edit?path=%2F')->assertOk();
        $this->put('/admin/seo', ['path' => '/', 'version' => 0, 'indexable' => true, 'schema_enabled' => true])->assertSessionHasErrors('version');
    }

    public function test_sitemap_excludes_unpublished_noindex_and_noncanonical_pages(): void
    {
        $this->enableProductionIndexing();
        $published = $this->published('page', 'approved', ['title' => 'Approved page', 'body' => 'Approved body']);
        $this->published('page', 'duplicate', ['title' => 'Duplicate', 'body' => 'Body']);
        ContentEntry::factory()->create(['type' => 'page', 'slug' => 'hidden-draft']);
        SeoPage::factory()->create(['path' => '/pages/duplicate', 'data' => ['canonical_path' => '/pages/approved']]);
        SeoPage::factory()->create(['path' => '/contact', 'data' => ['indexable' => false]]);
        $response = $this->get('/sitemap.xml')->assertOk()->assertSee('/pages/approved')->assertDontSee('/pages/duplicate')->assertDontSee('/contact')->assertDontSee('hidden-draft')->assertDontSee('/admin');
        $this->assertNotFalse(simplexml_load_string($response->getContent()));
        $this->get('/robots.txt')->assertSee('Sitemap:');
        $published->update(['published_revision_id' => null]);
        $this->get('/sitemap.xml')->assertDontSee('/pages/approved')->assertSee('/pages/duplicate');
        $this->get('/pages/duplicate')->assertSee('href="'.url('/pages/duplicate').'"', false);
    }

    public function test_redirects_cannot_replace_active_pages_escape_site_or_form_loops(): void
    {
        $this->login();
        $this->post('/admin/seo/redirects', ['source' => '/old-page', 'destination' => '/about', 'status' => 301])->assertSessionHasNoErrors();
        $this->get('/old-page?private=value')->assertRedirect(url('/about'))->assertStatus(301);
        $this->post('/admin/seo/redirects', ['source' => '/about', 'destination' => '/', 'status' => 301])->assertSessionHasErrors('source');
        $this->post('/admin/seo/redirects', ['source' => '/admin/anything', 'destination' => '/', 'status' => 301])->assertSessionHasErrors('source');
        $this->post('/admin/seo/redirects', ['source' => '/another-old', 'destination' => 'https://outside.example', 'status' => 302])->assertSessionHasErrors('destination');
        $this->post('/admin/seo/redirects', ['source' => '/another-old', 'destination' => '/old-page', 'status' => 302])->assertSessionHasErrors('destination');
        $this->assertDatabaseCount('seo_redirects', 1);
    }

    public function test_schema_matches_public_faq_and_safely_encodes_script_like_text(): void
    {
        $entry = $this->published('faq', 'test-answer', ['title' => 'A real question?', 'short_answer' => 'Visible short answer', 'body' => 'Answer </script><script>alert(1)</script>', 'category' => 'Company', 'schema_enabled' => true, 'source_note' => 'PRIVATE EVIDENCE', 'author' => 'Test Author', 'reviewer' => 'Test Reviewer']);
        $response = $this->get('/faqs/test-answer')->assertOk()->assertDontSee('PRIVATE EVIDENCE')->assertDontSee('</script><script>alert', false);
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $response->getContent(), $matches);
        $schema = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $faq = collect($schema['@graph'])->firstWhere('@type', 'FAQPage');
        $this->assertSame('A real question?', $faq['mainEntity'][0]['name']);
        $this->assertStringContainsString('Visible short answer', $faq['mainEntity'][0]['acceptedAnswer']['text']);
        $this->login('super-admin');
        $this->get('/admin/knowledge/'.$entry->id.'/preview')->assertOk()->assertDontSee('application/ld+json', false)->assertSee('noindex,nofollow', false);
    }

    public function test_job_schema_requires_verified_location_fields_and_omits_private_salary(): void
    {
        $job = $this->published('job', 'engineer', ['title' => 'Test Engineer', 'department' => 'Engineering', 'job_type' => 'Permanent', 'employment_type' => 'Full-time', 'location' => 'Test city', 'description' => 'Role description', 'responsibilities' => 'Role duties', 'requirements' => 'Role requirements', 'salary' => 'PRIVATE SALARY', 'salary_public' => false, 'deadline' => now()->addWeek()->toDateString()]);
        $this->get('/careers/engineer')->assertOk()->assertDontSee('JobPosting')->assertDontSee('PRIVATE SALARY');
        $revision = $job->publishedRevision;
        $revision->update(['payload' => [...$revision->payload, 'address_country' => 'IN', 'address_locality' => 'Test city']]);
        $this->get('/careers/engineer')->assertOk()->assertSee('JobPosting')->assertSee('Work location')->assertDontSee('PRIVATE SALARY');
        $this->travel(8)->days();
        $this->get('/careers/engineer')->assertNotFound();
        $this->travelBack();
    }

    public function test_unauthorized_users_cannot_change_seo_and_canonical_loops_are_rejected(): void
    {
        $this->login('viewer');
        $this->get('/admin/seo')->assertForbidden();
        $this->put('/admin/seo', [])->assertForbidden();
        $this->login();
        $this->put('/admin/seo', ['path' => '/about', 'canonical_path' => '/business', 'version' => 0, 'indexable' => true, 'schema_enabled' => true])->assertSessionHasNoErrors();
        $this->put('/admin/seo', ['path' => '/business', 'canonical_path' => '/about', 'version' => 0, 'indexable' => true, 'schema_enabled' => true])->assertSessionHasErrors('canonical_path');
        $this->put('/admin/seo', ['path' => '/made-up-page', 'version' => 0, 'indexable' => true, 'schema_enabled' => true])->assertSessionHasErrors('path');
    }
}
