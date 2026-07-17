<?php

namespace App\Services;

use App\Models\AffiliateWithdrawRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MomoPayoutService
{
    public function payout(AffiliateWithdrawRequest $withdraw): array
    {
        $endpoint = config('services.momo_payout.endpoint');
        $partnerCode = config('services.momo_payout.partner_code');
        $accessKey = config('services.momo_payout.access_key');
        $secretKey = config('services.momo_payout.secret_key');

        if (!$endpoint || !$partnerCode || !$accessKey || !$secretKey) {
            throw new RuntimeException('Chua cau hinh MoMo payout. Vui long them MOMO_PAYOUT_ENDPOINT, MOMO_PAYOUT_PARTNER_CODE, MOMO_PAYOUT_ACCESS_KEY, MOMO_PAYOUT_SECRET_KEY vao .env.');
        }

        $requestId = 'AFF-WD-' . $withdraw->id . '-' . now()->format('YmdHis');
        $orderId = 'AFFILIATE-WITHDRAW-' . $withdraw->id;
        $amount = (int) round((float) $withdraw->so_tien);
        $requestType = config('services.momo_payout.request_type', 'disbursement');
        $notifyUrl = config('services.momo_payout.notify_url');
        $extraData = base64_encode(json_encode([
            'withdraw_id' => $withdraw->id,
            'affiliate_user_id' => $withdraw->id_affiliate_khachhang,
        ], JSON_UNESCAPED_UNICODE));

        $payload = [
            'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => 'Chi tra hoa hong affiliate #' . $withdraw->id,
            'requestType' => $requestType,
            'extraData' => $extraData,
            'notifyUrl' => $notifyUrl,
            'lang' => 'vi',
            'receiverInfo' => [
                'bankName' => $withdraw->ten_ngan_hang,
                'accountName' => $withdraw->ten_chu_tai_khoan,
                'accountNumber' => $withdraw->so_tai_khoan,
            ],
        ];

        $payload['signature'] = $this->signature([
            'accessKey' => $accessKey,
            'amount' => $amount,
            'extraData' => $extraData,
            'notifyUrl' => $notifyUrl,
            'orderId' => $orderId,
            'orderInfo' => $payload['orderInfo'],
            'partnerCode' => $partnerCode,
            'requestId' => $requestId,
            'requestType' => $requestType,
        ], $secretKey);

        $response = Http::timeout((int) config('services.momo_payout.timeout', 20))
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new RuntimeException('MoMo payout HTTP error: ' . $response->status() . ' - ' . Str::limit($response->body(), 500));
        }

        $data = $response->json() ?: [];
        $resultCode = (string) ($data['resultCode'] ?? $data['status'] ?? '');
        $isAccepted = in_array($resultCode, ['0', '9000', '1000', 'processing', 'success'], true);

        if (!$isAccepted) {
            throw new RuntimeException($data['message'] ?? $data['localMessage'] ?? 'MoMo tu choi lenh chi tien.');
        }

        return [
            'transaction_id' => $data['transId'] ?? $data['transactionId'] ?? $requestId,
            'provider' => 'MoMo Payout API',
            'status_flow' => ['processing'],
            'processed_at' => now()->toDateTimeString(),
            'raw' => $data,
            'message' => sprintf(
                'MoMo payout da gui lenh: %s chuyen %s VND den %s - %s - %s. Cho IPN MoMo xac nhan thanh cong.',
                $data['transId'] ?? $requestId,
                number_format($amount, 0, '.', ''),
                $withdraw->ten_ngan_hang,
                $withdraw->ten_chu_tai_khoan,
                $withdraw->so_tai_khoan
            ),
        ];
    }

    public function verifySignature(array $payload): bool
    {
        $secretKey = config('services.momo_payout.secret_key');
        $signature = (string) ($payload['signature'] ?? '');
        if (!$secretKey || !$signature) {
            return false;
        }

        $fields = collect($payload)
            ->except(['signature'])
            ->filter(fn ($value) => is_scalar($value) || $value === null)
            ->sortKeys()
            ->all();

        return hash_equals($signature, $this->signature($fields, $secretKey));
    }

    private function signature(array $fields, string $secretKey): string
    {
        ksort($fields);
        $raw = collect($fields)
            ->map(fn ($value, $key) => $key . '=' . (string) $value)
            ->implode('&');

        return hash_hmac('sha256', $raw, $secretKey);
    }
}
