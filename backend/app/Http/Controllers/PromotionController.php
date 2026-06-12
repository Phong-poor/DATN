<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Models\UserVoucher;
use App\Models\DatHang;

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
            ->where('is_public', 1) // Chỉ trả về voucher công khai
            ->where('category', '!=', 'birthday') // Không trả về mã sinh nhật
            ->whereNotIn('id', $claimedIds)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($available);
    }


    // GET /api/promotions — public & admin
    public function index(Request $request)
    {
        // Ẩn mã sinh nhật trong mọi trường hợp vì có mục đích riêng
        $query = Promotion::where('category', '!=', 'birthday')->orderBy('id', 'desc');

        if ($request->is('api/admin/*')) {
            // Admin thấy tất cả (trừ birthday), bao gồm cả is_public = 0
            return response()->json($query->get());
        }
        
        // Public chỉ thấy is_public = 1
        return response()->json($query->where('is_public', 1)->get());
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

        if ($promo->category === 'birthday') {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để sử dụng mã sinh nhật.'
                ], 401);
            }
            $hasVoucher = UserVoucher::where('id_user', $user->id)
                ->where('id_promotion', $promo->id)
                ->where('trang_thai', 0)
                ->exists();
            if (!$hasVoucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không sở hữu mã sinh nhật này hoặc mã đã được sử dụng.'
                ], 422);
            }
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

        $subtotal = $request->subtotal;

        // Kiểm tra điều kiện đơn hàng tối thiểu (cho cả product và freeship)
        if ($promo->dieu_kien && $promo->dieu_kien > 0) {
            if ($subtotal < $promo->dieu_kien) {
                $type = $promo->category === 'freeship' ? 'miễn phí vận chuyển' : 'này';
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($promo->dieu_kien, 0, ',', '.') . 'đ để sử dụng mã ' . $type . '.'
                ], 422);
            }
        }

        // Tính số tiền giảm
        $discount = 0;

        if ($promo->category === 'freeship') {
            // Freeship: không giảm giá sản phẩm, giảm phí vận chuyển (frontend tự xử lý)
            $message = "Áp dụng mã {$promo->code} – Miễn phí vận chuyển!";
        } elseif ($promo->type === 'percent') {
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

    // POST /api/user/vouchers/claim
    public function claimVoucher(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'id_promotion' => 'required|exists:promotions,id'
        ]);

        $promo = Promotion::find($request->id_promotion);

        if (!$promo) {
            return response()->json(['success' => false, 'message' => 'Voucher không tồn tại.'], 404);
        }

        if ($promo->is_public == 0) {
            return response()->json(['success' => false, 'message' => 'Không thể nhận voucher này.'], 403);
        }

        if ($promo->category === 'birthday') {
            return response()->json(['success' => false, 'message' => 'Không thể nhận mã sinh nhật.'], 403);
        }

        // Check date
        if ($promo->end_date && \Carbon\Carbon::parse($promo->end_date)->isPast()) {
            return response()->json(['success' => false, 'message' => 'Voucher đã hết hạn.'], 400);
        }

        if ($promo->start_date && \Carbon\Carbon::parse($promo->start_date)->isFuture()) {
            return response()->json(['success' => false, 'message' => 'Voucher chưa tới thời gian nhận.'], 400);
        }

        // Check so_luong_phat
        if ($promo->so_luong_phat > 0) {
            $claimedCount = UserVoucher::where('id_promotion', $promo->id)->count();
            if ($claimedCount >= $promo->so_luong_phat) {
                return response()->json(['success' => false, 'message' => 'Voucher đã hết lượt phát.'], 400);
            }
        }

        // Check if user already owns it
        $existing = UserVoucher::where('id_user', $user->id)
                               ->where('id_promotion', $promo->id)
                               ->first();

        if ($existing) {
            if ($existing->trang_thai == 2 || $existing->trang_thai === 'het_han' || $existing->trang_thai === 'expired') {
                $existing->update([
                    'trang_thai' => 0,
                    'ngay_nhan' => now()
                ]);
                return response()->json(['success' => true, 'message' => 'Đã nhận lại voucher thành công.']);
            } else {
                return response()->json(['success' => false, 'message' => 'Bạn đã có voucher này rồi.'], 400);
            }
        }

        // Create
        UserVoucher::create([
            'id_user' => $user->id,
            'id_promotion' => $promo->id,
            'trang_thai' => 0,
            'ngay_nhan' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Nhận voucher thành công.']);
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
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'loai_dieu_kien' => 'nullable|string|max:5',
            'dieu_kien'      => 'nullable|numeric|min:0',
            'is_public'      => 'boolean',
            'dieu_kien_tang' => 'nullable|numeric|min:0',
            'so_luong_phat'  => 'nullable|integer|min:1',
        ]);

        $promo = Promotion::create([
            'name'           => $request->name,
            'category'       => $request->category,
            'code'           => strtoupper($request->code),
            'type'           => $request->type,
            'value'          => $request->value,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'status'         => $request->status ?? 'open',
            'mota'           => $request->mota,
            'loai_dieu_kien' => $request->category === 'product' ? $request->loai_dieu_kien : null,
            'dieu_kien'      => in_array($request->category, ['product', 'freeship']) ? $request->dieu_kien : null,
            'is_public'      => $request->has('is_public') ? $request->is_public : 1,
            'dieu_kien_tang' => $request->dieu_kien_tang,
            'so_luong_phat'  => $request->so_luong_phat,
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
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'loai_dieu_kien' => 'nullable|string|max:5',
            'dieu_kien'      => 'nullable|numeric|min:0',
            'is_public'      => 'boolean',
            'dieu_kien_tang' => 'nullable|numeric|min:0',
            'so_luong_phat'  => 'nullable|integer|min:1',
        ]);

        $promo->update([
            'name'           => $request->name,
            'category'       => $request->category,
            'code'           => strtoupper($request->code),
            'type'           => $request->type,
            'value'          => $request->value,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'status'         => $request->status ?? $promo->status,
            'mota'           => $request->mota,
            'loai_dieu_kien' => $request->category === 'product' ? $request->loai_dieu_kien : null,
            'dieu_kien'      => in_array($request->category, ['product', 'freeship']) ? $request->dieu_kien : null,
            'is_public'      => $request->has('is_public') ? $request->is_public : 1,
            'dieu_kien_tang' => $request->dieu_kien_tang,
            'so_luong_phat'  => $request->so_luong_phat,
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
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Xóa các bản ghi liên quan trong bảng users_voucher trước để tránh lỗi khóa ngoại
            UserVoucher::where('id_promotion', $id)->delete();

            // 2. Cập nhật các đơn hàng sử dụng mã này thành null để tránh lỗi khóa ngoại mà vẫn giữ được đơn hàng
            \App\Models\DatHang::where('promotion_id', $id)->update(['promotion_id' => null]);

            // 3. Xóa khuyến mãi
            Promotion::destroy($id);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa khuyến mãi thành công!'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa khuyến mãi: ' . $e->getMessage()
            ], 500);
        }
    }
}