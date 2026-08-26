<?php

namespace Tests\Feature;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawRequest;
use App\Models\DatHang;
use App\Models\User;
use App\Services\AffiliateBalanceService;
use App\Services\AffiliateCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AffiliateBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_is_only_earned_after_order_is_completed_and_paid(): void
    {
        $publisher = User::factory()->create();
        $buyer = User::factory()->create();
        AffiliateProfile::create([
            'id_khachhang' => $publisher->id, 'ma_affiliate' => 'TESTCODE',
            'ty_le_hoa_hong' => 5, 'trangthai' => 'active',
        ]);
        $order = DatHang::create([
            'id_khachhang' => $buyer->id, 'tongtien' => 1000000,
            'trangthai' => 'done', 'trang_thai_thanh_toan' => 'pending',
        ]);
        $commission = AffiliateCommission::create([
            'id_affiliate_khachhang' => $publisher->id,
            'id_khachhang_duoc_gioithieu' => $buyer->id,
            'id_donhang' => $order->id_dathang,
            'so_tien' => 50000,
            'trangthai' => 'pending',
        ]);

        app(AffiliateCommissionService::class)->syncOrderStatus($order);
        $this->assertSame('pending', $commission->fresh()->trangthai);

        $order->trang_thai_thanh_toan = 'paid';
        $order->save();
        app(AffiliateCommissionService::class)->syncOrderStatus($order->fresh());
        $this->assertSame('approved', $commission->fresh()->trangthai);
    }

    public function test_available_balance_subtracts_reserved_and_paid_withdrawals(): void
    {
        $publisher = User::factory()->create();
        AffiliateCommission::create([
            'id_affiliate_khachhang' => $publisher->id,
            'so_tien' => 500000,
            'trangthai' => 'approved',
        ]);
        foreach ([['pending', 100000], ['approved', 50000], ['paid', 200000], ['rejected', 100000]] as [$status, $amount]) {
            AffiliateWithdrawRequest::create([
                'id_affiliate_khachhang' => $publisher->id,
                'so_tien' => $amount,
                'ten_ngan_hang' => 'VCB',
                'ten_chu_tai_khoan' => 'NGUYEN VAN A',
                'so_tai_khoan' => '123456789',
                'trangthai' => $status,
            ]);
        }

        $summary = app(AffiliateBalanceService::class)->summary($publisher->id);
        $this->assertSame(150000.0, $summary['reserved_withdrawal']);
        $this->assertSame(200000.0, $summary['paid_commission']);
        $this->assertSame(150000.0, $summary['available_balance']);
    }

    public function test_user_can_cancel_pending_withdrawal_within_fifteen_minutes(): void
    {
        $publisher = User::factory()->create();
        Sanctum::actingAs($publisher);
        AffiliateProfile::create([
            'id_khachhang' => $publisher->id,
            'ma_affiliate' => 'CANCEL15',
            'ty_le_hoa_hong' => 5,
            'trangthai' => 'active',
        ]);
        AffiliateCommission::create([
            'id_affiliate_khachhang' => $publisher->id,
            'so_tien' => 500000,
            'trangthai' => 'approved',
        ]);
        $withdraw = AffiliateWithdrawRequest::create([
            'id_affiliate_khachhang' => $publisher->id,
            'so_tien' => 200000,
            'ten_ngan_hang' => 'VCB',
            'ten_chu_tai_khoan' => 'LE NGOC TAI',
            'so_tai_khoan' => '0987654321',
            'trangthai' => 'pending',
        ]);

        $this->assertSame(300000.0, app(AffiliateBalanceService::class)->summary($publisher->id)['available_balance']);

        $this->patchJson("/api/affiliate/withdraws/{$withdraw->id}/cancel")
            ->assertOk()
            ->assertJsonPath('withdraw.status', 'cancelled');

        $this->assertSame('cancelled', $withdraw->fresh()->trangthai);
        $this->assertSame(500000.0, app(AffiliateBalanceService::class)->summary($publisher->id)['available_balance']);
    }
}
