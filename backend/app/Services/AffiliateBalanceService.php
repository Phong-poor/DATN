<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawRequest;

class AffiliateBalanceService
{
    public function summary(int $affiliateUserId): array
    {
        $pending = (float) AffiliateCommission::where('id_affiliate_khachhang', $affiliateUserId)
            ->where('trangthai', 'pending')->sum('so_tien');
        $earned = (float) AffiliateCommission::where('id_affiliate_khachhang', $affiliateUserId)
            ->whereIn('trangthai', ['approved', 'paid'])->sum('so_tien');
        $withdrawn = (float) AffiliateWithdrawRequest::where('id_affiliate_khachhang', $affiliateUserId)
            ->where('trangthai', 'paid')->sum('so_tien');
        $reserved = (float) AffiliateWithdrawRequest::where('id_affiliate_khachhang', $affiliateUserId)
            ->whereIn('trangthai', ['pending', 'approved', 'processing'])->sum('so_tien');

        return [
            'pending_commission' => $pending,
            'approved_commission' => $earned,
            'paid_commission' => $withdrawn,
            'reserved_withdrawal' => $reserved,
            'available_balance' => max(0, $earned - $withdrawn - $reserved),
        ];
    }

    public function refreshProfileTotals(int $affiliateUserId): void
    {
        $profile = AffiliateProfile::where('id_khachhang', $affiliateUserId)->first();
        if (!$profile) {
            return;
        }

        $summary = $this->summary($affiliateUserId);
        $profile->tong_thu_nhap = $summary['approved_commission'];
        $profile->tong_da_thanh_toan = $summary['paid_commission'];
        $profile->save();
    }
}
