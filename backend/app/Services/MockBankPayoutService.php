<?php

namespace App\Services;

use App\Models\AffiliateWithdrawRequest;

class MockBankPayoutService
{
    public function payout(AffiliateWithdrawRequest $withdraw): array
    {
        $transactionId = 'MOCKBANK-' . now()->format('YmdHis') . '-' . str_pad((string) $withdraw->id, 6, '0', STR_PAD_LEFT);

        return [
            'transaction_id' => $transactionId,
            'provider' => 'Mock Bank Payout API',
            'status_flow' => ['processing', 'paid'],
            'processed_at' => now()->toDateTimeString(),
            'message' => sprintf(
                'Mock payout thanh cong: %s da chuyen %s VND den %s - %s - %s. Demo bank notification: TK %s +%s VND tu NextGen Affiliate.',
                $transactionId,
                number_format((float) $withdraw->so_tien, 0, '.', ''),
                $withdraw->ten_ngan_hang,
                $withdraw->ten_chu_tai_khoan,
                $withdraw->so_tai_khoan,
                $withdraw->so_tai_khoan,
                number_format((float) $withdraw->so_tien, 0, '.', '')
            ),
        ];
    }
}
