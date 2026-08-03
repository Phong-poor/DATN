<?php

namespace Database\Seeders;

use App\Models\BienThe;
use App\Models\DatHang;
use App\Models\DatHangChiTiet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DashboardChartDemoSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::query()
            ->where('vaitro', 'user')
            ->orderBy('id')
            ->get();
        $variants = BienThe::query()
            ->where('soluong', '>', 0)
            ->orderBy('id_bienthe')
            ->get();

        if ($customers->isEmpty() || $variants->isEmpty()) {
            throw new RuntimeException('Cần ít nhất một khách hàng và một biến thể sản phẩm để tạo dữ liệu biểu đồ.');
        }

        $statuses = ['done', 'done', 'done', 'done', 'confirmed', 'shipping', 'pending', 'cancelled'];
        $paymentMethods = ['COD', 'VNPAY', 'MOMO'];
        $today = Carbon::today();
        $created = 0;
        $updated = 0;

        DB::transaction(function () use (
            $customers,
            $variants,
            $statuses,
            $paymentMethods,
            $today,
            &$created,
            &$updated
        ) {
            for ($daysAgo = 60; $daysAgo >= 0; $daysAgo--) {
                $date = $today->copy()->subDays($daysAgo);
                $dayIndex = 60 - $daysAgo;
                $weekendBoost = $date->isWeekend() ? 1 : 0;
                $campaignBoost = in_array($dayIndex, [12, 13, 29, 30, 45, 46, 58], true) ? 2 : 0;
                $orderCount = 2 + (($dayIndex * 7 + 3) % 4) + $weekendBoost + $campaignBoost;

                for ($slot = 1; $slot <= $orderCount; $slot++) {
                    $code = sprintf('DEMO-CHART-%s-%02d', $date->format('Ymd'), $slot);
                    $customer = $customers[($dayIndex + $slot * 3) % $customers->count()];
                    $variant = $variants[($dayIndex * 5 + $slot * 7) % $variants->count()];
                    $quantity = 1 + (($dayIndex + $slot) % 2);

                    $basePrice = (float) ($variant->gia ?: 12000000);
                    $naturalFactor = 0.82 + ((($dayIndex * 11 + $slot * 17) % 36) / 100);
                    $unitPrice = max(3500000, round(($basePrice * $naturalFactor) / 10000) * 10000);
                    $total = $unitPrice * $quantity;
                    $status = $statuses[($dayIndex + $slot * 2) % count($statuses)];
                    $createdAt = $date->copy()->setTime(
                        8 + (($slot * 2) % 12),
                        ($dayIndex * 13 + $slot * 7) % 60
                    );

                    $order = DatHang::query()
                        ->where('ma_don_hang_thanh_toan', $code)
                        ->first();

                    if ($order) {
                        $updated++;
                    } else {
                        $order = new DatHang();
                        $created++;
                    }

                    $order->id_khachhang = $customer->id;
                    $order->tongtien = $total;
                    $order->trangthai = $status;
                    $order->diachi = '[Dữ liệu biểu đồ demo] TP. Hồ Chí Minh';
                    $order->PTTT = $paymentMethods[($dayIndex + $slot) % count($paymentMethods)];
                    $order->trang_thai_thanh_toan = $status === 'done' ? 'paid' : 'unpaid';
                    $order->ma_don_hang_thanh_toan = $code;
                    $order->created_at = $createdAt;
                    $order->updated_at = $createdAt;
                    $order->saveQuietly();

                    DatHangChiTiet::query()->updateOrCreate(
                        [
                            'id_dathang' => $order->id_dathang,
                            'id_bienthe' => $variant->id_bienthe,
                        ],
                        [
                            'soluong' => $quantity,
                            'gia' => $unitPrice,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]
                    );
                }
            }
        });

        Cache::flush();

        $this->command?->info("Dữ liệu biểu đồ: tạo mới {$created} đơn, cập nhật {$updated} đơn demo.");
    }
}
