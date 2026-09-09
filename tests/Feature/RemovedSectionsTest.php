<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Homepage;
use App\Models\Role;
use App\Models\User;
use App\Services\CorporateContent;
use App\Services\KnowledgeContent;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemovedSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_sections_are_unavailable_and_remaining_pages_work(): void
    {
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        foreach (['/business', '/business/services/test', '/capabilities', '/capabilities/equipment', '/insights', '/insights/test'] as $url) {
            $this->get($url)->assertNotFound();
        }
        foreach (['/', '/about', '/projects', '/knowledge-bank', '/faqs', '/login'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/')->assertSee('goyal-favicon.png')->assertDontSee('href="http://localhost/business"', false);
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user)->get('/admin/corporate')->assertOk()->assertDontSee('Business areas')->assertDontSee('Equipment & machinery');
        $this->get('/admin/knowledge')->assertOk()->assertDontSee('Insights & Project Stories');
        $this->assertFalse(CorporateContent::supports('business_unit'));
        $this->assertFalse(KnowledgeContent::supports('article'));
    }

    public function test_cleanup_deletes_removed_content_and_revisions_but_preserves_other_content(): void
    {
        $this->seed(HomepageSeeder::class);
        $ids = [];
        foreach (['business_unit', 'service', 'capability', 'equipment', 'article', 'knowledge'] as $type) {
            $entry = ContentEntry::create(['type' => $type, 'slug' => 'test-'.$type, 'title' => 'Test']);
            $revision = $entry->revisions()->create(['version' => 1, 'payload' => ['title' => 'Test']]);
            $entry->update(['published_revision_id' => $revision->id]);
            $ids[$type] = $entry->id;
        }
        $home = Homepage::main();
        $payload = $home->content;
        $payload['sections'][] = ['id' => 'business'];
        $payload['sections'][] = ['id' => 'insights'];
        $home->update(['content' => $payload]);
        $migration = require database_path('migrations/2026_09_09_105054_remove_business_capabilities_and_insights_content.php');
        $migration->up();
        foreach ($ids as $type => $id) {
            if ($type === 'knowledge') {
                $this->assertDatabaseHas('content_entries', ['id' => $id]);

                continue;
            }
            $this->assertDatabaseMissing('content_entries', ['id' => $id]);
            $this->assertDatabaseMissing('content_revisions', ['content_entry_id' => $id]);
        }
        $this->assertNotContains('business', array_column($home->fresh()->content['sections'], 'id'));
        $this->assertNotContains('insights', array_column($home->fresh()->content['sections'], 'id'));
    }
}
