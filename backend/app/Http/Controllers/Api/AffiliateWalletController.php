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
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['data' => ['balance' => 0, 'pending_balance' => 0, 'total_withdrawn' => 0]]);
            }

            $wallet = AffiliateWallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'pending_balance' => 0, 'total_withdrawn' => 0]
            );

            return new AffiliateWalletResource($wallet);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['balance' => 0, 'pending_balance' => 0, 'total_withdrawn' => 0]]);
        }
    }
}
