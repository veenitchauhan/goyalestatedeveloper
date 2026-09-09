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

class ProjectImagesTest extends TestCase
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

    private function data(array $overrides = []): array
    {
        return array_replace(['version' => 0, 'title' => 'Five photo project', 'city' => 'Test city', 'status' => 'Ongoing', 'sector' => 'Buildings', 'stage' => 'Structure', 'progress' => 50, 'client_approved' => false, 'value_approved' => false, 'featured' => true, 'verified' => true, 'source_note' => 'Test approval', 'description' => 'Test project description', 'scope' => 'Test scope', 'image_selection' => 1], $overrides);
    }

    private function images(int $count): array
    {
        return array_map(fn ($number) => UploadedFile::fake()->image('site-'.$number.'.jpg', 100, 100), range(1, $count));
    }

    public function test_create_and_edit_share_images_preserve_data_and_publish_the_cover(): void
    {
        $this->post('/admin/projects', $this->data(['images' => $this->images(5)]))->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'project')->firstOrFail();
        $payload = $entry->revisions()->firstOrFail()->payload;
        $ids = array_column($payload['gallery'], 'media_id');
        $this->assertCount(5, $ids);
        $this->assertSame($ids[0], $payload['cover_media_id']);
        $this->assertDatabaseCount('media', 5);
        $this->get('/admin/projects/create')->assertOk()->assertSee('Project images');
        $this->get('/admin/projects/'.$entry->id.'/edit')->assertOk()->assertSee('site-1.jpg')->assertSee('Project images');
        $this->put('/admin/projects/'.$entry->id, $this->data(['version' => 1, 'title' => 'Updated project', 'keep_images' => $ids]))->assertSessionHasNoErrors();
        $updated = $entry->revisions()->latest('version')->firstOrFail()->payload;
        $this->assertSame($ids, array_column($updated['gallery'], 'media_id'));
        $this->assertSame('Updated project', $updated['title']);
        $this->assertSame('Test city', $updated['city']);
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post('/admin/projects/'.$entry->id.'/status', ['version' => 2, 'action' => $action])->assertSessionHasNoErrors();
        }
        $this->get('/projects/'.$entry->slug)->assertOk()->assertSee(route('media.show', $ids[0]));
        $this->put('/admin/projects/'.$entry->id, $this->data(['version' => 2, 'keep_images' => array_slice($ids, 1), 'images' => $this->images(1)]))->assertSessionHasNoErrors();
        $replacement = $entry->revisions()->latest('version')->firstOrFail()->payload;
        $this->assertCount(5, $replacement['gallery']);
        $this->assertSame($ids[1], $replacement['cover_media_id']);
        Storage::disk('local')->assertExists(Media::findOrFail($ids[0])->original_path);
        $this->get('/projects/'.$entry->slug)->assertSee(route('media.show', $ids[0]));
    }

    public function test_card_replacements_preserve_order_and_deletions_keep_original_media(): void
    {
        $this->post('/admin/projects', $this->data(['image_slots' => 1, 'images' => [0 => $this->images(1)[0], 4 => $this->images(1)[0]]]))->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'project')->firstOrFail();
        $ids = array_column($entry->revisions()->firstOrFail()->payload['gallery'], 'media_id');
        $this->put('/admin/projects/'.$entry->id, $this->data(['version' => 1, 'image_slots' => 1, 'keep_images' => [1 => $ids[1]], 'images' => [0 => $this->images(1)[0]]]))->assertSessionHasNoErrors();
        $payload = $entry->revisions()->latest('version')->firstOrFail()->payload;
        $this->assertCount(2, $payload['gallery']);
        $this->assertNotContains($payload['cover_media_id'], $ids);
        $this->assertSame($payload['cover_media_id'], $payload['gallery'][0]['media_id']);
        $this->assertSame($ids[1], $payload['gallery'][1]['media_id']);
        $this->put('/admin/projects/'.$entry->id, $this->data(['version' => 2, 'image_slots' => 1]))->assertSessionHasNoErrors();
        $empty = $entry->revisions()->latest('version')->firstOrFail()->payload;
        $this->assertSame([], $empty['gallery']);
        $this->assertNull($empty['cover_media_id']);
        $this->assertDatabaseCount('media', 3);
        Storage::disk('local')->assertExists(Media::findOrFail($ids[0])->original_path);
    }

    public function test_cards_reject_out_of_range_slots_and_two_images_in_one_slot(): void
    {
        $this->post('/admin/projects', $this->data(['image_slots' => 1, 'images' => [5 => $this->images(1)[0]]]))->assertSessionHasErrors('images');
        $this->assertDatabaseCount('media', 0);
        $this->post('/admin/projects', $this->data(['images' => $this->images(1)]))->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'project')->firstOrFail();
        $id = $entry->revisions()->firstOrFail()->payload['cover_media_id'];
        $this->put('/admin/projects/'.$entry->id, $this->data(['version' => 1, 'image_slots' => 1, 'keep_images' => [0 => $id], 'images' => [0 => $this->images(1)[0]]]))->assertSessionHasErrors('images');
        $this->assertDatabaseCount('media', 1);
        $this->assertSame(1, $entry->revisions()->count());
    }

    public function test_limit_applies_to_existing_plus_new_images_and_failed_saves_leave_no_files(): void
    {
        $this->post('/admin/projects', $this->data(['images' => $this->images(6)]))->assertSessionHasErrors('images');
        $this->assertDatabaseCount('media', 0);
        $this->post('/admin/projects', $this->data(['images' => $this->images(5)]))->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'project')->firstOrFail();
        $ids = array_column($entry->revisions()->firstOrFail()->payload['gallery'], 'media_id');
        $files = Storage::disk('local')->allFiles();
        $this->put('/admin/projects/'.$entry->id, $this->data(['version' => 1, 'keep_images' => $ids, 'images' => $this->images(1)]))->assertSessionHasErrors('images');
        $this->put('/admin/projects/'.$entry->id, $this->data(['version' => 0, 'keep_images' => array_slice($ids, 1), 'images' => $this->images(1)]))->assertSessionHasErrors('version');
        $this->assertDatabaseCount('media', 5);
        $this->assertSame($files, Storage::disk('local')->allFiles());
        $this->assertSame(1, $entry->revisions()->count());
    }

    public function test_unrelated_media_cannot_be_retained_and_uploads_require_permission(): void
    {
        $this->post('/admin/projects', $this->data())->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'project')->firstOrFail();
        $other = Media::factory()->create(['is_public' => false]);
        $this->put('/admin/projects/'.$entry->id, $this->data(['version' => 1, 'keep_images' => [$other->id]]))->assertSessionHasErrors('keep_images.0');
        $editor = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $editor->roles()->attach(Role::where('name', 'project-editor')->firstOrFail());
        $entry->assignees()->attach($editor);
        $this->actingAs($editor)->put('/admin/projects/'.$entry->id, $this->data(['version' => 1, 'images' => $this->images(1)]))->assertForbidden();
    }
}
