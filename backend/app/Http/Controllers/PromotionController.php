<?php

namespace App\Http\Controllers;

use App\Mail\NewVoucherMail;
use App\Models\DatHang;
use App\Models\NewsletterSubscriber;
use App\Models\Promotion;
use App\Models\UserVoucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Quản lý mã khuyến mãi, kiểm tra điều kiện áp dụng và voucher của người dùng.
 */
class PromotionController extends Controller
{
    private function validateEventCode(Request $request): void
    {
        if ($request->input('danhmuc') !== 'event') {
            return;
        }

        $eventDate = trim((string) $request->input('ngay_su_kien'));
        if (! preg_match('/^(\d{2})-(\d{2})$/', $eventDate, $matches)
            || ! checkdate((int) $matches[2], (int) $matches[1], 2000)) {
            throw ValidationException::withMessages([
                'ngay_su_kien' => 'Ngày sự kiện phải hợp lệ theo định dạng DD-MM, ví dụ 02-09.',
            ]);
        }
        if (! preg_match('/^[A-Z0-9_-]+$/', strtoupper(trim((string) $request->input('code'))))) {
            throw ValidationException::withMessages(['code' => 'Mã KM phải là chữ in hoa không dấu, ví dụ QUOCKHANH.']);
        }
    }

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
        // Chỉ ẩn các mã người dùng ĐÃ có và CHƯA sử dụng (trang_thai = 0)
        // Nếu đã dùng hoặc hết hạn thì vẫn cho phép nhận lại khi sự kiện quay vòng
        $claimedIds = UserVoucher::where('id_user', $userId)
            ->where('trang_thai', 0)
            ->pluck('id_voucher');
        $today = now()->format('d-m');

        $available = Promotion::where('congkhai', 1)
            ->where('danhmuc', '!=', 'birthday')
            ->whereNotIn('id', $claimedIds)
            ->where(function($q) use ($today) {
                $q->where(function($sub) {
                    $sub->whereIn('trangthai', ['running', 'open'])
                        ->where('danhmuc', '!=', 'event');
                })->orWhere(function($sub) use ($today) {
                    $sub->where('danhmuc', 'event')
                        ->where('ngay_su_kien', $today);
                });
            })
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($available);
    }

    // GET /api/promotions — public & admin
    public function index(Request $request)
    {
        if ($request->is('api/admin/*') || $request->is('admin/*')) {
            // Admin quản lý chung nhưng vẫn nhìn thấy rõ các chương trình sinh nhật.
            return response()->json(Promotion::orderBy('id', 'desc')->get());
        }
        
        $today = now()->format('d-m');
        $query = Promotion::query();
        // Public chỉ thấy is_public = 1
        return response()->json($query->where('congkhai', 1)
            ->where('danhmuc', '!=', 'birthday')
            ->where(function($q) use ($today) {
                $q->where(function($sub) {
                    $sub->whereIn('trangthai', ['running', 'open'])
                        ->where('danhmuc', '!=', 'event');
                })->orWhere(function($sub) use ($today) {
                    $sub->where('danhmuc', 'event')
                        ->where('ngay_su_kien', $today);
                });
            })->get()
        );
    }

    // POST /api/apply-promo — public, kiểm tra mã giảm giá
    public function applyPromo(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $promo = Promotion::where('code', strtoupper($request->code))->first();

        if (! $promo) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không tồn tại.'
            ], 422);
        }

        if ($promo->danhmuc === 'event') {
            if ($promo->ngay_su_kien !== now()->format('d-m')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá sự kiện này chưa đến ngày sử dụng hoặc đã hết hạn.'
                ], 422);
            }
        } else {
            if (!in_array($promo->trangthai, ['running', 'open'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá đã hết hiệu lực.'
                ], 422);
            }
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
        if ($promo->danhmuc !== 'event' && $promo->ngayketthuc && now()->gt($promo->ngayketthuc)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá đã hết hạn.',
            ], 422);
        }

        // Kiểm tra ngày bắt đầu
        if ($promo->danhmuc !== 'event' && $promo->ngaybatdau && now()->lt($promo->ngaybatdau)) {
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
        if ($promo->danhmuc !== 'event' && $promo->ngayketthuc && \Carbon\Carbon::parse($promo->ngayketthuc)->isPast()) {
            return response()->json(['success' => false, 'message' => 'Voucher đã hết hạn.'], 400);
        }

        if ($promo->danhmuc !== 'event' && $promo->ngaybatdau && \Carbon\Carbon::parse($promo->ngaybatdau)->isFuture()) {
            return response()->json(['success' => false, 'message' => 'Voucher chưa tới thời gian nhận.'], 400);
        }

        if ($promo->danhmuc === 'event' && $promo->ngay_su_kien !== now()->format('d-m')) {
            return response()->json(['success' => false, 'message' => 'Chưa đến ngày nhận mã sự kiện này hoặc đã qua ngày nhận.'], 400);
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
        $this->validateEventCode($request);

        $request->validate([
            'ten'            => 'required|string|max:255',
            'danhmuc'        => 'required|string|in:product,birthday,freeship,event',
            'code'           => 'required|string|max:50|unique:vouchers,code',
            'loai'           => 'required|in:percent,fixed,maxprice',
            'giatri'         => 'required|numeric|min:0',
            'ngaybatdau'     => 'nullable|date',
            'ngayketthuc'    => 'nullable|date',
            'loai_dieu_kien' => 'nullable|string|max:5',
            'dieu_kien' => 'nullable|numeric|min:0',
            'congkhai' => 'boolean',
            'dieu_kien_tang' => 'nullable|numeric|min:0',
            'so_luong_phat' => 'nullable|integer|min:1',
            'tu_dong_gui' => 'nullable|boolean',
        ]);

        $promo = Promotion::create([
            'ten'            => $request->ten,
            'danhmuc'        => $request->danhmuc,
            'code'           => strtoupper($request->code),
            'ngay_su_kien'   => $request->danhmuc === 'event' ? $request->ngay_su_kien : null,
            'tu_dong_gui'    => $request->danhmuc === 'event' ? $request->boolean('tu_dong_gui', true) : false,
            'loai'           => $request->danhmuc === 'birthday' ? 'percent' : $request->loai,
            'giatri'         => $request->giatri,
            'ngaybatdau'     => $request->danhmuc === 'event' ? null : $request->ngaybatdau,
            'ngayketthuc'    => $request->danhmuc === 'event' ? null : $request->ngayketthuc,
            'trangthai'      => $request->danhmuc === 'event' ? 'open' : ($request->trangthai ?? 'open'),
            'mota'           => $request->mota,
            'loai_dieu_kien' => $request->danhmuc === 'product' ? $request->loai_dieu_kien : null,
            'dieu_kien' => in_array($request->danhmuc, ['product', 'freeship']) ? $request->dieu_kien : null,
            'congkhai' => $request->danhmuc === 'birthday' ? 0 : ($request->has('congkhai') ? $request->congkhai : 1),
            'dieu_kien_tang' => $request->danhmuc === 'birthday' ? null : $request->dieu_kien_tang,
            'so_luong_phat' => $request->danhmuc === 'birthday' ? null : $request->so_luong_phat,
        ]);

        // Gửi email thông báo voucher mới cho newsletter subscribers (chỉ với voucher công khai)
        if ($promo->congkhai) {
            $this->broadcastNewVoucher($promo);
        }

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

        $this->validateEventCode($request);

        $request->validate([
            'ten'            => 'required|string|max:255',
            'danhmuc'        => 'required|string|in:product,birthday,freeship,event',
            'code'           => 'required|string|max:50|unique:vouchers,code,' . $id,
            'loai'           => 'required|in:percent,fixed,maxprice',
            'giatri'         => 'required|numeric|min:0',
            'ngaybatdau'     => 'nullable|date',
            'ngayketthuc'    => 'nullable|date',
            'loai_dieu_kien' => 'nullable|string|max:5',
            'dieu_kien' => 'nullable|numeric|min:0',
            'congkhai' => 'boolean',
            'dieu_kien_tang' => 'nullable|numeric|min:0',
            'so_luong_phat' => 'nullable|integer|min:1',
            'tu_dong_gui' => 'nullable|boolean',
        ]);

        $promo->update([
            'ten'            => $request->ten,
            'danhmuc'        => $request->danhmuc,
            'code'           => strtoupper($request->code),
            'ngay_su_kien'   => $request->danhmuc === 'event' ? $request->ngay_su_kien : null,
            'tu_dong_gui'    => $request->danhmuc === 'event' ? $request->boolean('tu_dong_gui', true) : false,
            'loai'           => $request->danhmuc === 'birthday' ? 'percent' : $request->loai,
            'giatri'         => $request->giatri,
            'ngaybatdau'     => $request->danhmuc === 'event' ? null : $request->ngaybatdau,
            'ngayketthuc'    => $request->danhmuc === 'event' ? null : $request->ngayketthuc,
            'trangthai'      => $request->danhmuc === 'event' ? 'open' : ($request->trangthai ?? $promo->trangthai),
            'mota'           => $request->mota,
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

    public function updateAutoSend(Request $request, $id)
    {
        $request->validate(['tu_dong_gui' => 'required|boolean']);

        $promo = Promotion::where('danhmuc', 'event')->findOrFail($id);
        $promo->update(['tu_dong_gui' => $request->boolean('tu_dong_gui')]);

        return response()->json([
            'success' => true,
            'message' => $promo->tu_dong_gui ? 'Đã bật tự động gửi Gmail.' : 'Đã tắt tự động gửi Gmail.',
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

    /**
     * Gửi email thông báo voucher mới đến tất cả newsletter subscribers.
     */
    private function broadcastNewVoucher(Promotion $promo): void
    {
        try {
            $subscribers = NewsletterSubscriber::active()->pluck('email');
            foreach ($subscribers as $email) {
                Mail::to($email)->send(new NewVoucherMail($promo, $email));
            }
        } catch (\Throwable $e) {
            \Log::warning('Newsletter broadcast (voucher) failed: ' . $e->getMessage());
        }
    }
}

