<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditRecorder;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdministrationSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, HomepageSeeder::class]);
    }

    private function admin(bool $enrolled = true): User
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => $enrolled ? now() : null]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

        return $user;
    }

    public function test_new_admin_can_complete_setup_and_return_to_requested_cms_page(): void
    {
        $user = $this->admin(false);
        $this->get('/admin/homepage')->assertRedirect('/admin/security');
        $this->get('/admin/security')->assertOk()->assertSee('Your sign-in worked.')->assertSee('Show setup QR code')->assertDontSee('name="code"', false);
        $this->from('/admin/security')->post('/user/two-factor-authentication')->assertRedirect('/admin/security');
        $this->get('/admin/security')->assertSee('Six-digit authenticator code');
        $code = (new Google2FA)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret));
        $this->post('/user/confirmed-two-factor-authentication', ['code' => $code])->assertSessionHasNoErrors();
        $this->get('/admin/security')->assertSee('Save your recovery codes')->assertSee('Open my workspace');
        $this->post('/admin/security/continue', [])->assertSessionHasErrors('recovery_saved');
        $this->post('/admin/security/continue', ['recovery_saved' => 1])->assertRedirect('/admin/homepage');
        $this->get('/admin/homepage')->assertOk();
    }

    public function test_security_overview_does_not_reveal_secrets_without_recent_confirmation(): void
    {
        $user = $this->admin();
        $user->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_recovery_codes' => encrypt(json_encode(['private-recovery-example']))])->save();
        $this->withSession(['auth.password_confirmed_at' => 0])->get('/admin/security')->assertOk()->assertSee('Confirm current password')->assertDontSee('private-recovery-example')->assertDontSee('<svg', false);
        $this->post('/admin/security/continue', ['recovery_saved' => 1])->assertRedirect('/user/confirm-password');
    }

    public function test_fresh_login_counts_as_recent_password_confirmation(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->get('/admin/security')->assertOk()->assertSee('Show setup QR code')->assertDontSee('Confirm current password');
    }

    public function test_roles_can_be_created_edited_and_audited_without_seed_overwriting_edits(): void
    {
        $this->admin();
        $this->get('/admin/roles/create')->assertOk();
        $this->post('/admin/roles', ['name' => 'review-assistant', 'label' => 'Review Assistant', 'permissions' => ['admin.view', 'pages.view']])->assertSessionHasNoErrors();
        $role = Role::where('name', 'review-assistant')->firstOrFail();
        $this->get(route('admin.roles.edit', $role))->assertOk();
        $this->put(route('admin.roles.update', $role), ['label' => 'Editorial Reviewer', 'revision' => 1, 'permissions' => ['admin.view', 'pages.view', 'pages.edit']])->assertSessionHasNoErrors();
        $log = AuditLog::where('action', 'role.updated')->firstOrFail();
        $this->assertSame('Review Assistant', $log->changes['before_label']);
        $this->assertContains('pages.edit', $log->changes['after_permissions']);
        $this->put(route('admin.roles.update', $role), ['label' => 'Stale edit', 'revision' => 1, 'permissions' => []])->assertSessionHasErrors('revision');
        $default = Role::where('name', 'content-manager')->firstOrFail();
        $this->put(route('admin.roles.update', $default), ['label' => 'Restricted content manager', 'revision' => 1, 'permissions' => ['admin.view', 'pages.view']])->assertSessionHasNoErrors();
        $this->seed(RolePermissionSeeder::class);
        $this->assertFalse($default->permissions()->where('name', 'pages.edit')->exists());
        $this->get('/admin/audit')->assertOk()->assertSee('Review Assistant')->assertSee('Editorial Reviewer');
    }

    public function test_protected_roles_and_assigned_roles_cannot_be_deleted_or_privileged_permissions_delegated(): void
    {
        $user = $this->admin();
        $super = Role::where('name', 'super-admin')->firstOrFail();
        $this->put(route('admin.roles.update', $super), ['label' => 'Broken', 'revision' => 1, 'permissions' => []])->assertForbidden();
        $this->delete(route('admin.roles.destroy', $super), ['revision' => 1])->assertForbidden();
        $this->post('/admin/roles', ['name' => 'backdoor', 'label' => 'Backdoor', 'permissions' => ['roles.manage']])->assertSessionHasErrors('permissions.0');
        $this->delete(route('admin.roles.destroy', Role::where('name', 'viewer')->firstOrFail()), ['revision' => 1])->assertSessionHasErrors('role');
        $this->post('/admin/roles', ['name' => 'temporary', 'label' => 'Temporary', 'permissions' => ['admin.view']])->assertSessionHasNoErrors();
        $role = Role::where('name', 'temporary')->firstOrFail();
        $user->roles()->attach($role);
        $this->delete(route('admin.roles.destroy', $role), ['revision' => 1])->assertSessionHasErrors('role');
        $user->roles()->detach($role);
        $this->delete(route('admin.roles.destroy', $role), ['revision' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_non_admin_cannot_change_roles_and_granular_create_gate_is_enforced(): void
    {
        $role = Role::where('name', 'content-manager')->firstOrFail();
        $role->permissions()->detach(Permission::where('name', 'pages.create')->firstOrFail());
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
        $this->get('/admin/homepage')->assertOk();
        $this->post('/admin/content', [])->assertForbidden();
        $this->post('/admin/roles', [])->assertForbidden();
        $this->get('/admin')->assertOk()->assertSee('Edit homepage')->assertDontSee('Manage users');
    }

    public function test_granular_upload_permission_is_enforced_and_audit_excludes_unknown_secrets(): void
    {
        $role = Role::where('name', 'media-manager')->firstOrFail();
        $role->permissions()->detach(Permission::where('name', 'media.upload')->firstOrFail());
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $this->actingAs($user);
        $this->get('/admin/media')->assertOk()->assertDontSee('Upload media');
        $this->post('/admin/media', [])->assertForbidden();
        app(AuditRecorder::class)->record('test', $user, ['password' => 'do-not-log', 'two_factor_secret' => 'never-log', 'before_label' => 'Before', 'after_label' => 'After']);
        $this->assertEquals(['before_label' => 'Before', 'after_label' => 'After'], AuditLog::latest('id')->firstOrFail()->changes);
    }

    public function test_role_writes_require_recent_confirmation_and_changes_apply_to_existing_sessions(): void
    {
        $this->admin();
        $this->withSession(['auth.password_confirmed_at' => 0])->post('/admin/roles', ['name' => 'blocked', 'label' => 'Blocked', 'permissions' => []])->assertRedirect('/user/confirm-password');
        $this->assertDatabaseMissing('roles', ['name' => 'blocked']);
        $role = Role::where('name', 'content-manager')->firstOrFail();
        $editor = User::factory()->create();
        $editor->roles()->attach($role);
        $this->assertTrue($editor->can('pages.edit'));
        $this->withSession(['auth.password_confirmed_at' => time()])->put(route('admin.roles.update', $role), ['revision' => 1, 'label' => $role->label, 'permissions' => ['admin.view', 'pages.view']])->assertSessionHasNoErrors();
        $this->actingAs($editor)->get('/admin/homepage')->assertForbidden();
        $this->get('/admin/content')->assertOk();
    }
}
