<?php

namespace App\Http\Controllers;

use App\Models\AffiliateProfile;
use App\Models\AffiliateWithdrawRequest;
use App\Services\MomoPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MomoPayoutController extends Controller
{
    public function ipn(Request $request, MomoPayoutService $momoPayout)
    {
        $payload = $request->all();

        if (!$momoPayout->verifySignature($payload)) {
            Log::warning('MoMo payout IPN invalid signature', $payload);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $withdrawId = $this->resolveWithdrawId($payload);
        if (!$withdrawId) {
            Log::warning('MoMo payout IPN missing withdraw id', $payload);
            return response()->json(['message' => 'Missing withdraw id'], 422);
        }

        $row = AffiliateWithdrawRequest::findOrFail($withdrawId);
        if ($row->trangthai === 'paid') {
            return response()->json(['message' => 'Already processed', 'withdraw_id' => $row->id, 'status' => 'paid']);
        }
        $resultCode = (string) ($payload['resultCode'] ?? $payload['status'] ?? '');
        $isSuccess = in_array($resultCode, ['0', '9000', 'success'], true);
        $isProcessing = in_array($resultCode, ['1000', 'processing'], true);
        $transId = $payload['transId'] ?? $payload['transactionId'] ?? $payload['requestId'] ?? null;
        $row->nha_cung_cap = 'momo';
        $row->ma_giao_dich = $transId;
        $row->du_lieu_chi_tra = $payload;

        if ($isSuccess) {
            $row->status = 'paid';
            $row->approved_at = $row->approved_at ?: now();
            $row->paid_at = now();
            $row->note = trim(($row->note ? $row->note . "\n" : '') . sprintf(
                'MoMo IPN thanh cong: %s da chuyen %s VND den %s - %s - %s.',
                $transId ?: 'N/A',
                number_format((float) $row->so_tien, 0, '.', ''),
                $row->ten_ngan_hang,
                $row->ten_chu_tai_khoan,
                $row->so_tai_khoan
            ));
        } elseif ($isProcessing) {
            $row->status = 'processing';
            $row->paid_at = null;
            $row->note = trim(($row->note ? $row->note . "\n" : '') . 'MoMo IPN: giao dich dang xu ly.');
        } else {
            $row->status = 'rejected';
            $row->paid_at = null;
            $row->note = trim(($row->note ? $row->note . "\n" : '') . 'MoMo IPN that bai: ' . ($payload['message'] ?? $payload['localMessage'] ?? 'Khong ro ly do.'));
        }

        $row->save();
        $this->refreshPaidTotal($row->id_affiliate_khachhang);

        return response()->json([
            'message' => 'MoMo payout IPN processed',
            'withdraw_id' => $row->id,
            'status' => $row->status,
        ]);
    }

    private function resolveWithdrawId(array $payload): ?int
    {
        if (!empty($payload['extraData'])) {
            $decoded = json_decode(base64_decode((string) $payload['extraData'], true) ?: '', true);
            if (is_array($decoded) && !empty($decoded['withdraw_id'])) {
                return (int) $decoded['withdraw_id'];
            }
        }

        foreach (['orderId', 'requestId'] as $key) {
            if (!empty($payload[$key]) && preg_match('/(\d+)/', (string) $payload[$key], $matches)) {
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
