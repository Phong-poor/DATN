<?php

namespace App\Http\Controllers;

use App\Models\DatHang;
use App\Models\DatHangChiTiet;
use App\Models\GioHang;
use App\Models\BienThe;
use App\Models\Promotion;
use App\Models\UserVoucher;
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
            'diachi' => 'required|string',
            'PTTT'   => 'required|string',
        ]);

        $userId = Auth::id();

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
                'diachi'      => $request->diachi,
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

                $item->bienThe->decrement('soluong', $item->soluong);
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
}
