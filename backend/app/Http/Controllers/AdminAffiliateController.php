<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateReferral;
use App\Models\AffiliateWithdrawRequest;
use Illuminate\Http\Request;

class AdminAffiliateController extends Controller
{
    public function index()
    {
        $profiles = AffiliateProfile::with('user:id,ten,email')
            ->orderByDesc('id')
            ->get();

        $referralCounts = AffiliateReferral::selectRaw('id_affiliate_khachhang, COUNT(*) as total')
            ->groupBy('id_affiliate_khachhang')
            ->pluck('total', 'id_affiliate_khachhang');

        $pendingWithdraws = AffiliateWithdrawRequest::where('trangthai', 'pending')
            ->selectRaw('id_affiliate_khachhang, SUM(so_tien) as total')
            ->groupBy('id_affiliate_khachhang')
            ->pluck('total', 'id_affiliate_khachhang');

        $profiles->each(function ($profile) use ($referralCounts, $pendingWithdraws) {
            $profile->referrals_count = (int) ($referralCounts[$profile->id_khachhang] ?? 0);
            $profile->pending_withdraw_amount = (float) ($pendingWithdraws[$profile->id_khachhang] ?? 0);
            $profile->available_balance = max(0, (float) $profile->tong_thu_nhap - (float) $profile->tong_da_thanh_toan - (float) $profile->pending_withdraw_amount);
        });

        $commissions = AffiliateCommission::with([
            'affiliateUser:id,ten,email',
            'referredUser:id,ten,email',
            'order:id_dathang,tongtien,trangthai',
        ])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'profiles' => $profiles,
            'commissions' => $commissions,
            'withdraw_requests' => AffiliateWithdrawRequest::with('affiliateUser:id,ten,email')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function updateProfile(Request $request, $id)
    {
        $request->validate([
            'status' => 'nullable|in:pending,active,suspended,rejected',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $profile = AffiliateProfile::findOrFail($id);

        if ($request->has('status')) {
            $profile->status = $request->status;
        }

        if ($request->has('commission_rate')) {
            $profile->commission_rate = $request->commission_rate;
        }

        $profile->save();
        $profile->load('user:id,ten,email');

        return response()->json([
            'message' => 'Cap nhat publisher thanh cong.',
            'profile' => $profile,
        ]);
    }

    public function updateCommissionStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,paid,cancelled',
            'note' => 'nullable|string|max:500',
        ]);

        $commission = AffiliateCommission::findOrFail($id);
        $commission->status = $request->status;
        $commission->note = $request->note;

        if ($request->status === 'approved') {
            $commission->approved_at = now();
        }
        if ($request->status === 'paid') {
            $commission->paid_at = now();
        }
        if ($request->status === 'cancelled') {
            $commission->approved_at = null;
            $commission->paid_at = null;
        }

        $commission->save();

        $profile = AffiliateProfile::where('id_khachhang', $commission->id_affiliate_khachhang)->first();
        if ($profile) {
            $profile->tong_thu_nhap = (float) AffiliateCommission::where('id_affiliate_khachhang', $commission->id_affiliate_khachhang)
                ->whereIn('trangthai', ['approved', 'paid'])
                ->sum('so_tien');

            $profile->tong_da_thanh_toan = (float) AffiliateCommission::where('id_affiliate_khachhang', $commission->id_affiliate_khachhang)
                ->where('trangthai', 'paid')
                ->sum('so_tien');

            $profile->save();
        }

        return response()->json([
            'message' => 'Cap nhat trang thai hoa hong thanh cong.',
            'commission' => $commission,
        ]);
    }

    public function updateWithdrawStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,paid',
            'note' => 'nullable|string|max:500',
        ]);

        $row = AffiliateWithdrawRequest::findOrFail($id);
        $row->status = $request->status;
        $row->note = $request->note;

        if ($request->status === 'approved') {
            $row->approved_at = now();
        }
        if ($request->status === 'paid') {
            $row->approved_at = $row->approved_at ?: now();
            $row->paid_at = now();
        }
        if ($request->status === 'rejected' || $request->status === 'pending') {
            $row->paid_at = null;
            if ($request->status === 'pending') {
                $row->approved_at = null;
            }
        }

        $row->save();

        $profile = AffiliateProfile::where('id_khachhang', $row->id_affiliate_khachhang)->first();
        if ($profile) {
            $profile->tong_da_thanh_toan = (float) AffiliateWithdrawRequest::where('id_affiliate_khachhang', $row->id_affiliate_khachhang)
                ->where('trangthai', 'paid')
                ->sum('so_tien');
            $profile->save();
        }

        return response()->json([
            'message' => 'Cap nhat trang thai rut tien thanh cong.',
            'withdraw_request' => $row,
        ]);
    }
}
