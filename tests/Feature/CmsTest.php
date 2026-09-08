<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\Homepage;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
    }

    private function loginAs(string $role = 'admin'): User
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->firstOrFail());
        $this->actingAs($user);

        return $user;
    }

    private function data(array $overrides = []): array
    {
        return [...['version' => 0, 'type' => 'page', 'slug' => 'company-profile', 'title' => 'Company profile', 'body' => 'Approved company information.', 'description' => 'Company profile description', 'order' => 10, 'placement' => 'both'], ...$overrides];
    }

    private function createEntry(array $overrides = []): ContentEntry
    {
        $this->post(route('admin.content.store'), $this->data($overrides))->assertSessionHasNoErrors();

        return ContentEntry::where('type', $overrides['type'] ?? 'page')->latest('id')->firstOrFail();
    }

    private function publish(ContentEntry $entry, int $version = 1): void
    {
        $this->post(route('admin.content.transition', $entry), ['version' => $version, 'action' => 'publish'])->assertSessionHasNoErrors();
    }

    public function test_draft_preview_publication_and_unpublish(): void
    {
        $this->loginAs();
        $entry = $this->createEntry();
        $this->get('/pages/company-profile')->assertNotFound();
        $this->get(route('admin.content.preview', $entry))->assertOk()->assertSee('Approved company information.')->assertSee('Private draft preview');
        $this->publish($entry);
        $this->get('/pages/company-profile')->assertOk()->assertSee('Approved company information.');
        $this->put(route('admin.content.update', $entry), $this->data(['version' => 1, 'body' => 'Unapproved replacement']))->assertSessionHasNoErrors();
        $this->get('/pages/company-profile')->assertDontSee('Unapproved replacement')->assertSee('Approved company information.');
        $this->post(route('admin.content.transition', $entry), ['version' => 2, 'action' => 'unpublish'])->assertSessionHasNoErrors();
        $this->get('/pages/company-profile')->assertNotFound();
    }

    public function test_editor_cannot_publish_and_stale_versions_are_rejected(): void
    {
        $this->loginAs('content-manager');
        $entry = $this->createEntry();
        $this->post(route('admin.content.transition', $entry), ['version' => 1, 'action' => 'publish'])->assertForbidden();
        $this->post(route('admin.content.transition', $entry), ['version' => 1, 'action' => 'review', 'note' => 'Ready for approval'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('approval_events', ['action' => 'review', 'note' => 'Ready for approval']);
        $this->put(route('admin.content.update', $entry), $this->data(['version' => 0]))->assertSessionHasErrors('version');
        $this->assertDatabaseCount('content_revisions', 2);
        $this->loginAs('viewer');
        $this->get(route('admin.content.preview', $entry))->assertForbidden();
        $this->get('/admin/media')->assertForbidden();
    }

    public function test_scheduled_revision_publishes_once_and_new_draft_cancels_schedule(): void
    {
        $this->loginAs();
        $entry = $this->createEntry();
        $this->post(route('admin.content.transition', $entry), ['version' => 1, 'action' => 'schedule', 'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s')])->assertSessionHasNoErrors();
        $this->artisan('content:publish-due')->expectsOutput('0 entries published.')->assertSuccessful();
        $this->travel(61)->minutes();
        $this->artisan('content:publish-due')->expectsOutput('1 entries published.')->assertSuccessful();
        $this->artisan('content:publish-due')->expectsOutput('0 entries published.')->assertSuccessful();
        $this->get('/pages/company-profile')->assertOk();
        $this->post(route('admin.content.transition', $entry), ['version' => 1, 'action' => 'schedule', 'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s')]);
        $this->put(route('admin.content.update', $entry), $this->data(['version' => 1, 'body' => 'New draft']))->assertSessionHasNoErrors();
        $this->travel(61)->minutes();
        $this->artisan('content:publish-due')->expectsOutput('0 entries published.')->assertSuccessful();
    }

    public function test_shared_blocks_statistics_and_menus_use_only_published_values(): void
    {
        $this->loginAs();
        $block = $this->createEntry(['type' => 'block', 'slug' => 'mission', 'title' => 'Our mission', 'body' => 'Shared approved mission']);
        $this->publish($block);
        $stat = $this->createEntry(['type' => 'statistic', 'slug' => 'test-stat', 'title' => 'Test statistic', 'value' => '18']);
        $this->publish($stat);
        $page = $this->createEntry(['block_ids' => [$block->id], 'statistic_ids' => [$stat->id]]);
        $this->publish($page);
        $this->get('/pages/company-profile')->assertSee('Shared approved mission')->assertSee('Test statistic');
        $this->put(route('admin.content.update', $block), $this->data(['version' => 1, 'type' => 'block', 'slug' => 'mission', 'title' => 'Our mission', 'body' => 'Revised shared mission']))->assertSessionHasNoErrors();
        $this->publish($block, 2);
        $this->get('/pages/company-profile')->assertSee('Revised shared mission')->assertDontSee('Shared approved mission');
        $menu = $this->createEntry(['type' => 'menu', 'slug' => 'company-menu', 'title' => 'Corporate profile', 'url' => '/pages/company-profile']);
        $this->get('/')->assertDontSee('Corporate profile');
        $this->publish($menu);
        $this->get('/')->assertSee('Corporate profile');
        $this->post(route('admin.content.store'), $this->data(['type' => 'menu', 'slug' => 'bad', 'url' => 'javascript:alert(1)']))->assertSessionHasErrors('url');
    }

    public function test_restoring_history_creates_a_private_draft_and_rejects_other_entries(): void
    {
        $this->loginAs();
        $entry = $this->createEntry();
        $original = $entry->revisions()->firstOrFail();
        $this->publish($entry);
        $this->put(route('admin.content.update', $entry), $this->data(['version' => 1, 'body' => 'Second version']))->assertSessionHasNoErrors();
        $this->publish($entry, 2);
        $this->post(route('admin.content.restore', $entry), ['version' => 2, 'revision_id' => $original->id])->assertSessionHasNoErrors();
        $this->assertSame(3, $entry->revisions()->count());
        $this->get('/pages/company-profile')->assertSee('Second version');
        $this->get(route('admin.content.preview', $entry))->assertSee('Approved company information.');
        $other = $this->createEntry(['slug' => 'another-page']);
        $this->post(route('admin.content.restore', $other), ['version' => 1, 'revision_id' => $original->id])->assertNotFound();
    }

    private function mediaData(array $overrides = []): array
    {
        return [...['title' => 'Construction image', 'alt' => 'Test building illustration', 'category' => 'Company', 'is_public' => 0, 'sort_order' => 10, 'watermark' => ['enabled' => 1, 'position' => 'bottom-right', 'opacity' => 70, 'size' => 3, 'padding' => 20]], ...$overrides];
    }

    public function test_media_derivatives_preserve_original_and_private_access(): void
    {
        Storage::fake('local');
        $this->loginAs('media-manager');
        $file = UploadedFile::fake()->image('building.png', 900, 600);
        $original = file_get_contents($file->getPathname());
        $this->post('/admin/media', $this->mediaData(['file' => $file]))->assertSessionHasNoErrors();
        $media = Media::firstOrFail();
        $this->assertSame($original, Storage::disk('local')->get($media->original_path));
        $branded = Storage::disk('local')->get($media->web_path);
        $this->get(route('media.show', $media))->assertOk();
        $this->get(route('admin.media.edit', $media))->assertOk();
        $this->get('/admin/media')->assertOk();
        $this->put(route('admin.media.update', $media), $this->mediaData(['watermark' => ['enabled' => 0, 'position' => 'center', 'opacity' => 70, 'size' => 3, 'padding' => 20]]))->assertSessionHasNoErrors();
        $media->refresh();
        $this->assertSame($original, Storage::disk('local')->get($media->original_path));
        $this->assertNotSame($branded, Storage::disk('local')->get($media->web_path));
        auth()->logout();
        $this->get(route('media.show', $media))->assertNotFound();
        $this->get(route('admin.media.original', $media))->assertRedirect('/login');
        $this->loginAs('media-manager');
        $this->put(route('admin.media.update', $media), $this->mediaData(['is_public' => 1]))->assertSessionHasNoErrors();
        auth()->logout();
        $this->get(route('media.show', $media))->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_upload_rejects_executable_svg_and_missing_alt(): void
    {
        Storage::fake('local');
        $this->loginAs('media-manager');
        $this->post('/admin/media', $this->mediaData(['file' => UploadedFile::fake()->createWithContent('evil.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')]))->assertSessionHasErrors('file');
        $this->post('/admin/media', $this->mediaData(['alt' => '', 'file' => UploadedFile::fake()->image('test.png')]))->assertSessionHasErrors('alt');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_homepage_media_selection_and_admin_forms_render(): void
    {
        $this->loginAs();
        $this->get('/admin/content/create')->assertOk();
        $this->get('/admin/content')->assertOk();
        $this->get('/admin/media/create')->assertOk();
        $media = Media::factory()->create(['is_public' => false]);
        $this->put('/admin/homepage', ['version' => 1, 'content' => Homepage::main()->content, 'hero_media_id' => $media->id])->assertSessionHasErrors('hero_media_id');
        $media->update(['is_public' => true]);
        $this->put('/admin/homepage', ['version' => 1, 'content' => Homepage::main()->content, 'hero_media_id' => $media->id])->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $this->publish($entry, 2);
        $this->get('/')->assertSee(route('media.show', $media));
        $this->get('/admin/homepage')->assertOk();
        $page = $this->createEntry();
        $this->get(route('admin.content.edit', $page))->assertOk();
    }
}
