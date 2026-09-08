<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentityAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_recovery_codes' => encrypt(json_encode(['test-only-recovery-code'])), 'two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->firstOrFail());

        return $user;
    }

    public function test_guest_cannot_open_administration(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_viewer_cannot_read_or_mutate_identity_data(): void
    {
        $viewer = $this->userWithRole('viewer');
        $other = $this->userWithRole('content-manager');
        $this->actingAs($viewer)->get('/admin')->assertOk();
        foreach (['/admin/users', '/admin/roles', '/admin/audit'] as $path) {
            $this->get($path)->assertForbidden();
        }
        $this->post('/admin/users', [])->assertForbidden();
        $this->patch('/admin/users/'.$other->id, ['role_id' => Role::where('name', 'super-admin')->value('id'), 'is_active' => 1])->assertForbidden();
        $this->assertFalse($other->fresh()->hasRole('super-admin'));
    }

    public function test_super_admin_can_create_user_and_audit_excludes_secrets(): void
    {
        $admin = $this->userWithRole('super-admin');
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->post('/admin/users', ['name' => 'Editor', 'email' => 'editor@example.test', 'password' => 'ExamplePassword123', 'password_confirmation' => 'ExamplePassword123', 'role_id' => Role::where('name', 'project-editor')->value('id')])->assertSessionHasNoErrors()->assertRedirect();
        $user = User::where('email', 'editor@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('project-editor'));
        $audit = AuditLog::where('action', 'user.created')->firstOrFail();
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertStringNotContainsString('ExamplePassword123', $audit->toJson());
    }

    public function test_access_change_is_audited_and_self_lockout_is_prevented(): void
    {
        $admin = $this->userWithRole('super-admin');
        $editor = $this->userWithRole('project-editor');
        $role = Role::where('name', 'viewer')->firstOrFail();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->patch('/admin/users/'.$editor->id, ['role_id' => $role->id, 'is_active' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($editor->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.access_updated', 'subject_id' => $editor->id]);
        $this->patch('/admin/users/'.$admin->id, ['role_id' => $role->id, 'is_active' => 0])->assertSessionHasErrors('role_id');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_disabled_user_loses_an_existing_session(): void
    {
        $user = $this->userWithRole('viewer');
        $this->actingAs($user);
        $user->forceFill(['is_active' => false])->save();
        $this->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_without_two_factor_is_sent_to_enrollment(): void
    {
        $user = $this->userWithRole('super-admin');
        $user->forceFill(['two_factor_confirmed_at' => null])->save();
        $this->actingAs($user)->get('/admin/users')->assertRedirect('/admin/security');
    }

    public function test_access_changes_require_recent_password_confirmation(): void
    {
        $admin = $this->userWithRole('super-admin');
        $this->actingAs($admin)->post('/admin/users', [])->assertRedirect('/user/confirm-password');
    }

    public function test_seed_is_idempotent_and_creates_no_demo_accounts(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->assertDatabaseCount('roles', 12);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_views_and_security_screen_render(): void
    {
        $admin = $this->userWithRole('super-admin');
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        foreach (['/admin', '/admin/users', '/admin/roles', '/admin/audit', '/admin/security'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_interactive_bootstrap_creates_first_admin_and_refuses_second(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Full name', 'Test Administrator')
            ->expectsQuestion('Email', 'admin@example.test')
            ->expectsQuestion('Password (12+ characters with letters and numbers)', 'LongTestPassword123')
            ->assertSuccessful();
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($admin->hasRole('super-admin'));
        $this->artisan('app:create-admin')->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_generated_bootstrap_is_denied_outside_local_environment(): void
    {
        $this->artisan('app:create-admin', ['--local-bootstrap' => true])->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }
}
