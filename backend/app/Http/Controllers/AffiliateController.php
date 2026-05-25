<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateReferral;
use App\Models\AffiliateWithdrawRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AffiliateController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();
        $profile = AffiliateProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'active' => false,
                'profile' => null,
                'stats' => [
                    'total_referrals' => 0,
                    'pending_commission' => 0,
                    'approved_commission' => 0,
                    'paid_commission' => 0,
                ],
            ]);
        }

        $pending = AffiliateCommission::where('affiliate_user_id', $user->id)->where('status', 'pending')->sum('amount');
        $approved = AffiliateCommission::where('affiliate_user_id', $user->id)->where('status', 'approved')->sum('amount');
        $paid = AffiliateCommission::where('affiliate_user_id', $user->id)->where('status', 'paid')->sum('amount');

        return response()->json([
            'active' => $profile->status === 'active',
            'profile' => $profile,
            'ref_link' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/register?ref=' . $profile->affiliate_code,
            'stats' => [
                'total_referrals' => AffiliateReferral::where('affiliate_user_id', $user->id)->count(),
                'pending_commission' => (float) $pending,
                'approved_commission' => (float) $approved,
                'paid_commission' => (float) $paid,
                'available_balance' => (float) max(0, $approved - AffiliateWithdrawRequest::where('affiliate_user_id', $user->id)->whereIn('status', ['pending', 'approved', 'paid'])->sum('amount')),
            ],
        ]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $user = $request->user();
        $profile = AffiliateProfile::firstOrNew(['user_id' => $user->id]);

        if (!$profile->exists) {
            $profile->affiliate_code = $this->generateAffiliateCode();
        }

        $profile->commission_rate = $request->commission_rate ?? $profile->commission_rate ?? 5;
        $profile->status = 'active';
        $profile->save();

        return response()->json([
            'message' => 'Kich hoat affiliate thanh cong.',
            'profile' => $profile,
            'ref_link' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/register?ref=' . $profile->affiliate_code,
        ]);
    }

    public function referrals(Request $request)
    {
        $user = $request->user();
        $rows = AffiliateReferral::with('referredUser:id,name,email,created_at')
            ->where('affiliate_user_id', $user->id)
            ->latest()
            ->get();

        return response()->json($rows);
    }

    public function commissions(Request $request)
    {
        $user = $request->user();
        $rows = AffiliateCommission::with(['referredUser:id,name,email', 'order:id_dathang,tongtien,trangthai,created_at'])
            ->where('affiliate_user_id', $user->id)
            ->latest()
            ->get();

        return response()->json($rows);
    }

    public function withdraws(Request $request)
    {
        $user = $request->user();
        $rows = AffiliateWithdrawRequest::where('affiliate_user_id', $user->id)
            ->latest()
            ->get();

        return response()->json($rows);
    }

    public function requestWithdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'bank_name' => 'required|string|max:120',
            'bank_account_name' => 'required|string|max:120',
            'bank_account_number' => 'required|string|max:50',
        ]);

        $user = $request->user();
        $approved = (float) AffiliateCommission::where('affiliate_user_id', $user->id)->where('status', 'approved')->sum('amount');
        $locked = (float) AffiliateWithdrawRequest::where('affiliate_user_id', $user->id)->whereIn('status', ['pending', 'approved', 'paid'])->sum('amount');
        $available = max(0, $approved - $locked);
        $amount = (float) $request->amount;

        if ($amount > $available) {
            return response()->json([
                'message' => 'So du kha dung khong du de rut.',
                'available_balance' => $available,
            ], 422);
        }

        $row = AffiliateWithdrawRequest::create([
            'affiliate_user_id' => $user->id,
            'amount' => $amount,
            'bank_name' => $request->bank_name,
            'bank_account_name' => $request->bank_account_name,
            'bank_account_number' => $request->bank_account_number,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Da gui yeu cau rut tien.',
            'withdraw' => $row,
        ], 201);
    }

    private function generateAffiliateCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (AffiliateProfile::where('affiliate_code', $code)->exists());

        return $code;
    }
}
