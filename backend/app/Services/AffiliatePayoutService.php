<?php

namespace App\Services;

use App\Models\AffiliateWithdrawRequest;
use RuntimeException;

class AffiliatePayoutService
{
    public function __construct(
        private readonly MockBankPayoutService $mockBank,
        private readonly VnpayPayoutService $vnpay,
        private readonly MomoPayoutService $momo,
    ) {}

    public function send(AffiliateWithdrawRequest $withdraw): array
    {
        $provider = strtolower((string) config('affiliate.payout_provider', 'mock'));
        $result = match ($provider) {
            'mock' => $this->mockBank->payout($withdraw),
            'vnpay' => $this->vnpay->payout($withdraw),
            'momo' => $this->momo->payout($withdraw),
            default => throw new RuntimeException('Nhà cung cấp chi trả affiliate không hợp lệ.'),
        };

        return [
            ...$result,
            'provider_code' => $provider,
            'final_status' => in_array('paid', $result['status_flow'] ?? [], true) ? 'paid' : 'processing',
        ];
    }
}
