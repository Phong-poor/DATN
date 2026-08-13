<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'balance' => (float) $this->balance,
            'pending_balance' => (float) $this->pending_balance,
            'total_withdrawn' => (float) $this->total_withdrawn,
            'formatted' => [
                'balance' => number_format((float) $this->balance, 0, ',', '.') . 'd',
                'pending_balance' => number_format((float) $this->pending_balance, 0, ',', '.') . 'd',
                'total_withdrawn' => number_format((float) $this->total_withdrawn, 0, ',', '.') . 'd',
            ],
        ];
    }
}
