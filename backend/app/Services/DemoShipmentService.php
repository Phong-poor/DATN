<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\DatHang;
use Illuminate\Support\Carbon;

class DemoShipmentService
{
    public function syncDueShipments(): array
    {
        $checked = 0;
        $updated = 0;
        $created = 0;

        DatHang::whereNotIn('trangthai', ['cancelled', 'refunded', 'refund_rejected'])
            ->get()
            ->each(function (DatHang $order) use (&$checked, &$updated, &$created) {
                $checked++;
                $currentOrderStatus = (string) $order->trangthai;

                if (str_starts_with($currentOrderStatus, 'refund')) {
                    return;
                }

                $paymentData = $order->du_lieu_thanh_toan ?? [];
                $shipment = $paymentData['shipping_demo'] ?? null;

                if (empty($shipment['tracking_code'])) {
                    $isCod = !in_array(strtolower(trim((string)$order->PTTT)), ['vnpay', 'momo']);
                    $isPaid = $order->trang_thai_thanh_toan === 'paid';

                    if ($currentOrderStatus === 'pending' && ($isCod || $isPaid) && $order->created_at && $order->created_at->diffInSeconds(now()) >= $this->confirmAfterSeconds()) {
                        $shipment = $this->buildDemoShipmentForOrderStatus($order, 'confirmed');
                        $paymentData = $this->paymentDataWithStatusTime($order, 'confirmed');
                        $paymentData['shipping_demo'] = $shipment;

                        $order->update([
                            'trangthai' => 'confirmed',
                            'du_lieu_thanh_toan' => $paymentData,
                        ]);

                        $created++;
                        $updated++;
                        event(new OrderStatusUpdated($order->fresh(['user', 'chi_tiets.bienThe.sanPham'])));
                    }

                    if (in_array($currentOrderStatus, ['confirmed', 'shipping', 'done', 'completed'], true)) {
                        $normalizedStatus = $currentOrderStatus === 'completed' ? 'done' : $currentOrderStatus;
                        $shipment = $this->buildDemoShipmentForOrderStatus($order, $normalizedStatus);
                        $paymentData = $this->paymentDataWithStatusTime($order, $normalizedStatus);
                        $paymentData['shipping_demo'] = $shipment;

                        $order->update([
                            'trangthai' => $normalizedStatus,
                            'du_lieu_thanh_toan' => $paymentData,
                        ]);

                        $created++;
                        event(new OrderStatusUpdated($order->fresh(['user', 'chi_tiets.bienThe.sanPham'])));
                    }

                    return;
                }

                if (($shipment['status'] ?? null) === 'delivery_failed' && ($shipment['can_retry'] ?? false)) {
                    $lastSyncAt = $shipment['last_sync_at'] ?? $shipment['failed_at'] ?? null;
                    $attempts = (int) ($shipment['delivery_attempts'] ?? 0);

                    if ($lastSyncAt && $attempts < 3 && Carbon::parse($lastSyncAt)->diffInSeconds(now()) >= $this->retryAfterSeconds()) {
                        $nextAttempt = $attempts + 1;
                        $shipment['failure_reason'] = null;
                        $shipment['can_retry'] = false;
                        $shipment['auto_retry_at'] = now()->toDateTimeString();

                        $shipment = $this->appendShipmentTimeline(
                            $shipment,
                            'delivering',
                            "Hệ thống tự động sắp xếp giao lại lần {$nextAttempt}/3."
                        );

                        $paymentData = $this->paymentDataWithStatusTime($order, 'shipping');
                        $paymentData['shipping_demo'] = $shipment;

                        $order->update([
                            'trangthai' => 'shipping',
                            'du_lieu_thanh_toan' => $paymentData,
                        ]);

                        $updated++;
                        event(new OrderStatusUpdated($order->fresh(['user', 'chi_tiets.bienThe.sanPham'])));
                    }

                    return;
                }

                $step = $this->autoShipmentSteps()[$shipment['status'] ?? null] ?? null;
                if (! $step) {
                    return;
                }

                $lastSyncAt = $shipment['last_sync_at'] ?? $shipment['created_at'] ?? null;
                if (! $lastSyncAt) {
                    return;
                }

                $afterSeconds = $shipment['auto_plan'][$step['plan_key'] ?? ''] ?? $step['after_seconds'];
                if (Carbon::parse($lastSyncAt)->diffInSeconds(now()) < $afterSeconds) {
                    return;
                }

                $shipment = $this->appendShipmentTimeline(
                    $shipment,
                    $step['next_shipment_status'],
                    $step['note']
                );

                $paymentData = $this->paymentDataWithStatusTime($order, $step['next_order_status']);
                $paymentData['shipping_demo'] = $shipment;

                $order->update([
                    'trangthai' => $step['next_order_status'],
                    'du_lieu_thanh_toan' => $paymentData,
                ]);

                $updated++;
                event(new OrderStatusUpdated($order->fresh(['user', 'chi_tiets.bienThe.sanPham'])));
            });

        return compact('checked', 'updated', 'created');
    }

    private function paymentDataWithStatusTime(DatHang $order, string $status, $time = null): array
    {
        $paymentData = $order->du_lieu_thanh_toan ?? [];
        $history = $paymentData['status_history'] ?? [];

        if (! isset($history['pending']) && $order->created_at) {
            $history['pending'] = $order->created_at->toDateTimeString();
        }

        $history[$status] = ($time ?: now())->toDateTimeString();
        $paymentData['status_history'] = $history;

        return $paymentData;
    }

    private function shipmentStatusLabels(): array
    {
        return [
            'created' => 'Đã tạo vận đơn',
            'waiting_pickup' => 'Chờ lấy hàng',
            'picked_up' => 'Đã lấy hàng',
            'delivering' => 'Đang giao hàng',
            'delivered' => 'Giao thành công',
            'delivery_failed' => 'Giao thất bại',
            'returning' => 'Đang hoàn về',
            'returned' => 'Đã hoàn về kho',
        ];
    }

    private function appendShipmentTimeline(array $shipment, string $status, ?string $note = null, $time = null): array
    {
        $labels = $this->shipmentStatusLabels();
        $timeline = $shipment['timeline'] ?? [];

        if (! $time && ! empty($shipment['created_at']) && $status === 'created') {
            $time = $shipment['created_at'];
        }

        if (! $time && ! empty($shipment['last_sync_at'])) {
            $plan = $shipment['auto_plan'] ?? [];
            $offsets = [
                'waiting_pickup' => $this->randomScaledSeconds(300, 1200),
                'picked_up' => $plan['pickup_after_seconds'] ?? 10800,
                'delivering' => $plan['dispatch_after_seconds'] ?? 7200,
                'delivered' => $plan['delivery_after_seconds'] ?? 86400,
            ];

            if (isset($offsets[$status])) {
                $time = Carbon::parse($shipment['last_sync_at'])->addSeconds($offsets[$status]);
            }
        }

        $eventTime = $time ? Carbon::parse($time) : now();

        $timeline[] = [
            'status' => $status,
            'label' => $labels[$status] ?? $status,
            'note' => $note,
            'time' => $eventTime->toDateTimeString(),
        ];

        $shipment['status'] = $status;
        $shipment['status_label'] = $labels[$status] ?? $status;
        $shipment['last_sync_at'] = $eventTime->toDateTimeString();
        $shipment['timeline'] = $timeline;

        return $shipment;
    }

    private function buildDemoShipmentForOrderStatus(DatHang $order, ?string $status = null): array
    {
        $status = $status ?: (string) $order->trangthai;
        $shipment = $this->buildDemoShipment($order);

        if (in_array($status, ['shipping', 'done', 'completed'], true)) {
            $shipment = $this->appendShipmentTimeline(
                $shipment,
                'picked_up',
                'Đơn vị vận chuyển đã lấy hàng tại kho.'
            );

            $shipment = $this->appendShipmentTimeline(
                $shipment,
                'delivering',
                'Shipper đang giao hàng đến địa chỉ của khách.'
            );
        }

        if (in_array($status, ['done', 'completed'], true)) {
            $shipment = $this->appendShipmentTimeline(
                $shipment,
                'delivered',
                'Khách hàng đã nhận hàng thành công.'
            );
        }

        return $shipment;
    }

    private function buildDemoShipment(DatHang $order): array
    {
        $trackingCode = 'NGX'.now()->format('Ymd').str_pad((string) $order->id_dathang, 5, '0', STR_PAD_LEFT);
        $paymentMethod = strtoupper((string) ($order->PTTT ?? ''));
        $isCod = str_contains($paymentMethod, 'COD') || str_contains($paymentMethod, 'TIEN MAT') || str_contains($paymentMethod, 'TIEN_MAT');
        $plan = $this->demoShipmentPlan($order);
        $createdAt = $this->demoShipmentStartTime($order);

        $shipment = [
            'provider' => 'NextGen Express',
            'tracking_code' => $trackingCode,
            'fee' => 30000,
            'cod_amount' => $isCod ? (int) $order->tongtien : 0,
            'service_area' => $plan['service_area'],
            'service_level' => $plan['service_level'],
            'expected_delivery_date' => $createdAt->copy()->addSeconds($plan['pickup_after_seconds'] + $plan['dispatch_after_seconds'] + $plan['delivery_after_seconds'])->toDateString(),
            'auto_plan' => $plan,
            'created_at' => $createdAt->toDateTimeString(),
            'last_sync_at' => $createdAt->toDateTimeString(),
            'timeline' => [],
        ];

        $shipment = $this->appendShipmentTimeline($shipment, 'created', 'Admin tạo vận đơn demo trên hệ thống NextGen.');

        return $this->appendShipmentTimeline($shipment, 'waiting_pickup', 'Đơn hàng đang chờ nhân viên kho bàn giao cho đơn vị vận chuyển.');
    }

    private function demoShipmentStartTime(DatHang $order): Carbon
    {
        $start = $order->created_at
            ? Carbon::parse($order->created_at)->addMinutes(15)
            : Carbon::now();

        return $start->greaterThan(Carbon::now()) ? Carbon::now() : $start;
    }

    private function demoShipmentPlan(DatHang $order): array
    {
        $address = mb_strtolower((string) ($order->diachi ?? ''), 'UTF-8');
        $isMetro = str_contains($address, 'hcm')
            || str_contains($address, 'ho chi minh')
            || str_contains($address, 'hồ chí minh')
            || str_contains($address, 'sai gon')
            || str_contains($address, 'sài gòn')
            || str_contains($address, 'ha noi')
            || str_contains($address, 'hà nội');

        $isNearProvince = str_contains($address, 'binh duong')
            || str_contains($address, 'bình dương')
            || str_contains($address, 'dong nai')
            || str_contains($address, 'đồng nai')
            || str_contains($address, 'long an')
            || str_contains($address, 'ba ria')
            || str_contains($address, 'bà rịa')
            || str_contains($address, 'tay ninh')
            || str_contains($address, 'tây ninh')
            || str_contains($address, 'bac ninh')
            || str_contains($address, 'bắc ninh')
            || str_contains($address, 'hung yen')
            || str_contains($address, 'hưng yên')
            || str_contains($address, 'hai phong')
            || str_contains($address, 'hải phòng');

        if ($isMetro) {
            return [
                'service_area' => 'Nội thành',
                'service_level' => 'Giao nhanh trong ngày',
                'pickup_after_seconds' => $this->randomScaledSeconds(7200, 10800),
                'dispatch_after_seconds' => $this->randomScaledSeconds(1800, 3600),
                'delivery_after_seconds' => $this->randomScaledSeconds(7200, 14400),
            ];
        }

        if ($isNearProvince) {
            return [
                'service_area' => 'Tỉnh gần',
                'service_level' => 'Giao liên tỉnh nhanh',
                'pickup_after_seconds' => $this->randomScaledSeconds(7200, 10800),
                'dispatch_after_seconds' => $this->randomScaledSeconds(3600, 7200),
                'delivery_after_seconds' => $this->randomScaledSeconds(43200, 86400),
            ];
        }

        return [
            'service_area' => 'Tỉnh xa',
            'service_level' => 'Giao tiêu chuẩn',
            'pickup_after_seconds' => $this->randomScaledSeconds(7200, 10800),
            'dispatch_after_seconds' => $this->randomScaledSeconds(7200, 14400),
            'delivery_after_seconds' => $this->randomScaledSeconds(172800, 345600),
        ];
    }

    private function confirmAfterSeconds(): int
    {
        $default = app()->environment('local') ? 60 : 900;

        return max(1, (int) env('DEMO_SHIPMENT_CONFIRM_SECONDS', $default));
    }

    private function demoTimeScale(): float
    {
        $default = app()->environment('local') ? 0.02 : 1.0;

        return max(0.001, (float) env('DEMO_SHIPMENT_TIME_SCALE', $default));
    }

    private function scaledSeconds(int $seconds): int
    {
        return max(1, (int) round($seconds * $this->demoTimeScale()));
    }

    private function randomScaledSeconds(int $min, int $max): int
    {
        return $this->scaledSeconds(random_int($min, $max));
    }

    private function retryAfterSeconds(): int
    {
        return $this->randomScaledSeconds(7200, 14400);
    }

    private function autoShipmentSteps(): array
    {
        return [
            'waiting_pickup' => [
                'next_shipment_status' => 'picked_up',
                'next_order_status' => 'shipping',
                'plan_key' => 'pickup_after_seconds',
                'after_seconds' => 10800,
                'note' => 'Đơn vị vận chuyển đã tự động xác nhận lấy hàng tại kho.',
            ],
            'picked_up' => [
                'next_shipment_status' => 'delivering',
                'next_order_status' => 'shipping',
                'plan_key' => 'dispatch_after_seconds',
                'after_seconds' => 3600,
                'note' => 'Đơn hàng đang được shipper giao đến khách.',
            ],
            'delivering' => [
                'next_shipment_status' => 'delivered',
                'next_order_status' => 'done',
                'plan_key' => 'delivery_after_seconds',
                'after_seconds' => 86400,
                'note' => 'Hệ thống ghi nhận khách đã nhận hàng thành công.',
            ],
        ];
    }
}
