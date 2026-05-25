<?php

namespace App\Http\Controllers;

use App\Models\DatHang;
use App\Models\DatHangChiTiet;
use App\Models\GioHang;
use App\Models\BienThe;
use App\Models\DiaChi;
use App\Models\Promotion;
use App\Models\UserVoucher;
use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateReferral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\OrderSuccessMail;
use App\Events\OrderStatusUpdated;
use App\Events\OrderPlaced;


class DatHangController extends Controller
{
    public function cancelOrder(Request $request, $id)
    {
        $userId = Auth::id();
        $order = DatHang::with('chi_tiets.bienThe')
            ->where('id_dathang', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        if (!in_array($order->trangthai, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng ở trạng thái này.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $order->update([
                'trangthai' => 'cancelled',
                'lydo' => $request->lydo ?? 'Người dùng hủy đơn'
            ]);

            foreach ($order->chi_tiets as $chiTiet) {
                if ($chiTiet->bienThe) {
                    $chiTiet->bienThe->increment('soluong', $chiTiet->soluong);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hủy đơn hàng thành công!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reorder(Request $request, $id)
    {
        $userId = Auth::id();
        $order = DatHang::with('chi_tiets.bienThe')
            ->where('id_dathang', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $order->update(['lydo' => null]);
            $addedItemsCount = 0;
            $skippedItems = [];

            foreach ($order->chi_tiets as $chiTiet) {
                $bienThe = $chiTiet->bienThe;

                if (!$bienThe || $bienThe->soluong <= 0) {
                    $skippedItems[] = $bienThe ? $bienThe->ten_bienthe : 'Sản phẩm không còn tồn tại';
                    continue;
                }

                $cartItem = GioHang::where('user_id', $userId)
                    ->where('id_bienthe', $chiTiet->id_bienthe)
                    ->first();

                if ($cartItem) {
                    $cartItem->increment('soluong', $chiTiet->soluong);
                } else {
                    GioHang::create([
                        'user_id'    => $userId,
                        'id_bienthe' => $chiTiet->id_bienthe,
                        'soluong'    => $chiTiet->soluong,
                    ]);
                }
                $addedItemsCount++;
            }

            DB::commit();

            if ($addedItemsCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rất tiếc! Tất cả sản phẩm trong đơn hàng này đều đã hết hàng.',
                    'skipped' => $skippedItems
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã thêm $addedItemsCount sản phẩm vào giỏ hàng.",
                'skipped' => $skippedItems
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'id_diachi' => 'nullable|integer',
            'diachi' => 'required_without:id_diachi|string',
            'PTTT'   => 'required|string',
        ]);

        $userId = Auth::id();
        $diaChiGiaoHang = $request->diachi;

        if ($request->filled('id_diachi')) {
            $diaChi = DiaChi::where('id_user', $userId)
                ->where('id_diachi', $request->id_diachi)
                ->first();

            if (! $diaChi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn địa chỉ'
                ], 422);
            }

            $diaChiGiaoHang = $diaChi->dia_chi_day_du;
        }

        $gioHangItems = GioHang::with('bienThe')->where('user_id', $userId)->get();

        if ($gioHangItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Giỏ hàng của bạn đang trống.'
            ], 400);
        }

        // Tính tổng tiền gốc
        $tongTienGoc = 0;
        foreach ($gioHangItems as $item) {
            $tongTienGoc += $item->soluong * $item->bienThe->gia;
        }

        $shippingFee = 30000;

        // ── Xử lý mã giảm giá ──────────────────────────
        $giamGia = 0;
        $giamGiaShip = 0;
        $promoId = null;

        if ($request->filled('promo_code')) {
            $promo = Promotion::where('code', strtoupper($request->promo_code))
                ->whereIn('status', ['running', 'open'])
                ->first();

            if ($promo) {
                if ($promo->category === 'freeship') {
                    return response()->json(['success' => false, 'message' => 'Mã này là mã freeship, không áp dụng cho đơn hàng.'], 400);
                }
                if ($promo->end_date && now()->gt($promo->end_date)) {
                    return response()->json(['success' => false, 'message' => 'Mã giảm giá đã hết hạn.'], 400);
                }

                // Block if subtotal < conditions
                if ($promo->dieu_kien && $promo->dieu_kien > 0) {
                    if ($tongTienGoc < $promo->dieu_kien) {
                        return response()->json([
                            'success' => false, 
                            'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($promo->dieu_kien, 0, ',', '.') . 'đ để sử dụng mã này.'
                        ], 400);
                    }
                }

                if ($promo->type === 'percent') {
                    $giamGia = round($tongTienGoc * $promo->value / 100);
                } elseif ($promo->type === 'fixed') {
                    $giamGia = min($promo->value, $tongTienGoc);
                } elseif ($promo->type === 'maxprice') {
                    $giamGia = min($promo->value, $tongTienGoc);
                }

                $promoId = $promo->id;
            }
        }

        if ($request->filled('freeship_code')) {
            $fpromo = Promotion::where('code', strtoupper($request->freeship_code))
                ->whereIn('status', ['running', 'open'])
                ->first();

            if ($fpromo) {
                if ($fpromo->end_date && now()->gt($fpromo->end_date)) {
                    return response()->json(['success' => false, 'message' => 'Mã freeship đã hết hạn.'], 400);
                }

                if ($fpromo->dieu_kien && $fpromo->dieu_kien > 0) {
                    if ($tongTienGoc < $fpromo->dieu_kien) {
                        return response()->json([
                            'success' => false, 
                            'message' => 'Đơn hàng chưa đạt tối thiểu ' . number_format($fpromo->dieu_kien, 0, ',', '.') . 'đ để dùng mã miễn phí vận chuyển.'
                        ], 400);
                    }
                }

                if ($fpromo->category === 'freeship') {
                    $giamGiaShip = $shippingFee;
                } else {
                    return response()->json(['success' => false, 'message' => 'Mã này không phải mã freeship.'], 400);
                }
            }
        }

        $tongTienSauGiam = max(0, $tongTienGoc - $giamGia) + max(0, $shippingFee - $giamGiaShip);

        try {
            DB::beginTransaction();

            $donHang = DatHang::create([
                'user_id'     => $userId,
                'tongtien'    => $tongTienSauGiam,
                'trangthai'   => 'pending',
                'diachi'      => $diaChiGiaoHang,
                'PTTT'        => $request->PTTT,
                'giam_gia'    => $giamGia + $giamGiaShip,       // lưu số tiền đã giảm
                'promotion_id' => $promoId,      // lưu id promotion đã dùng
            ]);

            foreach ($gioHangItems as $item) {
                DatHangChiTiet::create([
                    'id_dathang' => $donHang->id_dathang,
                    'id_bienthe' => $item->id_bienthe,
                    'soluong'    => $item->soluong,
                    'gia'        => $item->bienThe->gia,
                ]);
            }

            GioHang::where('user_id', $userId)->delete();

            // Cập nhật trạng thái voucher trong hồ sơ user thành "Đã sử dụng"
            if ($promoId) {
                UserVoucher::where('id_user', $userId)
                    ->where('id_promotion', $promoId)
                    ->update(['trang_thai' => 1]);
            }

            // Nếu có dùng thêm mã freeship
            if (isset($fpromo) && $fpromo) {
                UserVoucher::where('id_user', $userId)
                    ->where('id_promotion', $fpromo->id)
                    ->update(['trang_thai' => 1]);
            }

            DB::commit();

            // Invalidate dashboard cache
            Cache::forget('dashboard_data_all');
            Cache::forget('dashboard_data_week');
            Cache::forget('dashboard_data_month');
            Cache::forget('dashboard_data_year');

            // Broadcast new order event
            broadcast(new OrderPlaced($donHang));

            $payUrl = null;
            if ($request->PTTT === 'Ví điện tử') {
                $vnpay = new VnpayController();
                $payUrl = $vnpay->createPaymentUrl($donHang);
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Đặt hàng thành công!',
                'order'     => $donHang,
                'payUrl'    => $payUrl,
                'giam_gia'  => $giamGia,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendSuccessEmail($id)
    {
        try {
            $order = DatHang::with(['chi_tiets.bienThe.sanPham', 'user'])->findOrFail($id);

            if ($order->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            Mail::to($order->user->email)->send(new \App\Mail\OrderSuccessMail($order, $order->user));

            return response()->json(['success' => true, 'message' => 'Email sent']);
        } catch (\Exception $e) {
            Log::error("Lỗi gửi mail thủ công: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function orders()
    {
        $userId = Auth::id();
        $orders = DatHang::with(['chi_tiets.bienThe.sanPham'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $orders->each(function($order) use ($userId) {
            $order->chi_tiets->each(function($chiTiet) use ($order, $userId) {
                $chiTiet->is_reviewed = \App\Models\DanhGia::where('id_dathang', $order->id_dathang)
                    ->where('id_bienthe', $chiTiet->id_bienthe)
                    ->where('user_id', $userId)
                    ->exists();
            });
        });

        return response()->json([
            'success' => true,
            'orders'  => $orders
        ]);
    }

    // ===== ADMIN METHODS =====

    public function allOrders()
    {
        $orders = DatHang::with(['user', 'chi_tiets.bienThe.sanPham'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders'  => $orders
        ]);
    }

    public function updateStatus(Request $request, $id) 
    {
        $request->validate([
            'trangthai' => 'required|string|in:pending,confirmed,shipping,done,cancelled'
        ]);

        $order = DatHang::with('chi_tiets.bienThe')->findOrFail($id);
        $oldStatus = $order->trangthai;
        $newStatus = $request->trangthai;

        if ($oldStatus === $newStatus) {
            return response()->json([
                'success' => true,
                'message' => 'Trạng thái không đổi.',
                'order'   => $order
            ]);
        }

        try {
            DB::beginTransaction();

            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                foreach ($order->chi_tiets as $chiTiet) {
                    if ($chiTiet->bienThe) {
                        $chiTiet->bienThe->increment('soluong', $chiTiet->soluong);
                    } 
                }
            }

            if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                foreach ($order->chi_tiets as $chiTiet) {
                    if ($chiTiet->bienThe) {
                        if ($chiTiet->bienThe->soluong < $chiTiet->soluong) {
                            throw new \Exception("Sản phẩm {$chiTiet->bienThe->ten_bienthe} không đủ hàng để khôi phục đơn hàng.");
                        }
                        $chiTiet->bienThe->decrement('soluong', $chiTiet->soluong);
                    }
                }
            }

            $updateData = ['trangthai' => $newStatus];
            if ($newStatus === 'cancelled' && $request->has('lydo')) {
                $updateData['lydo'] = $request->lydo;
            }
            $order->update($updateData);

            if ($newStatus === 'done' && $oldStatus !== 'done') {
                $this->createAffiliateCommissionForOrder($order);
            }

            if ($newStatus === 'cancelled') {
                AffiliateCommission::where('order_id', $order->id_dathang)->update([
                    'status' => 'cancelled',
                    'approved_at' => null,
                    'paid_at' => null,
                ]);
            }

            DB::commit();

            // Broadcast the status update
            event(new OrderStatusUpdated($order));


            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công!',
                'order'   => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $order = DatHang::with('chi_tiets.bienThe')->findOrFail($id);

        try {
            DB::beginTransaction();

            if (!in_array($order->trangthai, ['done', 'cancelled'])) {
                foreach ($order->chi_tiets as $chiTiet) {
                    if ($chiTiet->bienThe) {
                        $chiTiet->bienThe->increment('soluong', $chiTiet->soluong);
                    }
                }
            }

            $order->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Da xoa don hang thanh cong.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Khong the xoa don hang: ' . $e->getMessage()
            ], 500);
        }
    }

    private function createAffiliateCommissionForOrder(DatHang $order): void
    {
        $referral = AffiliateReferral::where('referred_user_id', $order->user_id)->first();
        if (!$referral) {
            return;
        }

        $profile = AffiliateProfile::where('user_id', $referral->affiliate_user_id)
            ->where('status', 'active')
            ->first();

        if (!$profile) {
            return;
        }

        $exists = AffiliateCommission::where('order_id', $order->id_dathang)->exists();
        if ($exists) {
            return;
        }

        $rate = (float) ($profile->commission_rate ?? 5);
        $amount = round(((float) $order->tongtien * $rate) / 100, 2);

        if ($amount <= 0) {
            return;
        }

        AffiliateCommission::create([
            'affiliate_user_id' => $profile->user_id,
            'referred_user_id' => $order->user_id,
            'order_id' => $order->id_dathang,
            'amount' => $amount,
            'status' => 'pending',
        ]);
    }
}
