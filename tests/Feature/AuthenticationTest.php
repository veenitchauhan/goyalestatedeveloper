<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_work_and_are_audited(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'auth.login']);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'auth.logout']);
    }

    public function test_wrong_password_and_inactive_account_cannot_sign_in(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $user->forceFill(['is_active' => false])->save();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'unknown@example.test', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_two_factor_challenge_precedes_authentication(): void
    {
        $user = User::factory()->create(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_recovery_codes' => encrypt(json_encode(['recovery-example'])), 'two_factor_confirmed_at' => now()]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
        $this->post('/two-factor-challenge', ['code' => 'invalid'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->post('/two-factor-challenge', ['recovery_code' => 'recovery-example'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->assertNotContains('recovery-example', $user->fresh()->recoveryCodes());
    }

    public function test_two_factor_setup_requires_password_confirmation_and_valid_code(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/user/two-factor-authentication')->assertRedirect('/user/confirm-password');
        $this->withSession(['auth.password_confirmed_at' => time()])->post('/user/two-factor-authentication')->assertRedirect();
        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->post('/user/confirmed-two-factor-authentication', ['code' => 'invalid'])->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', 'code');
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
        $secret = decrypt($user->fresh()->two_factor_secret);
        $code = (new Google2FA)->getCurrentOtp($secret);
        $this->post('/user/confirmed-two-factor-authentication', ['code' => $code])->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.two_factor_confirmed', 'actor_id' => $user->id]);
    }
}
