<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateWithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_code' => $this->transaction_code,
            'amount' => (float) $this->amount,
            'bank_name' => $this->bank_name,
            'phone_account_masked' => $this->masked_phone_account,
            'account_name' => $this->account_name,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'sms_status' => $this->sms_status,
            'sms_status_label' => $this->sms_status_label,
            'sms_message_id' => $this->sms_message_id,
            'sms_error' => $this->sms_error,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'created_at_formatted' => optional($this->created_at)->format('H:i d/m/Y'),
            'formatted' => [
                'amount' => number_format((float) $this->amount, 0, ',', '.') . 'd',
                'balance_before' => number_format((float) $this->balance_before, 0, ',', '.') . 'd',
                'balance_after' => number_format((float) $this->balance_after, 0, ',', '.') . 'd',
            ],
        ];
    }
}
