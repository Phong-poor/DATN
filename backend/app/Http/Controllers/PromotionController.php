<?php

namespace App\Http\Controllers;

use App\Models\DatHang;
use App\Models\Promotion;
use App\Models\UserVoucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Quản lý mã khuyến mãi, kiểm tra điều kiện áp dụng và voucher của người dùng.
 */
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
            'vouchers' => $vouchers,
        ]);
    }

    /**
     * GET /api/user/vouchers/available
     */
    public function availableGifts(Request $request)
    {
        $userId = $request->user()->id;
        $claimedIds = UserVoucher::where('id_user', $userId)->pluck('id_voucher');

        $available = Promotion::whereIn('trangthai', ['running', 'open'])
            ->where('congkhai', 1) // Chỉ trả về voucher công khai
            ->where('danhmuc', '!=', 'birthday') // Không trả về mã sinh nhật
            ->whereNotIn('id', $claimedIds)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($available);
    }

    // GET /api/promotions — public & admin
    public function index(Request $request)
    {
        if ($request->is('api/admin/*')) {
            // Admin quản lý chung nhưng vẫn nhìn thấy rõ các chương trình sinh nhật.
            return response()->json(Promotion::orderBy('id', 'desc')->get());
        }

        // Mã sinh nhật là quyền lợi riêng được cấp qua email, tuyệt đối không công khai.
        return response()->json(
            Promotion::where('danhmuc', '!=', 'birthday')
                ->where('congkhai', 1)
                ->orderBy('id', 'desc')
                ->get()
        );
    }

    // POST /api/apply-promo — public, kiểm tra mã giảm giá
    public function applyPromo(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $promo = Promotion::where('code', strtoupper($request->code))
            ->whereIn('trangthai', ['running', 'open'])
            ->first();

        if (! $promo) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không tồn tại hoặc đã hết hiệu lực.',
            ], 422);
        }

        if ($promo->danhmuc === 'birthday') {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để sử dụng mã sinh nhật.',
                ], 401);
            }
            $hasVoucher = UserVoucher::where('id_user', $user->id)
                ->where('id_voucher', $promo->id)
                ->where('trang_thai', 0)
                ->whereNull('da_su_dung_luc')
                ->where(function ($query) {
                    $query->whereNull('het_han_luc')->orWhere('het_han_luc', '>=', now());
                })
                ->exists();
            if (! $hasVoucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không sở hữu mã sinh nhật này hoặc mã đã được sử dụng.',
                ], 422);
            }
        }

        // Kiểm tra ngày hết hạn
        if ($promo->ngayketthuc && now()->gt($promo->ngayketthuc)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá đã hết hạn.',
            ], 422);
        }

        // Kiểm tra ngày bắt đầu
        if ($promo->ngaybatdau && now()->lt($promo->ngaybatdau)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá chưa có hiệu lực.',
            ], 422);
        }

        $subtotal = $request->subtotal;

        // Kiểm tra điều kiện đơn hàng tối thiểu (cho cả product và freeship)
        if ($promo->dieu_kien && $promo->dieu_kien > 0) {
            if ($subtotal < $promo->dieu_kien) {
                $type = $promo->category === 'freeship' ? 'miễn phí vận chuyển' : 'này';

                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng chưa đạt giá trị tối thiểu '.number_format($promo->dieu_kien, 0, ',', '.').'đ để sử dụng mã '.$type.'.',
                ], 422);
            }
        }

        // Tính số tiền giảm
        $discount = 0;

        if ($promo->danhmuc === 'freeship') {
            // Freeship: không giảm giá sản phẩm, giảm phí vận chuyển (frontend tự xử lý)
            $message = "Áp dụng mã {$promo->code} – Miễn phí vận chuyển!";
        } elseif ($promo->loai === 'percent') {
            $discount = round($subtotal * $promo->giatri / 100);
            $message = "Áp dụng mã {$promo->code} – giảm {$promo->giatri}%!";
        } elseif ($promo->loai === 'fixed') {
            $discount = min($promo->giatri, $subtotal);
            $message = "Áp dụng mã {$promo->code} – giảm ".number_format($promo->giatri, 0, ',', '.').'đ!';
        } else {
            $message = "Áp dụng mã {$promo->code} thành công!";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'discount' => $discount,
            'promotion' => $promo,
        ]);
    }

    // POST /api/user/vouchers/claim
    public function claimVoucher(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'id_voucher' => 'required|exists:vouchers,id',
        ]);

        $promo = Promotion::find($request->id_voucher);

        if (! $promo) {
            return response()->json(['success' => false, 'message' => 'Voucher không tồn tại.'], 404);
        }

        if ($promo->congkhai == 0) {
            return response()->json(['success' => false, 'message' => 'Không thể nhận voucher này.'], 403);
        }

        if ($promo->danhmuc === 'birthday') {
            return response()->json(['success' => false, 'message' => 'Không thể nhận mã sinh nhật.'], 403);
        }

        // Check date
        if ($promo->ngayketthuc && Carbon::parse($promo->ngayketthuc)->isPast()) {
            return response()->json(['success' => false, 'message' => 'Voucher đã hết hạn.'], 400);
        }

        if ($promo->ngaybatdau && Carbon::parse($promo->ngaybatdau)->isFuture()) {
            return response()->json(['success' => false, 'message' => 'Voucher chưa tới thời gian nhận.'], 400);
        }

        // Check so_luong_phat
        if ($promo->so_luong_phat > 0) {
            $claimedCount = UserVoucher::where('id_voucher', $promo->id)->count();
            if ($claimedCount >= $promo->so_luong_phat) {
                return response()->json(['success' => false, 'message' => 'Voucher đã hết lượt phát.'], 400);
            }
        }

        // Check if user already owns it
        $existing = UserVoucher::where('id_user', $user->id)
            ->where('id_voucher', $promo->id)
            ->first();

        if ($existing) {
            if ($existing->trang_thai == 2 || $existing->trang_thai === 'het_han' || $existing->trang_thai === 'expired') {
                $existing->update([
                    'trang_thai' => 0,
                    'ngay_nhan' => now(),
                ]);

                return response()->json(['success' => true, 'message' => 'Đã nhận lại voucher thành công.']);
            } else {
                return response()->json(['success' => false, 'message' => 'Bạn đã có voucher này rồi.'], 400);
            }
        }

        // Create
        UserVoucher::create([
            'id_user' => $user->id,
            'id_voucher' => $promo->id,
            'trang_thai' => 0,
            'ngay_nhan' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Nhận voucher thành công.']);
    }

    // POST /api/admin/promotions
    public function store(Request $request)
    {
        $request->validate([
            'ten' => 'required|string|max:255',
            'danhmuc' => 'required|string|in:product,birthday,freeship',
            'code' => 'required|string|max:50|unique:vouchers,code',
            'loai' => 'required|in:percent,fixed,maxprice',
            'giatri' => 'required|numeric|min:0',
            'ngaybatdau' => 'nullable|date',
            'ngayketthuc' => 'nullable|date',
            'loai_dieu_kien' => 'nullable|string|max:5',
            'dieu_kien' => 'nullable|numeric|min:0',
            'congkhai' => 'boolean',
            'dieu_kien_tang' => 'nullable|numeric|min:0',
            'so_luong_phat' => 'nullable|integer|min:1',
        ]);

        $promo = Promotion::create([
            'ten' => $request->ten,
            'danhmuc' => $request->danhmuc,
            'code' => strtoupper($request->code),
            'loai' => $request->loai,
            'giatri' => $request->giatri,
            'ngaybatdau' => $request->ngaybatdau,
            'ngayketthuc' => $request->ngayketthuc,
            'trangthai' => $request->trangthai ?? 'open',
            'mota' => $request->mota,
            'loai_dieu_kien' => $request->danhmuc === 'product' ? $request->loai_dieu_kien : null,
            'dieu_kien' => in_array($request->danhmuc, ['product', 'freeship']) ? $request->dieu_kien : null,
            'congkhai' => $request->danhmuc === 'birthday' ? 0 : ($request->has('congkhai') ? $request->congkhai : 1),
            'dieu_kien_tang' => $request->danhmuc === 'birthday' ? null : $request->dieu_kien_tang,
            'so_luong_phat' => $request->danhmuc === 'birthday' ? null : $request->so_luong_phat,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo khuyến mãi thành công!',
            'promotion' => $promo,
        ]);
    }

    // PUT /api/admin/promotions/{id}
    public function update(Request $request, $id)
    {
        $promo = Promotion::findOrFail($id);

        $request->validate([
            'ten' => 'required|string|max:255',
            'danhmuc' => 'required|string|in:product,birthday,freeship',
            'code' => 'required|string|max:50|unique:vouchers,code,'.$id,
            'loai' => 'required|in:percent,fixed,maxprice',
            'giatri' => 'required|numeric|min:0',
            'ngaybatdau' => 'nullable|date',
            'ngayketthuc' => 'nullable|date',
            'loai_dieu_kien' => 'nullable|string|max:5',
            'dieu_kien' => 'nullable|numeric|min:0',
            'congkhai' => 'boolean',
            'dieu_kien_tang' => 'nullable|numeric|min:0',
            'so_luong_phat' => 'nullable|integer|min:1',
        ]);

        $promo->update([
            'ten' => $request->ten,
            'danhmuc' => $request->danhmuc,
            'code' => strtoupper($request->code),
            'loai' => $request->loai,
            'giatri' => $request->giatri,
            'ngaybatdau' => $request->ngaybatdau,
            'ngayketthuc' => $request->ngayketthuc,
            'trangthai' => $request->trangthai ?? $promo->trangthai,
            'mota' => $request->mota,
            'loai_dieu_kien' => $request->danhmuc === 'product' ? $request->loai_dieu_kien : null,
            'dieu_kien' => in_array($request->danhmuc, ['product', 'freeship']) ? $request->dieu_kien : null,
            'congkhai' => $request->danhmuc === 'birthday' ? 0 : ($request->has('congkhai') ? $request->congkhai : 1),
            'dieu_kien_tang' => $request->danhmuc === 'birthday' ? null : $request->dieu_kien_tang,
            'so_luong_phat' => $request->danhmuc === 'birthday' ? null : $request->so_luong_phat,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật khuyến mãi thành công!',
            'promotion' => $promo,
        ]);
    }

    // DELETE /api/admin/promotions/{id}
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // 1. Xóa các bản ghi liên quan trong bảng khachhang_voucher trước để tránh lỗi khóa ngoại
            UserVoucher::where('id_voucher', $id)->delete();

            // 2. Cập nhật các đơn hàng sử dụng mã này thành null để tránh lỗi khóa ngoại mà vẫn giữ được đơn hàng
            DatHang::where('id_khuyenmai', $id)->update(['id_khuyenmai' => null]);

            // 3. Xóa khuyến mãi
            Promotion::destroy($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa khuyến mãi thành công!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa khuyến mãi: '.$e->getMessage(),
            ], 500);
        }
    }
}
