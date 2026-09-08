<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrimaryAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function primaryAdmin(): User
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $user->roles()->attach(Role::where('name', 'super-admin')->firstOrFail());
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

        return $user;
    }

    public function test_primary_admin_has_no_access_form_and_cannot_be_assigned_from_dropdowns(): void
    {
        $admin = $this->primaryAdmin();
        $response = $this->get('/admin/users')->assertOk()->assertSee('Primary Super Admin');
        $response->assertDontSee('action="'.route('admin.users.update', $admin).'"', false);
        $response->assertDontSee('value="'.Role::where('name', 'super-admin')->value('id').'"', false);
        $response->assertSee('Roles & permissions', false)->assertSee('Audit log');
    }

    public function test_cannot_create_or_promote_another_super_admin(): void
    {
        $this->primaryAdmin();
        $role = Role::where('name', 'super-admin')->value('id');
        $this->post('/admin/users', ['name' => 'Duplicate', 'email' => 'duplicate@example.test', 'password' => 'Test1234', 'password_confirmation' => 'Test1234', 'role_id' => $role])->assertSessionHasErrors('role_id');
        $this->assertDatabaseMissing('users', ['email' => 'duplicate@example.test']);
        $other = User::factory()->create();
        $this->patch('/admin/users/'.$other->id, ['role_id' => $role, 'is_active' => 1])->assertSessionHasErrors('role_id');
        $this->assertFalse($other->fresh()->hasRole('super-admin'));
    }

    public function test_primary_admin_cannot_be_demoted_or_deactivated(): void
    {
        $admin = $this->primaryAdmin();
        foreach ([0, 1] as $active) {
            $this->patch('/admin/users/'.$admin->id, ['role_id' => Role::where('name', 'viewer')->value('id'), 'is_active' => $active])->assertSessionHasErrors('role_id');
        }
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertTrue($admin->fresh()->hasRole('super-admin'));
    }

    public function test_personal_settings_are_available_without_user_management_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->roles()->attach(Role::where('name', 'viewer')->firstOrFail());
        $this->actingAs($viewer)->get('/admin/account')->assertOk()->assertSee($viewer->email)->assertSee('Change password')->assertSee('action="'.route('user-password.update').'"', false)->assertSee('aria-current="page"', false)->assertDontSee('Create user');
        $this->get('/admin/users')->assertForbidden();
    }
}
