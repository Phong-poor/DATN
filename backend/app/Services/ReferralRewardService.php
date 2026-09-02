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

        $profile = AffiliateProfile::where('ma_affiliate', $referralCode)
            ->where('trangthai', 'active')
            ->first();

        if (! $profile || (int) $profile->user_id === (int) $user->id) {
            return null;
        }

        AffiliateReferral::firstOrCreate(
            ['id_khachhang_duoc_gioithieu' => $user->id],
            [
                'id_affiliate_khachhang' => $profile->id_khachhang,
                'ma_ref' => $referralCode,
                'da_dang_ky_luc' => now(),
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

        return AffiliateProfile::where('ma_affiliate', $referralCode)
            ->where('trangthai', 'active')
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
