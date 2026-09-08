<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function login(string $role): User
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    private function upload(ContentEntry $entry, array $extra = []): array
    {
        return array_replace(['project_entry_id' => $entry->id, 'file' => UploadedFile::fake()->image('site.jpg', 100, 100), 'title' => 'Approved site photo', 'alt' => 'Test construction image', 'category' => 'Projects', 'sort_order' => 0, 'publication_status' => 'draft', 'is_public' => 0, 'watermark' => ['enabled' => 0, 'position' => 'bottom-right', 'opacity' => 70, 'size' => 2, 'padding' => 10]], $extra);
    }

    public function test_project_upload_preserves_original_and_does_not_change_project_revision(): void
    {
        $this->login('super-admin');
        $entry = ContentEntry::factory()->create();
        $this->postJson('/admin/media', $this->upload($entry, ['publication_status' => 'published', 'is_public' => 1]))->assertCreated()->assertJsonPath('selectable', true);
        $media = Media::firstOrFail();
        $this->assertSame($entry->slug, $media->project);
        Storage::disk('local')->assertExists($media->original_path);
        Storage::disk('local')->assertExists($media->web_path);
        $this->assertSame(0, $entry->revisions()->count());
        $this->assertNull($entry->fresh()->published_revision_id);
    }

    public function test_upload_requires_both_media_and_project_access(): void
    {
        $this->login('media-manager');
        $entry = ContentEntry::factory()->create();
        $this->postJson('/admin/media', $this->upload($entry))->assertForbidden();
        $this->login('project-editor');
        $this->postJson('/admin/media', $this->upload($entry))->assertForbidden();
        $this->assertDatabaseCount('media', 0);
    }

    public function test_private_upload_is_not_selectable_and_wrong_record_type_is_rejected(): void
    {
        $this->login('super-admin');
        $entry = ContentEntry::factory()->create();
        $this->postJson('/admin/media', $this->upload($entry))->assertCreated()->assertJsonPath('selectable', false);
        $other = ContentEntry::factory()->create(['type' => 'page']);
        $this->postJson('/admin/media', $this->upload($other))->assertUnprocessable()->assertJsonValidationErrors('project_entry_id');
    }
}
