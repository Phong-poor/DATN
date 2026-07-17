<?php

namespace Tests\Feature;

use App\Models\AffiliateWallet;
use App\Models\AffiliateWithdrawal;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class AffiliateWalletWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sms.enabled' => false]);
    }

    public function test_guest_cannot_view_wallet(): void
    {
        $this->getJson('/api/affiliate/wallet')->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_wallet(): void
    {
        $user = User::factory()->create();
        AffiliateWallet::create(['user_id' => $user->id, 'balance' => 2500000, 'pending_balance' => 300000, 'total_withdrawn' => 1000000]);

        Sanctum::actingAs($user);

        $this->getJson('/api/affiliate/wallet')
            ->assertOk()
            ->assertJsonPath('data.balance', 2500000);
    }

    public function test_user_can_withdraw_demo_successfully_and_balance_is_updated(): void
    {
        config(['services.sms.enabled' => false]);
        $user = User::factory()->create();
        AffiliateWallet::create(['user_id' => $user->id, 'balance' => 2500000]);
        Sanctum::actingAs($user);

        $this->postJson('/api/affiliate/withdrawals', $this->payload(['amount' => 500000]))
            ->assertCreated()
            ->assertJsonPath('remaining_balance', 2000000);

        $this->assertDatabaseHas('affiliate_wallets', [
            'user_id' => $user->id,
            'balance' => 2000000,
            'total_withdrawn' => 500000,
        ]);
        $this->assertDatabaseHas('affiliate_withdrawals', [
            'user_id' => $user->id,
            'amount' => 500000,
            'status' => 'success',
            'sms_status' => 'sent',
        ]);
    }

    public function test_cannot_withdraw_more_than_balance(): void
    {
        $user = User::factory()->create();
        AffiliateWallet::create(['user_id' => $user->id, 'balance' => 100000]);
        Sanctum::actingAs($user);

        $this->postJson('/api/affiliate/withdrawals', $this->payload(['amount' => 200000]))
            ->assertStatus(422);
    }

    public function test_cannot_withdraw_below_minimum(): void
    {
        $user = User::factory()->create();
        AffiliateWallet::create(['user_id' => $user->id, 'balance' => 1000000]);
        Sanctum::actingAs($user);

        $this->postJson('/api/affiliate/withdrawals', $this->payload(['amount' => 99999]))
            ->assertStatus(422);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $user = User::factory()->create();
        AffiliateWallet::create(['user_id' => $user->id, 'balance' => 1000000]);
        Sanctum::actingAs($user);

        $this->postJson('/api/affiliate/withdrawals', $this->payload(['phone_account' => '12345']))
            ->assertStatus(422);
    }

    public function test_same_idempotency_key_does_not_create_two_withdrawals(): void
    {
        $user = User::factory()->create();
        AffiliateWallet::create(['user_id' => $user->id, 'balance' => 1000000]);
        Sanctum::actingAs($user);

        $payload = $this->payload(['idempotency_key' => 'same-key']);
        $this->postJson('/api/affiliate/withdrawals', $payload)->assertCreated();
        $this->postJson('/api/affiliate/withdrawals', $payload)->assertOk();

        $this->assertSame(1, AffiliateWithdrawal::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('affiliate_wallets', ['user_id' => $user->id, 'balance' => 500000]);
    }

    public function test_user_cannot_see_other_users_withdrawals(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        AffiliateWithdrawal::create([
            'user_id' => $userB->id,
            'amount' => 500000,
            'bank_name' => 'Vietcombank',
            'phone_account' => '0987654321',
            'account_name' => 'USER B',
            'transaction_code' => 'WD-USER-B',
            'status' => 'success',
            'sms_status' => 'sent',
            'balance_before' => 1000000,
            'balance_after' => 500000,
            'completed_at' => now(),
        ]);

        Sanctum::actingAs($userA);

        $this->getJson('/api/affiliate/withdrawals')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_sms_failure_does_not_rollback_withdrawal(): void
    {
        $user = User::factory()->create();
        AffiliateWallet::create(['user_id' => $user->id, 'balance' => 1000000]);
        Sanctum::actingAs($user);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('sendWithdrawalSuccess')->once()->andReturn([
            'success' => false,
            'message_id' => null,
            'error' => 'Provider down',
        ]);
        $this->app->instance(SmsService::class, $sms);

        $this->postJson('/api/affiliate/withdrawals', $this->payload())->assertCreated();

        $this->assertDatabaseHas('affiliate_withdrawals', [
            'user_id' => $user->id,
            'status' => 'success',
            'sms_status' => 'failed',
        ]);
        $this->assertDatabaseHas('affiliate_wallets', ['user_id' => $user->id, 'balance' => 500000]);
    }

    public function test_sequential_requests_do_not_make_balance_negative(): void
    {
        $user = User::factory()->create();
        AffiliateWallet::create(['user_id' => $user->id, 'balance' => 500000]);
        Sanctum::actingAs($user);

        $this->postJson('/api/affiliate/withdrawals', $this->payload(['idempotency_key' => 'one', 'amount' => 500000]))->assertCreated();
        $this->postJson('/api/affiliate/withdrawals', $this->payload(['idempotency_key' => 'two', 'amount' => 500000]))->assertStatus(422);

        $this->assertDatabaseHas('affiliate_wallets', ['user_id' => $user->id, 'balance' => 0]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'phone_account' => '0987654321',
            'account_name' => 'LE NGOC TAI',
            'bank_name' => 'Vietcombank',
            'amount' => 500000,
            'idempotency_key' => 'idem-' . uniqid(),
        ], $override);
    }
}
