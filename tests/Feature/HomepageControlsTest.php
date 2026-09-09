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
use Tests\TestCase;

class HomepageControlsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
    }

    private function signInEditor(): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user);
    }

    private function publishedItem(string $type, array $payload): ContentEntry
    {
        $entry = ContentEntry::factory()->create(['type' => $type]);
        $revision = $entry->revisions()->create(['version' => 1, 'payload' => $payload + ['order' => 0]]);
        $entry->update(['published_revision_id' => $revision->id, 'status' => 'published']);

        return $entry;
    }

    public function test_homepage_controls_stay_private_until_approved_and_published(): void
    {
        $this->signInEditor();
        $video = Media::factory()->create(['mime' => 'video/mp4', 'is_public' => true, 'publication_status' => 'published']);
        $cta = $this->publishedItem('cta', ['title' => 'Discuss infrastructure', 'url' => '/#contact']);
        $selected = $this->publishedItem('statistic', ['title' => 'Selected statistic', 'value' => '8']);
        $this->publishedItem('statistic', ['title' => 'Unselected statistic', 'value' => '3']);
        $this->get('/admin/homepage')->assertSee('Hero film (optional)')->assertSee('Selected statistic');
        $this->put('/admin/homepage', ['version' => 1, 'content' => Homepage::main()->content, 'hero_video_id' => $video->id, 'primary_cta_id' => $cta->id, 'statistics_mode' => 'selected', 'statistic_ids' => [$selected->id]])->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $this->get('/')->assertDontSee('Discuss infrastructure')->assertDontSee('class="hero-film"', false)->assertDontSee('Unselected statistic');
        $this->get(route('admin.content.preview', $entry))->assertSee('Discuss infrastructure')->assertSee('class="hero-film"', false)->assertDontSee('Selected statistic')->assertDontSee('Unselected statistic');
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post(route('admin.content.transition', $entry), ['version' => 2, 'action' => $action])->assertSessionHasNoErrors();
        }
        $this->get('/')->assertSee('Discuss infrastructure')->assertSee('preload="none"', false)->assertDontSee('autoplay', false)->assertDontSee('Selected statistic')->assertDontSee('Unselected statistic');
        $video->update(['is_public' => false]);
        $cta->update(['published_revision_id' => null]);
        $selected->update(['published_revision_id' => null]);
        $this->get('/')->assertDontSee('class="hero-film"', false)->assertDontSee('Discuss infrastructure')->assertDontSee('Selected statistic')->assertSee('Start a project');
    }

    public function test_repeated_draft_saves_preserve_unpublished_fields_and_reject_stale_edits(): void
    {
        $this->signInEditor();
        $content = Homepage::main()->content;
        $content['hero']['baseline'] = 'A private baseline';
        $this->put('/admin/homepage', ['version' => 1, 'content' => $content])->assertSessionHasNoErrors();
        $this->put('/admin/homepage', ['version' => 2, 'content' => Homepage::main()->content])->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $this->get(route('admin.content.preview', $entry))->assertSee('A private baseline');
        $this->get('/')->assertDontSee('A private baseline');
        $this->put('/admin/homepage', ['version' => 2, 'content' => $content])->assertSessionHasErrors('version');
        $this->assertSame(3, $entry->revisions()->count());
    }

    public function test_invalid_and_unpublished_references_cannot_be_saved(): void
    {
        $this->signInEditor();
        $privateVideo = Media::factory()->create(['mime' => 'video/mp4', 'is_public' => false]);
        $image = Media::factory()->create(['is_public' => true, 'publication_status' => 'published']);
        $draft = ContentEntry::factory()->create(['type' => 'cta']);
        $wrongType = $this->publishedItem('page', ['title' => 'Not a statistic']);
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $this->put('/admin/homepage', ['version' => 1, 'content' => Homepage::main()->content, 'hero_media_id' => $privateVideo->id, 'hero_video_id' => $image->id, 'primary_cta_id' => $draft->id, 'statistics_mode' => 'selected', 'statistic_ids' => [$wrongType->id]])->assertSessionHasErrors(['hero_media_id', 'hero_video_id', 'primary_cta_id', 'statistic_ids.0']);
        $this->put('/admin/homepage', ['version' => 1, 'content' => Homepage::main()->content, 'hero_video_id' => $privateVideo->id])->assertSessionHasErrors('hero_video_id');
        $this->assertSame(1, $entry->revisions()->count());
    }

    public function test_statistics_can_be_hidden_and_homepage_text_is_escaped(): void
    {
        $this->publishedItem('statistic', ['title' => 'Hidden statistic', 'value' => '4']);
        $page = Homepage::main();
        $content = $page->content;
        $content['statistics'] = ['mode' => 'hidden', 'ids' => []];
        $content['hero']['baseline'] = '<script>alert(1)</script>';
        $page->update(['content' => $content]);
        $this->get('/')->assertDontSee('Hidden statistic')->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_section_assets_require_publication_and_can_be_removed(): void
    {
        $this->signInEditor();
        $image = Media::factory()->create(['is_public' => true, 'publication_status' => 'published', 'alt' => 'Approved section illustration']);
        $video = Media::factory()->create(['mime' => 'video/mp4', 'is_public' => true, 'publication_status' => 'published', 'title' => 'Approved section film']);
        $cta = $this->publishedItem('cta', ['title' => 'Read about our approach', 'url' => '/#about']);
        $assets = [0 => ['media_id' => $image->id, 'video_id' => $video->id, 'cta_id' => $cta->id]];
        $this->put('/admin/homepage', ['version' => 1, 'content' => Homepage::main()->content, 'section_assets' => $assets])->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $this->get('/')->assertDontSee('Approved section illustration');
        $this->get(route('admin.content.preview', $entry))->assertSee('Approved section illustration')->assertSee('Approved section film')->assertSee('Read about our approach');
        foreach (['review', 'approve', 'publish'] as $action) {
            $this->post(route('admin.content.transition', $entry), ['version' => 2, 'action' => $action])->assertSessionHasNoErrors();
        }
        $this->get('/')->assertSee('Approved section illustration')->assertSee('Approved section film')->assertSee('Read about our approach');
        $image->update(['archived_at' => now()]);
        $video->update(['publication_status' => 'draft']);
        $cta->update(['published_revision_id' => null]);
        $this->get('/')->assertDontSee('Approved section illustration')->assertDontSee('Approved section film')->assertDontSee('Read about our approach');
        $this->put('/admin/homepage', ['version' => 2, 'content' => Homepage::main()->content, 'section_assets' => $assets])->assertSessionHasErrors(['section_assets.0.media_id', 'section_assets.0.video_id', 'section_assets.0.cta_id']);
        $this->assertSame(2, $entry->revisions()->count());
        $this->put('/admin/homepage', ['version' => 2, 'content' => Homepage::main()->content, 'section_assets' => [0 => ['media_id' => null, 'video_id' => null, 'cta_id' => null]]])->assertSessionHasNoErrors();
        $this->assertNull($entry->revisions()->latest('version')->firstOrFail()->payload['sections'][0]['media_id']);
    }

    public function test_unknown_section_assets_are_rejected_without_changing_content(): void
    {
        $this->signInEditor();
        $this->put('/admin/homepage', ['version' => 1, 'content' => Homepage::main()->content, 'section_assets' => [999 => ['media_id' => null]]])->assertSessionHasErrors('section_assets');
        $this->put('/admin/homepage', ['version' => 1, 'content' => Homepage::main()->content, 'section_assets' => [0 => ['enabled' => false]]])->assertSessionHasErrors('section_assets.0');
        $this->assertSame(1, ContentEntry::where('type', 'homepage')->firstOrFail()->revisions()->count());
    }

    public function test_missing_pages_offer_branded_recovery_actions(): void
    {
        $this->get('/missing-page')->assertNotFound()->assertSee(config('app.name'))->assertSee('Explore projects')->assertSee('Contact us')->assertSee('noindex,follow');
        $page = Homepage::main();
        $content = $page->content;
        foreach ($content['sections'] as &$section) {
            if (in_array($section['id'], ['projects', 'contact'])) {
                $section['enabled'] = false;
            }
        }
        unset($section);
        $page->update(['content' => $content]);
        $this->get('/missing-page')->assertNotFound()->assertSee('Back home')->assertDontSee('Explore projects')->assertDontSee('Contact us');
    }

    public function test_about_artwork_uses_approved_media_and_can_be_hidden_in_draft(): void
    {
        $this->signInEditor();
        $image = Media::factory()->create(['is_public' => true, 'publication_status' => 'published', 'alt' => 'Custom construction artwork']);
        $payload = Homepage::main()->content;
        $this->put('/admin/homepage', ['version' => 1, 'content' => $payload, 'section_assets' => [0 => ['media_id' => $image->id]]])->assertSessionHasNoErrors();
        $entry = ContentEntry::where('type', 'homepage')->firstOrFail();
        $this->get(route('admin.content.preview', $entry))->assertSee('Custom construction artwork');
        $this->get('/')->assertDontSee('Custom construction artwork');
        $payload['sections'][0]['artwork_enabled'] = false;
        $this->put('/admin/homepage', ['version' => 2, 'content' => $payload])->assertSessionHasNoErrors();
        $this->get(route('admin.content.preview', $entry))->assertDontSee('Custom construction artwork');
        $image->update(['is_public' => false]);
        $this->put('/admin/homepage', ['version' => 3, 'content' => $payload, 'section_assets' => [0 => ['media_id' => $image->id]]])->assertSessionHasErrors('section_assets.0.media_id');
    }
}
