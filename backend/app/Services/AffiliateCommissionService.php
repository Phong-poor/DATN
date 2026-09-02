<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateVideo;
use App\Models\DatHang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AffiliateCommissionService
{
    public function __construct(private readonly AffiliateBalanceService $balanceService)
    {
    }

    public function createPendingFromVideo(DatHang $order, $affiliateVideoId): ?AffiliateCommission
    {
        $affiliateVideoId = (int) $affiliateVideoId;
        if ($affiliateVideoId <= 0) {
            return null;
        }

        return DB::transaction(function () use ($order, $affiliateVideoId) {
            $video = AffiliateVideo::with('product')
                ->where('id', $affiliateVideoId)
                ->where('trangthai', 'approved')
                ->lockForUpdate()
                ->first();

            if (!$video || !$video->id_sanpham) {
                return null;
            }

            $profile = AffiliateProfile::where('id_khachhang', $video->id_affiliate_khachhang)
                ->where('trangthai', 'active')
                ->first();

            if (!$profile || (int) $profile->id_khachhang === (int) $order->id_khachhang) {
                return null;
            }

            $order->loadMissing('chi_tiets.bienThe');
            $baseAmount = $order->chi_tiets
                ->filter(fn ($item) => (int) $item->bienThe?->id_sanpham === (int) $video->id_sanpham)
                ->sum(fn ($item) => (float) $item->gia * (int) $item->soluong);

            if ($baseAmount <= 0) {
                return null;
            }

            $rate = max(0, (float) $profile->ty_le_hoa_hong);
            $amount = round($baseAmount * $rate / 100, 2);
            if ($amount <= 0) {
                return null;
            }

            $commission = AffiliateCommission::firstOrNew([
                'id_donhang' => $order->id_dathang,
            ]);

            if ($commission->exists && $commission->trangthai !== 'pending') {
                return $commission;
            }

            $commission->fill([
                'id_affiliate_khachhang' => $profile->id_khachhang,
                'id_khachhang_duoc_gioithieu' => $order->id_khachhang,
                'id_donhang' => $order->id_dathang,
                'so_tien' => $amount,
                'trangthai' => 'pending',
                'duoc_duyet_luc' => null,
                'duoc_thanh_toan_luc' => null,
                'ghichu' => sprintf(
                    'Hoa hong tu video affiliate #%d cho san pham #%d. Gia tri tinh hoa hong: %s, ty le: %s%%.',
                    $video->id,
                    $video->id_sanpham,
                    $baseAmount,
                    $rate
                ),
            ]);
            $commission->save();

            $this->mergeOrderAffiliateData($order, [
                'video_id' => $video->id,
                'product_id' => $video->id_sanpham,
                'affiliate_user_id' => $profile->id_khachhang,
                'commission_rate' => $rate,
                'commission_base_amount' => $baseAmount,
                'commission_amount' => $amount,
            ]);

            $this->syncOrderStatus($order->fresh());

            return $commission->fresh();
        });
    }

    public function syncOrderStatus(DatHang $order): void
    {
        $commission = AffiliateCommission::where('id_donhang', $order->id_dathang)->first();
        if (!$commission) {
            return;
        }

        $cancelStatuses = ['cancelled', 'refunded'];
        if (in_array($order->trangthai, $cancelStatuses, true) || $order->trang_thai_thanh_toan === 'refunded') {
            $commission->status = 'cancelled';
            $commission->approved_at = null;
            $commission->paid_at = null;
            $commission->save();
            $this->refreshProfileTotals($commission->id_affiliate_khachhang);
            return;
        }

        $isCompleted = in_array($order->trangthai, ['done', 'completed'], true);
        $isEarned = $isCompleted && $order->trang_thai_thanh_toan === 'paid';
        if ($isEarned && $commission->trangthai === 'pending') {
            $commission->status = 'approved';
            $commission->approved_at = now();
            $commission->save();
            $this->refreshProfileTotals($commission->id_affiliate_khachhang);
        }
    }

    public function cancelForOrder(DatHang $order): void
    {
        $commission = AffiliateCommission::where('id_donhang', $order->id_dathang)->first();
        if (!$commission) {
            return;
        }

        $commission->status = 'cancelled';
        $commission->approved_at = null;
        $commission->paid_at = null;
        $commission->save();
        $this->refreshProfileTotals($commission->id_affiliate_khachhang);
    }

    public function refreshProfileTotals(int $affiliateUserId): void
    {
        $this->balanceService->refreshProfileTotals($affiliateUserId);
    }

    private function mergeOrderAffiliateData(DatHang $order, array $affiliateData): void
    {
        if (!Schema::hasColumn($order->getTable(), 'du_lieu_thanh_toan')) {
            return;
        }

        $paymentData = $order->du_lieu_thanh_toan ?: [];
        if (is_string($paymentData)) {
            $paymentData = json_decode($paymentData, true) ?: [];
        }

        $paymentData['affiliate'] = $affiliateData;
        $order->du_lieu_thanh_toan = $paymentData;
        $order->save();
    }
}
