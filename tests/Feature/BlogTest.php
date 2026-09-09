<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_posts_are_managed_in_cms_and_only_published_posts_are_public(): void
    {
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        $this->get('/blog')->assertOk()->assertSee('Our blog is coming soon.');
        $this->get('/')->assertSee(route('knowledge.blog.index'));
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user)->get('/admin/knowledge/create')->assertOk()->assertSee('value="blog"', false);
        $payload = ['type' => 'blog', 'title' => 'Planning a construction project', 'slug' => 'planning-a-project', 'category' => 'Construction', 'short_answer' => 'Start with a clear brief.', 'body' => 'A clear project brief helps the team plan the work and understand the requirements.', 'author' => 'Editorial team', 'reviewer' => 'Project team', 'source_note' => 'Approved for this test', 'verified' => true, 'featured' => false, 'schema_enabled' => true, 'order' => 1, 'version' => 0];
        $this->post('/admin/knowledge', $payload)->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'blog')->firstOrFail();
        $this->get('/blog')->assertDontSee($payload['title']);
        $this->get('/blog/planning-a-project')->assertNotFound();
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post(route('admin.content.transition', $entry), ['action' => $action, 'version' => 1])->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->get('/blog')->assertOk()->assertSee($payload['title']);
        $this->get('/blog/planning-a-project')->assertOk()->assertSee($payload['body']);
        $this->get('/insights')->assertNotFound();
    }
}
