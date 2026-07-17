<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AffiliateWalletResource;
use App\Models\AffiliateWallet;
use Illuminate\Http\Request;

class AffiliateWalletController extends Controller
{
    public function show(Request $request)
    {
        $wallet = AffiliateWallet::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0, 'pending_balance' => 0, 'total_withdrawn' => 0]
        );

        return new AffiliateWalletResource($wallet);
    }
}
