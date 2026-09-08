<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckpointTest extends TestCase
{
    public function test_checkpoint_is_rendered_by_laravel_with_noindex(): void
    {
        $this->get('/')->assertOk()->assertViewIs('checkpoint')->assertDontSee('$health')->assertDontSee('Local connection is working')->assertSee('GOYAL ESTATE &amp; DEVELOPERS PVT. LTD.', false)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_health_reports_framework_and_has_browser_view(): void
    {
        $this->getJson('/health')->assertOk()->assertJsonPath('framework', 'Laravel');
        $this->get('/health', ['Accept' => 'text/html'])->assertOk()->assertSee('Local connection is working.');
    }

    public function test_unknown_route_returns_branded_404(): void
    {
        $this->get('/missing')->assertNotFound()->assertSee('This page isn’t here.');
    }

    public function test_login_form_includes_csrf_token(): void
    {
        $this->get('/login')->assertOk()->assertSee('name="_token"', false);
    }
}
