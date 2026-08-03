<?php

namespace App\Http\Controllers;

use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawRequest;
use App\Services\VnpayPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VnpayPayoutController extends Controller
{
    public function ipn(Request $request, VnpayPayoutService $vnpayPayout)
    {
        $payload = $request->all();

        if (!$vnpayPayout->verifySignature($payload)) {
            Log::warning('VNPay payout IPN invalid signature', $payload);
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature'], 401);
        }

        $withdrawId = $this->resolveWithdrawId($payload);
        if (!$withdrawId) {
            Log::warning('VNPay payout IPN missing withdraw id', $payload);
            return response()->json(['RspCode' => '01', 'Message' => 'Withdraw not found'], 422);
        }

        $row = AffiliateWithdrawRequest::findOrFail($withdrawId);
        $responseCode = (string) ($payload['vnp_ResponseCode'] ?? $payload['responseCode'] ?? '');
        $isSuccess = in_array($responseCode, ['00', '0', 'success'], true);
        $isProcessing = in_array($responseCode, ['09', '10', 'processing'], true);
        $transactionNo = $payload['vnp_TransactionNo'] ?? $payload['transactionId'] ?? $payload['vnp_TxnRef'] ?? null;

        if ($isSuccess) {
            $row->status = 'paid';
            $row->approved_at = $row->approved_at ?: now();
            $row->paid_at = now();
            $row->note = trim(($row->note ? $row->note . "\n" : '') . sprintf(
                'VNPay IPN thanh cong: %s da chuyen %s VND den %s - %s - %s.',
                $transactionNo ?: 'N/A',
                number_format((float) $row->so_tien, 0, '.', ''),
                $row->ten_ngan_hang,
                $row->ten_chu_tai_khoan,
                $row->so_tai_khoan
            ));
        } elseif ($isProcessing) {
            $row->status = 'processing';
            $row->paid_at = null;
            $row->note = trim(($row->note ? $row->note . "\n" : '') . 'VNPay IPN: giao dich dang xu ly.');
        } else {
            $row->status = 'rejected';
            $row->paid_at = null;
            $row->note = trim(($row->note ? $row->note . "\n" : '') . 'VNPay IPN that bai: ' . ($payload['vnp_Message'] ?? $payload['message'] ?? 'Khong ro ly do.'));
        }

        $row->save();
        $this->refreshPaidTotal($row->id_affiliate_khachhang);

        return response()->json([
            'RspCode' => '00',
            'Message' => 'Confirm Success',
            'withdraw_id' => $row->id,
            'status' => $row->status,
        ]);
    }

    private function resolveWithdrawId(array $payload): ?int
    {
        if (!empty($payload['vnp_ExtraData'])) {
            $decoded = json_decode(base64_decode((string) $payload['vnp_ExtraData'], true) ?: '', true);
            if (is_array($decoded) && !empty($decoded['withdraw_id'])) {
                return (int) $decoded['withdraw_id'];
            }
        }

        foreach (['vnp_TxnRef', 'requestId', 'orderId'] as $key) {
            if (!empty($payload[$key]) && preg_match('/AFF-WD-(\d+)/', (string) $payload[$key], $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function refreshPaidTotal(int $affiliateUserId): void
    {
        $profile = AffiliateProfile::where('id_khachhang', $affiliateUserId)->first();
        if (!$profile) {
            return;
        }

        $profile->tong_da_thanh_toan = (float) AffiliateWithdrawRequest::where('id_affiliate_khachhang', $affiliateUserId)
            ->where('trangthai', 'paid')
            ->sum('so_tien');
        $profile->save();
    }
}
