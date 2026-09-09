<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectPanoramaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, HomepageSeeder::class]);
        Storage::fake('local');
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user);
    }

    private function image(int $width, int $height): Media
    {
        $path = UploadedFile::fake()->image('panorama.jpg', $width, $height)->store('test', 'local');

        return Media::factory()->create(['original_path' => $path, 'is_public' => true, 'publication_status' => 'published']);
    }

    private function payload(Media $media): array
    {
        return ['title' => 'Panorama test project', 'slug' => 'panorama-test', 'version' => 0, 'status' => 'Ongoing', 'sector' => 'Buildings', 'stage' => 'Structure', 'progress' => 50, 'featured' => false, 'client_approved' => false, 'value_approved' => false, 'verified' => true, 'project_type' => 'Construction', 'city' => 'Test city', 'progress_date' => now()->toDateString(), 'scope' => 'Test scope', 'description' => 'Test description', 'source_note' => 'Test approval', 'panorama_media_id' => $media->id, 'panorama_caption' => 'Approved test panorama'];
    }

    public function test_panorama_publishes_with_fallback_and_disappears_when_media_is_private(): void
    {
        $media = $this->image(800, 400);
        $this->post('/admin/projects', $this->payload($media))->assertSessionHasNoErrors();
        $entry = ContentEntry::where('slug', 'panorama-test')->firstOrFail();
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post('/admin/projects/'.$entry->id.'/status', ['action' => $action, 'version' => 1])->assertSessionHasNoErrors();
        }
        $this->get('/projects/panorama-test')->assertOk()->assertSee('Open interactive view')->assertSee('Approved test panorama')->assertSee(route('media.show', $media));
        $this->assertStringContainsString("script-src 'self'", $this->get('/projects/panorama-test')->headers->get('Content-Security-Policy'));
        $this->get('/admin/projects/'.$entry->id.'/edit')->assertOk()->assertDontSee('360° panorama');
        $media->update(['is_public' => false]);
        $this->get('/projects/panorama-test')->assertOk()->assertDontSee('Open interactive view')->assertDontSee(route('media.show', $media));
    }

    public function test_regular_photographs_and_missing_files_are_rejected_as_panoramas(): void
    {
        $media = $this->image(400, 400);
        $this->post('/admin/projects', $this->payload($media))->assertSessionHasErrors('panorama_media_id');
        $media = $this->image(800, 400);
        Storage::disk('local')->delete($media->original_path);
        $this->post('/admin/projects', $this->payload($media))->assertSessionHasErrors('panorama_media_id');
        $this->assertDatabaseMissing('content_entries', ['slug' => 'panorama-test']);
    }

    public function test_panorama_is_revalidated_at_publication(): void
    {
        $media = $this->image(800, 400);
        $this->post('/admin/projects', $this->payload($media))->assertSessionHasNoErrors();
        $entry = ContentEntry::where('slug', 'panorama-test')->firstOrFail();
        foreach (['review', 'approve'] as $action) {
            $this->post('/admin/projects/'.$entry->id.'/status', ['action' => $action, 'version' => 1])->assertSessionHasNoErrors();
        }
        Storage::disk('local')->delete($media->original_path);
        $this->post('/admin/projects/'.$entry->id.'/status', ['action' => 'publish', 'version' => 1])->assertSessionHasErrors('panorama_media_id');
        $this->assertNull($entry->fresh()->published_revision_id);
    }
}
