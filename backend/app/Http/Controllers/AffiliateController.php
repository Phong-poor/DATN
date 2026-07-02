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
        $profile = AffiliateProfile::where('id_khachhang', $user->id)->first();

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

        $pending = AffiliateCommission::where('id_affiliate_khachhang', $user->id)->where('trangthai', 'pending')->sum('so_tien');
        $approved = AffiliateCommission::where('id_affiliate_khachhang', $user->id)->where('trangthai', 'approved')->sum('so_tien');
        $paid = AffiliateCommission::where('id_affiliate_khachhang', $user->id)->where('trangthai', 'paid')->sum('so_tien');

        return response()->json([
            'active' => $profile->trangthai === 'active',
            'profile' => $profile,
            'ref_link' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/register?ref=' . $profile->ma_affiliate,
            'stats' => [
                'total_referrals' => AffiliateReferral::where('id_affiliate_khachhang', $user->id)->count(),
                'pending_commission' => (float) $pending,
                'approved_commission' => (float) $approved,
                'paid_commission' => (float) $paid,
                'available_balance' => (float) max(0, $approved - AffiliateWithdrawRequest::where('id_affiliate_khachhang', $user->id)->whereIn('trangthai', ['pending', 'approved', 'paid'])->sum('so_tien')),
            ],
        ]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $user = $request->user();
        $profile = AffiliateProfile::firstOrNew(['id_khachhang' => $user->id]);

        if (!$profile->exists) {
            $profile->ma_affiliate = $this->generateAffiliateCode();
        }

        $profile->ty_le_hoa_hong = $request->commission_rate ?? $profile->ty_le_hoa_hong ?? 5;
        $profile->trangthai = 'active';
        $profile->save();

        return response()->json([
            'message' => 'Kich hoat affiliate thanh cong.',
            'profile' => $profile,
            'ref_link' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/register?ref=' . $profile->ma_affiliate,
        ]);
    }

    public function referrals(Request $request)
    {
        $user = $request->user();
        $rows = AffiliateReferral::with('referredUser:id,ten,email,created_at')
            ->where('id_affiliate_khachhang', $user->id)
            ->latest()
            ->get();

        return response()->json($rows);
    }

    public function commissions(Request $request)
    {
        $user = $request->user();
        $rows = AffiliateCommission::with(['referredUser:id,ten,email', 'order:id_dathang,tongtien,trangthai,created_at'])
            ->where('id_affiliate_khachhang', $user->id)
            ->latest()
            ->get();

        return response()->json($rows);
    }

    public function withdraws(Request $request)
    {
        $user = $request->user();
        $rows = AffiliateWithdrawRequest::where('id_affiliate_khachhang', $user->id)
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
        $approved = (float) AffiliateCommission::where('id_affiliate_khachhang', $user->id)->where('trangthai', 'approved')->sum('so_tien');
        $locked = (float) AffiliateWithdrawRequest::where('id_affiliate_khachhang', $user->id)->whereIn('trangthai', ['pending', 'approved', 'paid'])->sum('so_tien');
        $available = max(0, $approved - $locked);
        $amount = (float) $request->amount;

        if ($amount > $available) {
            return response()->json([
                'message' => 'So du kha dung khong du de rut.',
                'available_balance' => $available,
            ], 422);
        }

        $row = AffiliateWithdrawRequest::create([
            'id_affiliate_khachhang' => $user->id,
            'so_tien' => $amount,
            'ten_ngan_hang' => $request->bank_name,
            'ten_chu_tai_khoan' => $request->bank_account_name,
            'so_tai_khoan' => $request->bank_account_number,
            'trangthai' => 'pending',
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
        } while (AffiliateProfile::where('ma_affiliate', $code)->exists());

        return $code;
    }
}
