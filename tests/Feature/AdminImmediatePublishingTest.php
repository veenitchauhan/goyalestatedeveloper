<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Homepage;
use App\Models\Role;
use App\Models\User;
use App\Services\ContentPublisher;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminImmediatePublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_blog_saves_publish_immediately_and_invalid_changes_leave_live_content_intact(): void
    {
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'admin')->firstOrFail());
        $this->actingAs($user);
        $data = ['type' => 'blog', 'title' => 'Live blog title', 'slug' => 'live-blog', 'category' => 'Construction', 'short_answer' => 'Summary', 'body' => 'Published body', 'author' => 'Author', 'reviewer' => 'Admin', 'source_note' => 'Confirmed', 'verified' => true, 'featured' => false, 'schema_enabled' => true, 'order' => 1, 'version' => 0];
        $this->post('/admin/knowledge', $data)->assertRedirect()->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'blog')->firstOrFail();
        $this->assertSame('published', $entry->status);
        $this->get('/blog/live-blog')->assertOk()->assertSee('Published body');
        $this->get('/admin/knowledge/'.$entry->id.'/edit')->assertOk()->assertDontSee('Submit for review')->assertSee('No review or approval is required.');
        $this->put('/admin/knowledge/'.$entry->id, [...$data, 'version' => 1, 'body' => 'Updated live body'])->assertSessionHasNoErrors();
        $this->get('/blog/live-blog')->assertSee('Updated live body');
        $this->put('/admin/knowledge/'.$entry->id, [...$data, 'version' => 2, 'body' => ''])->assertSessionHasErrors('body');
        $this->assertSame(2, $entry->revisions()->count());
        $this->get('/blog/live-blog')->assertSee('Updated live body');
        $this->assertDatabaseCount('approval_events', 0);
    }

    public function test_admin_can_update_an_existing_faq_without_verification_or_author(): void
    {
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $entry = ContentEntry::create(['type' => 'faq', 'slug' => 'dummy', 'title' => 'Existing FAQ']);
        $entry->revisions()->create(['version' => 1, 'payload' => ['title' => 'Existing FAQ']]);
        $this->actingAs($user)->put('/admin/knowledge/'.$entry->id, ['version' => 1, 'type' => 'faq', 'title' => 'Updated FAQ question', 'slug' => 'dummy', 'category' => 'General', 'short_answer' => 'A short answer.', 'body' => 'The full answer.', 'verified' => false, 'author' => '', 'reviewer' => '', 'source_note' => '', 'featured' => false, 'schema_enabled' => false, 'order' => 0])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('published', $entry->fresh()->status);
        $this->get('/faqs/dummy')->assertOk()->assertSee('Updated FAQ question')->assertSee('The full answer.');
        $this->get('/')->assertSee('Updated FAQ question');
        $this->assertDatabaseCount('approval_events', 0);
    }

    public function test_project_upload_is_live_on_save_and_failed_save_rolls_back_files(): void
    {
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        Storage::fake('local');
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user);
        $data = ['version' => 0, 'title' => 'Immediate project', 'city' => 'Test city', 'status' => 'Ongoing', 'sector' => 'Buildings', 'stage' => 'Structure', 'progress' => 50, 'client_approved' => false, 'value_approved' => false, 'featured' => true, 'verified' => true, 'source_note' => 'Confirmed', 'description' => 'Project description', 'scope' => 'Project scope', 'image_selection' => 1, 'image_slots' => 1, 'images' => [UploadedFile::fake()->image('project.jpg')]];
        $this->post('/admin/projects', $data)->assertRedirect()->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'project')->firstOrFail();
        $this->get('/projects/'.$entry->slug)->assertOk()->assertSee('Immediate project');
        $this->get('/admin/projects/'.$entry->id.'/edit')->assertOk()->assertDontSee('Submit for review')->assertSee('No review or approval is required.');
        $files = Storage::disk('local')->allFiles();
        $data['version'] = 1;
        $data['scope'] = '';
        $data['images'] = [UploadedFile::fake()->image('rejected.jpg')];
        $this->put('/admin/projects/'.$entry->id, $data)->assertSessionHasErrors('scope');
        $this->assertSame($files, Storage::disk('local')->allFiles());
        $this->assertSame(1, $entry->revisions()->count());
    }

    public function test_super_admin_homepage_save_is_live_and_editor_save_remains_private(): void
    {
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user);
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $payload = Homepage::main()->content;
        $payload['hero']['line_one'] = 'Immediate homepage';
        app(ContentPublisher::class)->save($entry, $payload, 1);
        $this->assertSame('Immediate homepage', Homepage::main()->content['hero']['line_one']);
        $editor = User::factory()->create();
        $editor->roles()->attach(Role::where('name', 'content-manager')->firstOrFail());
        $this->actingAs($editor);
        $payload['hero']['line_one'] = 'Private editor draft';
        app(ContentPublisher::class)->save($entry, $payload, 2);
        $this->assertSame('Immediate homepage', Homepage::main()->content['hero']['line_one']);
    }
}
