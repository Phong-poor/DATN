<?php

namespace App\Console\Commands;

use App\Models\DatHang;
use App\Models\UserVoucher;
use App\Models\XuHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpirePendingPayments extends Command
{
    protected $signature = 'orders:expire-pending-payments';
    protected $description = 'Cancel expired online payments and restore reserved resources';

    public function handle(): int
    {
        $ids = DatHang::whereIn('nha_cung_cap_thanh_toan', ['momo', 'vnpay', 'sepay'])
            ->where('trangthai', 'pending')
            ->where('trang_thai_thanh_toan', 'pending')
            ->where('created_at', '<', now()->subMinutes(15))
            ->pluck('id_dathang');

        $expired = 0;
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$expired) {
                $order = DatHang::with(['chi_tiets.bienThe', 'user'])->lockForUpdate()->find($id);
                if (! $order || $order->trangthai !== 'pending' || $order->trang_thai_thanh_toan !== 'pending') return;

                foreach ($order->chi_tiets as $detail) {
                    if ($detail->bienThe) $detail->bienThe->increment('soluong', $detail->soluong);
                }

                if ($order->xu_dung > 0 && $order->user) {
                    $order->user->increment('xu', $order->xu_dung);
                    XuHistory::create([
                        'id_khachhang' => $order->id_khachhang, 'so_xu' => $order->xu_dung,
                        'loai_giao_dich' => 'hoan_tra', 'id_dathang' => $order->id_dathang,
                        'mo_ta' => 'Hoàn xu do đơn hàng #'.$order->id_dathang.' hết hạn thanh toán',
                    ]);
                }

                $promoIds = array_filter([
                    $order->id_khuyenmai,
                    data_get($order->du_lieu_thanh_toan, 'checkout.freeship_promotion_id'),
                ]);
                if ($promoIds) {
                    UserVoucher::where('id_user', $order->id_khachhang)
                        ->whereIn('id_voucher', $promoIds)
                        ->update(['trang_thai' => 0]);
                }

                $paymentData = $order->du_lieu_thanh_toan ?: [];
                $paymentData['status_history']['failed'] = now()->toDateTimeString();
                $order->update([
                    'trangthai' => 'cancelled', 'trang_thai_thanh_toan' => 'failed',
                    'lydo' => 'Hết hạn thời gian thanh toán trực tuyến (15 phút)',
                    'du_lieu_thanh_toan' => $paymentData,
                ]);
                $expired++;
            });
        }

        $this->info("Expired {$expired} pending payment(s).");
        return self::SUCCESS;
    }
}
