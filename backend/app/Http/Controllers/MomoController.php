<?php

namespace App\Http\Controllers;

use App\Events\NewOrderPlaced;
use App\Models\DatHang;
use App\Models\GioHang;
use App\Models\UserVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MomoController extends Controller
{
    public function createPaymentUrl(DatHang $order, ?string $requestType = null): string
    {
        return $this->createPayment($order, $requestType)['payUrl'];
    }

    public function createPayment(DatHang $order, ?string $requestType = null): array
    {
        $this->assertConfigured();
        $order->loadMissing('user');

        $endpoint = env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
        $partnerCode = env('MOMO_PARTNER_CODE');
        $accessKey = env('MOMO_ACCESS_KEY');
        $secretKey = env('MOMO_SECRET_KEY');
        $redirectUrl = env('MOMO_RETURN_URL', rtrim(env('APP_URL'), '/') . '/api/momo/return');
        $ipnUrl = env('MOMO_IPN_URL', rtrim(env('APP_URL'), '/') . '/api/momo/ipn');
        $amount = (int) round($order->tongtien);

        $requestType = $requestType ?: env('MOMO_REQUEST_TYPE', 'payWithMethod');
        $this->assertAmountSupported($amount, $requestType);
        $requestId = 'DH' . $order->id_dathang . '_' . time();
        $orderId = $requestId;
        $orderInfo = 'Thanh toan don hang #' . $order->id_dathang;
        $extraData = base64_encode(json_encode([
            'order_id' => $order->id_dathang,
            'user_id' => $order->id_khachhang,
        ]));

        $rawSignature = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}"
            . "&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}"
            . "&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}"
            . "&requestId={$requestId}&requestType={$requestType}";

        $payload = [
            'partnerCode' => $partnerCode,
            'partnerName' => env('APP_NAME', 'VinaTech'),
            'storeId' => 'VinaTech',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => hash_hmac('sha256', $rawSignature, $secretKey),
        ];

        if ($requestType === 'payWithCC') {
            $payload['userInfo'] = [
                'name' => $order->user?->ten ?: 'Khach hang VinaTech',
                'phoneNumber' => $order->user?->sodienthoai ?: '',
                'email' => $order->user?->email ?: 'sandbox@example.com',
            ];
        }

        $response = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $payload);

        if (!$response->successful()) {
            Log::error('MoMo create payment HTTP error', [
                'order_id' => $order->id_dathang,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Không gọi được cổng thanh toán MoMo sandbox.');
        }

        $data = $response->json();
        if ((int) ($data['resultCode'] ?? -1) !== 0 || empty($data['payUrl'])) {
            Log::warning('MoMo create payment rejected', [
                'order_id' => $order->id_dathang,
                'response' => $data,
            ]);
            throw new \RuntimeException($data['message'] ?? 'MoMo không trả về link thanh toán.');
        }

        if ($this->hasPaymentTracking()) {
            $order->update([
                'nha_cung_cap_thanh_toan' => 'momo',
                'trang_thai_thanh_toan' => 'pending',
                'ma_don_hang_thanh_toan' => $orderId,
                'ma_yeu_cau_thanh_toan' => $requestId,
                'ma_ket_qua_thanh_toan' => (int) ($data['resultCode'] ?? 0),
                'thong_bao_thanh_toan' => $data['message'] ?? 'MoMo payment created',
                'du_lieu_thanh_toan' => $this->mergePaymentPayload($order, [
                    'momo_create_request' => $this->withoutSignature($payload),
                    'momo_create_response' => $data,
                    'momo_request_type' => $requestType,
                ]),
            ]);
        }

        return $data;
    }

    public function momoReturn(Request $request)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $payload = $request->all();
        $order = $this->findOrderFromPayload($payload);

        if (!$order || !$this->verifyResultSignature($payload)) {
            return redirect($frontendUrl . '/payment-failed');
        }

        if ((int) ($payload['resultCode'] ?? -1) === 0) {
            try {
                $this->markPaidOrder($order, $payload);
                return redirect($frontendUrl . '/thank-you?status=success&order_id=' . $order->id_dathang);
            } catch (\Throwable $e) {
                Log::error('MoMo return confirm failed', [
                    'order_id' => $order->id_dathang,
                    'error' => $e->getMessage(),
                ]);
                return redirect($frontendUrl . '/payment-failed');
            }
        }

        $this->markFailedOrder($order, $payload);

        return redirect($frontendUrl . '/payment-failed');
    }

    public function momoIpn(Request $request)
    {
        $payload = $request->all();
        $order = $this->findOrderFromPayload($payload);

        if (!$order || !$this->verifyResultSignature($payload)) {
            return response()->json(['resultCode' => 97, 'message' => 'Invalid signature'], 400);
        }

        if ((int) ($payload['resultCode'] ?? -1) === 0) {
            try {
                $this->markPaidOrder($order, $payload);
                return response()->json(['resultCode' => 0, 'message' => 'Confirm Success']);
            } catch (\Throwable $e) {
                Log::error('MoMo IPN confirm failed', [
                    'order_id' => $order->id_dathang,
                    'error' => $e->getMessage(),
                ]);
                return response()->json(['resultCode' => 99, 'message' => $e->getMessage()], 400);
            }
        }

        $this->markFailedOrder($order, $payload);

        return response()->json(['resultCode' => 0, 'message' => 'Payment Failed']);
    }

    public function momoQuery(Request $request, int $id)
    {
        if (!$this->hasPaymentTracking()) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa chạy migration payment tracking cho bảng đơn hàng.',
            ], 422);
        }

        $order = DatHang::where('id_dathang', $id)
            ->where('id_khachhang', $request->user()->id)
            ->firstOrFail();

        if ($order->nha_cung_cap_thanh_toan !== 'momo' || !$order->ma_don_hang_thanh_toan) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này không phải giao dịch MoMo.',
            ], 422);
        }

        $data = $this->queryPaymentStatus($order);

        if ((int) ($data['resultCode'] ?? -1) === 0 && !empty($data['transId'])) {
            $this->markPaidOrder($order, $data);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'order' => $order->fresh(),
        ]);
    }

    public function queryPaymentStatus(DatHang $order): array
    {
        $this->assertConfigured();

        $endpoint = env('MOMO_QUERY_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/query');
        $partnerCode = env('MOMO_PARTNER_CODE');
        $accessKey = env('MOMO_ACCESS_KEY');
        $secretKey = env('MOMO_SECRET_KEY');
        $requestId = 'QUERY_' . $order->id_dathang . '_' . time();
        $orderId = $order->ma_don_hang_thanh_toan;

        $rawSignature = "accessKey={$accessKey}&orderId={$orderId}"
            . "&partnerCode={$partnerCode}&requestId={$requestId}";

        $payload = [
            'partnerCode' => $partnerCode,
            'requestId' => $requestId,
            'orderId' => $orderId,
            'lang' => 'vi',
            'signature' => hash_hmac('sha256', $rawSignature, $secretKey),
        ];

        $response = Http::timeout(30)
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Không kiểm tra được trạng thái giao dịch MoMo.');
        }

        $data = $response->json();
        $order->update([
            'du_lieu_thanh_toan' => $this->mergePaymentPayload($order, [
                'momo_query_request' => $this->withoutSignature($payload),
                'momo_query_response' => $data,
            ]),
        ]);

        return $data;
    }

    private function markPaidOrder(DatHang $order, array $payload): void
    {
        $shouldBroadcast = false;

        DB::transaction(function () use ($order, $payload, &$shouldBroadcast) {
            $freshOrder = DatHang::lockForUpdate()->findOrFail($order->id_dathang);

            if ($this->hasPaymentTracking() && $freshOrder->trang_thai_thanh_toan === 'paid') {
                return;
            }

            $this->assertResultMatchesOrder($freshOrder, $payload);

            $updateData = [
                'trangthai' => 'confirmed',
            ];

            if ($this->hasPaymentTracking()) {
                $updateData += [
                    'nha_cung_cap_thanh_toan' => 'momo',
                    'trang_thai_thanh_toan' => 'paid',
                    'ma_don_hang_thanh_toan' => $payload['orderId'] ?? $freshOrder->ma_don_hang_thanh_toan,
                    'ma_yeu_cau_thanh_toan' => $payload['requestId'] ?? $freshOrder->ma_yeu_cau_thanh_toan,
                    'ma_giao_dich_thanh_toan' => isset($payload['transId']) ? (string) $payload['transId'] : $freshOrder->ma_giao_dich_thanh_toan,
                    'ma_ket_qua_thanh_toan' => (int) ($payload['resultCode'] ?? 0),
                    'thong_bao_thanh_toan' => $payload['message'] ?? 'MoMo payment success',
                    'kieu_thanh_toan' => $payload['payType'] ?? $freshOrder->kieu_thanh_toan,
                    'thanh_toan_luc' => now(),
                    'du_lieu_thanh_toan' => $this->mergePaymentPayload($freshOrder, [
                        'momo_result' => $payload,
                    ]),
                ];
            }

            $freshOrder->update($updateData);

            GioHang::where('id_khachhang', $freshOrder->id_khachhang)->delete();
            $this->markVoucherUsed($freshOrder->fresh());
            $shouldBroadcast = true;
        });

        $this->clearDashboardCache();

        if ($shouldBroadcast) {
            event(new NewOrderPlaced($order->fresh()));
        }
    }

    private function markFailedOrder(DatHang $order, array $payload): void
    {
        DB::transaction(function () use ($order, $payload) {
            $freshOrder = DatHang::lockForUpdate()->find($order->id_dathang);

            if (!$freshOrder || ($this->hasPaymentTracking() && $freshOrder->trang_thai_thanh_toan === 'paid')) {
                return;
            }

            $freshOrderLoad = DatHang::with('chi_tiets')->find($freshOrder->id_dathang);
            if ($freshOrderLoad) {
                // Restore cart items
                foreach ($freshOrderLoad->chi_tiets as $detail) {
                    $cartItem = GioHang::where('id_khachhang', $freshOrderLoad->id_khachhang)
                        ->where('id_bienthe', $detail->id_bienthe)
                        ->first();
                    if ($cartItem) {
                        $cartItem->increment('soluong', $detail->soluong);
                    } else {
                        GioHang::create([
                            'id_khachhang' => $freshOrderLoad->id_khachhang,
                            'id_bienthe' => $detail->id_bienthe,
                            'soluong' => $detail->soluong,
                        ]);
                    }
                }

                // Restore stock
                foreach ($freshOrderLoad->chi_tiets as $chiTiet) {
                    if ($chiTiet->bienThe) {
                        $chiTiet->bienThe->increment('soluong', $chiTiet->soluong);
                    }
                }
            }

            // Restore coins
            if ($freshOrder->xu_dung > 0) {
                $user = $freshOrder->user;
                if ($user) {
                    $user->increment('xu', $freshOrder->xu_dung);
                    \App\Models\XuHistory::create([
                        'id_khachhang' => $freshOrder->id_khachhang,
                        'so_xu' => $freshOrder->xu_dung,
                        'loai_giao_dich' => 'hoan_tra',
                        'id_dathang' => $freshOrder->id_dathang,
                        'mo_ta' => 'Hoàn xu do thanh toán MoMo thất bại đơn hàng #' . $freshOrder->id_dathang,
                    ]);
                }
            }

            $updateData = [
                'trangthai' => 'cancelled',
                'lydo' => 'Thanh toán MoMo thất bại: ' . ($payload['message'] ?? 'Không rõ lý do'),
            ];

            if ($this->hasPaymentTracking()) {
                $updateData += [
                    'nha_cung_cap_thanh_toan' => 'momo',
                    'trang_thai_thanh_toan' => 'failed',
                    'ma_don_hang_thanh_toan' => $payload['orderId'] ?? $freshOrder->ma_don_hang_thanh_toan,
                    'ma_yeu_cau_thanh_toan' => $payload['requestId'] ?? $freshOrder->ma_yeu_cau_thanh_toan,
                    'ma_giao_dich_thanh_toan' => isset($payload['transId']) ? (string) $payload['transId'] : $freshOrder->ma_giao_dich_thanh_toan,
                    'ma_ket_qua_thanh_toan' => isset($payload['resultCode']) ? (int) $payload['resultCode'] : null,
                    'thong_bao_thanh_toan' => $payload['message'] ?? 'MoMo payment failed',
                    'kieu_thanh_toan' => $payload['payType'] ?? $freshOrder->kieu_thanh_toan,
                    'du_lieu_thanh_toan' => $this->mergePaymentPayload($freshOrder, [
                        'momo_result' => $payload,
                    ]),
                ];
            }

            $freshOrder->update($updateData);
        });

        $this->clearDashboardCache();
    }

    private function assertConfigured(): void
    {
        if (!env('MOMO_PARTNER_CODE') || !env('MOMO_ACCESS_KEY') || !env('MOMO_SECRET_KEY')) {
            throw new \RuntimeException('Thiếu cấu hình MoMo sandbox trong file .env.');
        }
    }

    private function assertAmountSupported(int $amount, string $requestType): void
    {
        if ($requestType === 'payWithATM' && ($amount < 10000 || $amount > 50000000)) {
            throw new \RuntimeException('Thẻ ATM/Napas qua MoMo chỉ hỗ trợ số tiền từ 10.000đ đến 50.000.000đ.');
        }

        if ($requestType === 'payWithCC' && ($amount < 1000 || $amount > 10000000)) {
            throw new \RuntimeException('Thẻ Visa/Mastercard/JCB qua MoMo chỉ hỗ trợ số tiền từ 1.000đ đến 10.000.000đ.');
        }

        if (!in_array($requestType, ['payWithATM', 'payWithCC'], true) && ($amount < 1000 || $amount > 50000000)) {
            throw new \RuntimeException('MoMo chỉ hỗ trợ số tiền từ 1.000đ đến 50.000.000đ.');
        }
    }

    private function verifyResultSignature(array $payload): bool
    {
        $secretKey = env('MOMO_SECRET_KEY');
        $accessKey = env('MOMO_ACCESS_KEY');
        $signature = $payload['signature'] ?? '';

        if (!$secretKey || !$accessKey || !$signature) {
            return false;
        }

        $rawSignature = 'accessKey=' . $accessKey
            . '&amount=' . ($payload['amount'] ?? '')
            . '&extraData=' . ($payload['extraData'] ?? '')
            . '&message=' . ($payload['message'] ?? '')
            . '&orderId=' . ($payload['orderId'] ?? '')
            . '&orderInfo=' . ($payload['orderInfo'] ?? '')
            . '&orderType=' . ($payload['orderType'] ?? '')
            . '&partnerCode=' . ($payload['partnerCode'] ?? '')
            . '&payType=' . ($payload['payType'] ?? '')
            . '&requestId=' . ($payload['requestId'] ?? '')
            . '&responseTime=' . ($payload['responseTime'] ?? '')
            . '&resultCode=' . ($payload['resultCode'] ?? '')
            . '&transId=' . ($payload['transId'] ?? '');

        $expectedSignature = hash_hmac('sha256', $rawSignature, $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    private function findOrderFromPayload(array $payload): ?DatHang
    {
        $localOrderId = $this->extractLocalOrderId((string) ($payload['orderId'] ?? ''));

        if (!$localOrderId) {
            return null;
        }

        return DatHang::find($localOrderId);
    }

    private function assertResultMatchesOrder(DatHang $order, array $payload): void
    {
        $payloadOrderId = (string) ($payload['orderId'] ?? '');

        if ($order->ma_don_hang_thanh_toan && $payloadOrderId !== $order->ma_don_hang_thanh_toan) {
            throw new \RuntimeException('Mã giao dịch MoMo không khớp đơn hàng.');
        }

        if ((int) ($payload['amount'] ?? 0) !== (int) round($order->tongtien)) {
            throw new \RuntimeException('Số tiền MoMo trả về không khớp đơn hàng.');
        }
    }

    private function extractLocalOrderId(string $momoOrderId): ?int
    {
        if (preg_match('/^DH(\d+)_/', $momoOrderId, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function markVoucherUsed(DatHang $order): void
    {
        if ($order->id_khuyenmai) {
            UserVoucher::where('id_user', $order->id_khachhang)
                ->where('id_voucher', $order->id_khuyenmai)
                ->update(['trang_thai' => 1]);
        }

        $freeshipPromotionId = data_get($order->du_lieu_thanh_toan, 'checkout.freeship_promotion_id');
        if ($freeshipPromotionId) {
            UserVoucher::where('id_user', $order->id_khachhang)
                ->where('id_voucher', $freeshipPromotionId)
                ->update(['trang_thai' => 1]);
        }
    }

    private function mergePaymentPayload(DatHang $order, array $data): array
    {
        $current = $order->du_lieu_thanh_toan ?: [];

        if (is_string($current)) {
            $current = json_decode($current, true) ?: [];
        }

        return array_replace_recursive($current, $data);
    }

    private function withoutSignature(array $payload): array
    {
        unset($payload['signature']);

        return $payload;
    }

    private function hasPaymentTracking(): bool
    {
        return Schema::hasColumn('dathang', 'nha_cung_cap_thanh_toan');
    }

    private function clearDashboardCache(): void
    {
        Cache::forget('dashboard_data_all');
        Cache::forget('dashboard_data_week');
        Cache::forget('dashboard_data_month');
        Cache::forget('dashboard_data_year');
    }
}
