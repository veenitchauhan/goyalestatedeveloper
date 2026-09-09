<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalSignInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([HomepageSeeder::class, RolePermissionSeeder::class]);
        config(['fortify.require_admin_two_factor' => false]);
        $this->withSession(['_token' => 'local-login-test-token']);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());

        return $user;
    }

    public function test_local_password_login_opens_cms_without_enrollment(): void
    {
        $this->app->instance('env', 'local');
        $user = $this->admin();
        $this->post('/login', ['_token' => 'local-login-test-token', 'email' => $user->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->withSession(['admin.setup_recovery_pending' => true])->get('/admin')->assertOk()->assertSee('Open projects')->assertDontSee('Complete account setup')->assertDontSee('Manage security');
        $this->get('/admin/homepage')->assertOk();
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_existing_local_setup_page_redirects_to_the_cms(): void
    {
        $this->app->instance('env', 'local');
        $this->actingAs($this->admin())->withSession(['admin.setup_destination' => '/admin/homepage', 'admin.setup_recovery_pending' => true])->get('/admin/security')->assertRedirect('/admin')->assertSessionMissing('admin.setup_recovery_pending')->assertSessionMissing('admin.setup_destination');
    }

    public function test_production_can_skip_required_setup_when_disabled(): void
    {
        $this->app->instance('env', 'production');
        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('target="_blank" rel="noopener noreferrer">View website', false);
        $this->get('/admin/security')->assertRedirect('/admin');
    }

    public function test_local_access_still_requires_authentication_and_valid_credentials(): void
    {
        $this->app->instance('env', 'local');
        $user = $this->admin();
        $this->get('/admin')->assertRedirect('/login');
        $this->post('/login', ['_token' => 'local-login-test-token', 'email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $user->forceFill(['is_active' => false])->save();
        $this->post('/login', ['_token' => 'local-login-test-token', 'email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_local_setup_can_be_reenabled(): void
    {
        $this->app->instance('env', 'local');
        config(['fortify.require_admin_two_factor' => true]);
        $this->actingAs($this->admin())->get('/admin')->assertRedirect('/admin/security');
    }
}
