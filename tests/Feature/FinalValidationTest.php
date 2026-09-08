<?php

namespace Tests\Feature;

use Database\Seeders\HomepageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_hubs_render_content_and_reference_existing_local_assets(): void
    {
        $this->seed(HomepageSeeder::class);
        foreach (['/', '/about', '/business', '/capabilities', '/projects', '/locations', '/careers', '/insights', '/knowledge-bank', '/faqs', '/contact', '/search', '/privacy-preferences'] as $path) {
            $response = $this->get($path)->assertOk()->assertDontSee("@yield('content')", false);
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $this->assertGreaterThan(0, $xpath->query('//main//h1')->length, $path.' must render its page content');
            foreach ($xpath->query('//link[@rel="stylesheet"]/@href | //script[@src]/@src') as $asset) {
                $assetPath = parse_url($asset->nodeValue, PHP_URL_PATH);
                $this->assertFileExists(public_path(ltrim($assetPath, '/')), $path.' references a missing asset');
            }
        }
    }

    public function test_error_page_links_to_dedicated_recovery_pages(): void
    {
        $this->seed(HomepageSeeder::class);
        $this->get('/missing-page')->assertNotFound()
            ->assertSee('href="'.route('projects.index').'"', false)
            ->assertSee('href="'.route('contact').'"', false);
    }

    public function test_local_diagnostics_are_not_exposed_in_production(): void
    {
        $this->seed(HomepageSeeder::class);
        $this->app->instance('env', 'production');
        $this->getJson('/health')->assertNotFound()->assertDontSee('framework');
    }
}
