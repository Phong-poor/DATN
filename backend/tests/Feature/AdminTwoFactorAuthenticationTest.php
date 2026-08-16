<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_two_factor_does_not_receive_token_before_challenge(): void
    {
        $admin = $this->adminWithTwoFactor();

        $response = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Admin@123',
        ]);

        $response->assertOk()
            ->assertJsonPath('two_factor_required', true)
            ->assertJsonMissingPath('token')
            ->assertJsonStructure(['challenge_token', 'expires_in']);
    }

    public function test_valid_recovery_code_completes_challenge_and_is_consumed(): void
    {
        $admin = $this->adminWithTwoFactor();
        $challenge = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Admin@123',
        ])->json('challenge_token');

        $response = $this->postJson('/api/auth/two-factor/challenge', [
            'challenge_token' => $challenge,
            'code' => 'safe-recovery-code',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
        $this->assertNotContains('safe-recovery-code', $admin->fresh()->recoveryCodes());
    }

    public function test_invalid_challenge_code_does_not_issue_token(): void
    {
        $admin = $this->adminWithTwoFactor();
        $challenge = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Admin@123',
        ])->json('challenge_token');

        $this->postJson('/api/auth/two-factor/challenge', [
            'challenge_token' => $challenge,
            'code' => '000000',
        ])->assertUnprocessable()->assertJsonMissingPath('token');
    }

    public function test_customer_login_is_not_forced_through_admin_two_factor(): void
    {
        $customer = User::query()->create([
            'ten' => 'Khach hang',
            'email' => 'customer-2fa@example.com',
            'matkhau' => Hash::make('User@123'),
            'vaitro' => 'user',
            'trangthai' => 'active',
        ]);

        $this->postJson('/api/login', [
            'email' => $customer->email,
            'password' => 'User@123',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_two_factor_settings_are_only_available_to_admin_area_accounts(): void
    {
        $customer = User::query()->create([
            'ten' => 'Khach hang',
            'email' => 'customer-settings@example.com',
            'matkhau' => Hash::make('User@123'),
            'vaitro' => 'user',
            'trangthai' => 'active',
        ]);
        Sanctum::actingAs($customer);

        $this->getJson('/api/admin/account/two-factor')->assertForbidden();
    }

    private function adminWithTwoFactor(): Admin
    {
        $provider = app(TwoFactorAuthenticationProvider::class);

        $admin = Admin::query()->create([
            'ten' => 'Admin 2FA',
            'email' => 'admin-2fa@example.com',
            'matkhau' => Hash::make('Admin@123'),
            'vaitro' => 'admin',
            'trangthai' => 'active',
        ]);

        $admin->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt($provider->generateSecretKey()),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['safe-recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $admin;
    }
}
