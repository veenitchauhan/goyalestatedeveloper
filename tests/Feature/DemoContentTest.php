<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\SiteSetting;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_dataset_populates_pages_without_overwriting_and_can_be_removed(): void
    {
        Storage::fake('local');
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        $original = ContentEntry::where('type', 'homepage')->firstOrFail()->published_revision_id;
        $this->artisan('demo:content')->assertSuccessful();
        $count = ContentEntry::count();
        $this->artisan('demo:content')->assertSuccessful();
        $this->assertSame($count, ContentEntry::count());
        foreach (['/about', '/projects', '/locations', '/careers', '/blog', '/knowledge-bank', '/faqs', '/developments', '/campaign/demo-project-consultation'] as $path) {
            $this->get($path)->assertOk()->assertSee('Demo');
        }
        $prefixes = ['company_page' => '/about/', 'business_unit' => '/business/', 'service' => '/business/services/', 'capability' => '/capabilities/', 'equipment' => '/capabilities/equipment/', 'team_member' => '/about/people/', 'company_milestone' => '/about/milestones/', 'employee_story' => '/about/employee-stories/', 'project' => '/projects/', 'location' => '/locations/', 'job' => '/careers/', 'blog' => '/blog/', 'knowledge' => '/knowledge-bank/', 'faq' => '/faqs/', 'page' => '/pages/', 'campaign' => '/campaign/', 'development' => '/developments/'];
        foreach (ContentEntry::where('slug', 'like', 'demo-%')->get() as $entry) {
            if (isset($prefixes[$entry->type])) {
                $this->get($prefixes[$entry->type].$entry->slug)->assertOk();
            }
        }
        $this->assertSame($original, ContentEntry::where('type', 'homepage')->firstOrFail()->published_revision_id);
        $this->artisan('demo:content', ['--remove' => true])->assertSuccessful();
        $this->assertFalse(SiteSetting::where('key', 'demo_content')->exists());
        $this->assertDatabaseCount('enquiries', 0);
        $this->assertDatabaseCount('candidates', 0);
        $this->get('/projects')->assertDontSee('Demo — Commercial campus');
        $this->get('/developments')->assertNotFound();
    }

    public function test_existing_dataset_gains_blogs_without_overwriting_existing_content(): void
    {
        Storage::fake('local');
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        $this->artisan('demo:content')->assertSuccessful();
        $blog = ContentEntry::where('type', 'blog')->firstOrFail();
        $title = $blog->title;
        $blog->delete();
        $project = ContentEntry::where('type', 'project')->firstOrFail();
        $project->update(['title' => 'My edited project']);
        $projectCount = ContentEntry::where('type', 'project')->count();
        $this->artisan('demo:content')->assertSuccessful();
        $this->assertSame('My edited project', $project->fresh()->title);
        $this->assertSame($projectCount, ContentEntry::where('type', 'project')->count());
        $this->assertSame(3, ContentEntry::where('type', 'blog')->count());
        $this->get('/blog')->assertOk()->assertSee($title);
    }

    public function test_demo_creation_is_refused_in_production(): void
    {
        $before = ContentEntry::count();
        $this->app->instance('env', 'production');
        $this->artisan('demo:content')->assertFailed();
        $this->assertDatabaseCount('content_entries', $before);
        Storage::fake('local');
        $this->artisan('demo:content', ['--force' => true])->assertSuccessful();
        $this->assertSame(3, ContentEntry::where('type', 'blog')->whereNotNull('published_revision_id')->count());
        $this->assertSame(3, ContentEntry::where('type', 'job')->whereNotNull('published_revision_id')->count());
        $this->assertSame(3, ContentEntry::where('type', 'project')->whereNotNull('published_revision_id')->count());
    }
}
