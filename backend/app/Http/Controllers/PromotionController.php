<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Models\UserVoucher;

class PromotionController extends Controller
{
    // GET /api/user/vouchers — fetch vouchers owned by the user
    public function myVouchers(Request $request)
    {
        $vouchers = UserVoucher::with('promotion')
            ->where('id_user', $request->user()->id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'vouchers' => $vouchers
        ]);
    }

    /**
     * GET /api/user/vouchers/available
     */
    public function availableGifts(Request $request)
    {
        $userId = $request->user()->id;
        $claimedIds = UserVoucher::where('id_user', $userId)->pluck('id_promotion');

        $available = Promotion::whereIn('status', ['running', 'open'])
            ->whereNotIn('id', $claimedIds)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($available);
    }

    /**
     * POST /api/user/vouchers/claim
     */
    public function claimVoucher(Request $request)
    {
        $request->validate([
            'id_promotion' => 'required|exists:promotions,id'
        ]);

        $userId = $request->user()->id;
        $promoId = $request->id_promotion;

        $exists = UserVoucher::where('id_user', $userId)
            ->where('id_promotion', $promoId)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã nhận mã giảm giá này rồi.'
            ], 422);
        }

        $userVoucher = UserVoucher::create([
            'id_user'      => $userId,
            'id_promotion' => $promoId,
            'trang_thai'   => 0,
            'ngay_nhan'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu mã giảm giá vào hồ sơ của bạn!',
            'voucher' => $userVoucher
        ]);
    }

    // GET /api/promotions — public
    public function index()
    {
        return response()->json(Promotion::orderBy('id', 'desc')->get());
    }

    // POST /api/apply-promo — public, kiểm tra mã giảm giá
    public function applyPromo(Request $request)
    {
        $request->validate([
            'code'     => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $promo = Promotion::where('code', strtoupper($request->code))
            ->whereIn('status', ['running', 'open'])
            ->first();

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không tồn tại hoặc đã hết hiệu lực.'
            ], 422);
        }

        // Kiểm tra ngày hết hạn
        if ($promo->end_date && now()->gt($promo->end_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá đã hết hạn.'
            ], 422);
        }

        // Kiểm tra ngày bắt đầu
        if ($promo->start_date && now()->lt($promo->start_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá chưa có hiệu lực.'
            ], 422);
        }

        // Tính số tiền giảm
        $subtotal = $request->subtotal;
        $discount = 0;

        if ($promo->type === 'percent') {
            $discount = round($subtotal * $promo->value / 100);
            $message  = "Áp dụng mã {$promo->code} – giảm {$promo->value}%!";
        } elseif ($promo->type === 'fixed') {
            $discount = min($promo->value, $subtotal);
            $message  = "Áp dụng mã {$promo->code} – giảm " . number_format($promo->value, 0, ',', '.') . "đ!";
        } else {
            $message = "Áp dụng mã {$promo->code} thành công!";
        }

        return response()->json([
            'success'    => true,
            'message'    => $message,
            'discount'   => $discount,
            'promotion'  => $promo,
        ]);
    }

    // POST /api/admin/promotions
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'category'       => 'required|string|in:product,birthday,freeship',
            'code'           => 'required|string|max:50|unique:promotions,code',
            'type'           => 'required|in:percent,fixed,maxprice',
            'value'          => 'required|numeric|min:0',
            'loai_dieu_kien' => 'nullable|string|max:5',
            'dieu_kien'      => 'nullable|numeric|min:0',
        ]);

        $promo = Promotion::create([
            'name'           => $request->name,
            'category'       => $request->category,
            'code'           => strtoupper($request->code),
            'type'           => $request->type,
            'value'          => $request->value,
            'end_date'       => $request->end_date,
            'status'         => $request->status ?? 'open',
            'mota'           => $request->mota,
            'loai_dieu_kien' => $request->loai_dieu_kien,
            'dieu_kien'      => $request->dieu_kien,
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Tạo khuyến mãi thành công!',
            'promotion' => $promo,
        ]);
    }

    // PUT /api/admin/promotions/{id}
    public function update(Request $request, $id)
    {
        $promo = Promotion::findOrFail($id);

        $request->validate([
            'name'           => 'required|string|max:255',
            'category'       => 'required|string|in:product,birthday,freeship',
            'code'           => 'required|string|max:50|unique:promotions,code,' . $id,
            'type'           => 'required|in:percent,fixed,maxprice',
            'value'          => 'required|numeric|min:0',
            'loai_dieu_kien' => 'nullable|string|max:5',
            'dieu_kien'      => 'nullable|numeric|min:0',
        ]);

        $promo->update([
            'name'           => $request->name,
            'category'       => $request->category,
            'code'           => strtoupper($request->code),
            'type'           => $request->type,
            'value'          => $request->value,
            'end_date'       => $request->end_date,
            'status'         => $request->status ?? $promo->status,
            'mota'           => $request->mota,
            'loai_dieu_kien' => $request->loai_dieu_kien,
            'dieu_kien'      => $request->dieu_kien,
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Cập nhật khuyến mãi thành công!',
            'promotion' => $promo,
        ]);
    }

    // DELETE /api/admin/promotions/{id}
    public function destroy($id)
    {
        Promotion::destroy($id);
        return response()->json([
            'success' => true,
            'message' => 'Xóa khuyến mãi thành công!'
        ]);
    }
}