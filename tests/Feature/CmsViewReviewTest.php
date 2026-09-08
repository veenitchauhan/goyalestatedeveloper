<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsViewReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_workspace_lists_and_new_record_editors_render_with_navigation(): void
    {
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
        foreach (['', '/account', '/homepage', '/content', '/content/create', '/corporate', '/corporate/create?type=service', '/projects', '/projects/create', '/locations', '/locations/create', '/knowledge', '/knowledge/create', '/jobs', '/jobs/create', '/candidates', '/media', '/media/create', '/enquiries', '/enquiry-forms', '/campaigns', '/campaigns/create', '/analytics', '/seo', '/developments', '/developments/create', '/integrations', '/settings', '/users', '/roles', '/roles/create', '/audit', '/search'] as $suffix) {
            $response = $this->get('/admin'.$suffix)->assertOk()->assertSee('Skip to content');
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $this->assertSame(1, $xpath->query('//main//h1')->length, $suffix.' should have one page heading');
            $this->assertGreaterThan(0, $xpath->query('//nav//a[@aria-current="page"]')->length, $suffix.' should identify the current workspace');
        }
    }
}
