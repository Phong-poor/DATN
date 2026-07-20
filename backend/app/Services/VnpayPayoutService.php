<?php

namespace App\Services;

use App\Models\AffiliateWithdrawRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class VnpayPayoutService
{
    public function payout(AffiliateWithdrawRequest $withdraw): array
    {
        $endpoint = config('services.vnpay_payout.endpoint');
        $tmnCode = config('services.vnpay_payout.tmn_code');
        $hashSecret = config('services.vnpay_payout.hash_secret');

        if (!$endpoint || !$tmnCode || !$hashSecret) {
            throw new RuntimeException('Chua cau hinh VNPay payout. Vui long them VNPAY_PAYOUT_ENDPOINT, VNPAY_PAYOUT_TMN_CODE, VNPAY_PAYOUT_HASH_SECRET vao .env.');
        }

        $txnRef = 'AFF-WD-' . $withdraw->id . '-' . now()->format('YmdHis');
        $amount = (int) round((float) $withdraw->so_tien);
        $payload = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => config('services.vnpay_payout.command', 'payout'),
            'vnp_TmnCode' => $tmnCode,
            'vnp_TxnRef' => $txnRef,
            'vnp_Amount' => $amount,
            'vnp_CurrCode' => 'VND',
            'vnp_OrderInfo' => 'Chi tra hoa hong affiliate #' . $withdraw->id,
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_IpAddr' => request()->ip() ?: '127.0.0.1',
            'vnp_NotifyUrl' => config('services.vnpay_payout.notify_url'),
            'vnp_BankName' => $withdraw->ten_ngan_hang,
            'vnp_AccountName' => $withdraw->ten_chu_tai_khoan,
            'vnp_AccountNumber' => $withdraw->so_tai_khoan,
            'vnp_ExtraData' => base64_encode(json_encode([
                'withdraw_id' => $withdraw->id,
                'affiliate_user_id' => $withdraw->id_affiliate_khachhang,
            ], JSON_UNESCAPED_UNICODE)),
        ];

        $payload['vnp_SecureHash'] = $this->signature($payload, $hashSecret);

        $response = Http::timeout((int) config('services.vnpay_payout.timeout', 20))
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new RuntimeException('VNPay payout HTTP error: ' . $response->status() . ' - ' . Str::limit($response->body(), 500));
        }

        $data = $response->json() ?: [];
        $responseCode = (string) ($data['vnp_ResponseCode'] ?? $data['responseCode'] ?? $data['code'] ?? '');
        $isAccepted = in_array($responseCode, ['00', '0', 'processing', 'success'], true);

        if (!$isAccepted) {
            throw new RuntimeException($data['vnp_Message'] ?? $data['message'] ?? 'VNPay tu choi lenh chi tien.');
        }

        return [
            'transaction_id' => $data['vnp_TransactionNo'] ?? $data['transactionId'] ?? $txnRef,
            'provider' => 'VNPay Payout API',
            'status_flow' => ['processing'],
            'processed_at' => now()->toDateTimeString(),
            'raw' => $data,
            'message' => sprintf(
                'VNPay payout da gui lenh: %s chuyen %s VND den %s - %s - %s. Cho IPN VNPay xac nhan thanh cong.',
                $data['vnp_TransactionNo'] ?? $txnRef,
                number_format($amount, 0, '.', ''),
                $withdraw->ten_ngan_hang,
                $withdraw->ten_chu_tai_khoan,
                $withdraw->so_tai_khoan
            ),
        ];
    }

    public function verifySignature(array $payload): bool
    {
        $hashSecret = config('services.vnpay_payout.hash_secret');
        $secureHash = (string) ($payload['vnp_SecureHash'] ?? $payload['secureHash'] ?? '');

        if (!$hashSecret || !$secureHash) {
            return false;
        }

        return hash_equals($secureHash, $this->signature($payload, $hashSecret));
    }

    private function signature(array $payload, string $hashSecret): string
    {
        $data = collect($payload)
            ->except(['vnp_SecureHash', 'vnp_SecureHashType', 'secureHash', 'secureHashType'])
            ->filter(fn ($value) => is_scalar($value) || $value === null)
            ->sortKeys()
            ->map(fn ($value, $key) => urlencode((string) $key) . '=' . urlencode((string) $value))
            ->implode('&');

        return hash_hmac('sha512', $data, $hashSecret);
    }
}
