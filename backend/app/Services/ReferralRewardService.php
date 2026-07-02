<?php

namespace App\Services;

use App\Models\AffiliateProfile;
use App\Models\AffiliateReferral;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserVoucher;

class ReferralRewardService
{
    public function rewardNewUser(User $user, ?string $referralCode): ?UserVoucher
    {
        $referralCode = strtoupper(trim((string) $referralCode));

        if ($referralCode === '') {
            return null;
        }

        $profile = AffiliateProfile::where('affiliate_code', $referralCode)
            ->where('status', 'active')
            ->first();

        if (! $profile || (int) $profile->user_id === (int) $user->id) {
            return null;
        }

        AffiliateReferral::firstOrCreate(
            ['referred_user_id' => $user->id],
            [
                'affiliate_user_id' => $profile->user_id,
                'ref_code' => $referralCode,
                'registered_at' => now(),
            ]
        );

        $promotion = $this->welcomePromotion();

        return UserVoucher::firstOrCreate(
            [
                'id_user' => $user->id,
                'id_voucher' => $promotion->id,
            ],
            [
                'trang_thai' => 0,
                'ngay_nhan' => now(),
            ]
        );
    }

    public function activeProfile(?string $referralCode): ?AffiliateProfile
    {
        $referralCode = strtoupper(trim((string) $referralCode));

        if ($referralCode === '') {
            return null;
        }

        return AffiliateProfile::where('affiliate_code', $referralCode)
            ->where('status', 'active')
            ->first();
    }

    private function welcomePromotion(): Promotion
    {
        return Promotion::updateOrCreate(
            ['code' => 'REFWELCOME'],
            [
                'name' => 'Ưu đãi chào mừng từ link giới thiệu',
                'danhmuc' => 'product',
                'loai' => 'fixed',
                'giatri' => 50000,
                'ngaybatdau' => now()->toDateString(),
                'ngayketthuc' => now()->addYear()->toDateString(),
                'trangthai' => 'running',
                'mota' => 'Voucher tự động tặng cho tài khoản mới đăng ký qua link affiliate.',
                'loai_dieu_kien' => 'order_total',
                'dieu_kien' => 500000,
                'congkhai' => 0,
                'dieu_kien_tang' => 0,
                'so_luong_phat' => 0,
            ]
        );
    }
}
