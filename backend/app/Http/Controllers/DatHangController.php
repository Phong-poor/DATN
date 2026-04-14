<?php

namespace App\Http\Controllers;

use App\Models\DatHang;
use App\Models\DatHangChiTiet;
use App\Models\GioHang;
use App\Models\BienThe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderSuccessMail;
use App\Events\OrderStatusUpdated;
use App\Events\NewOrderPlaced;


class DatHangController extends Controller
{
    public function cancelOrder(Request $request, $id)
    {
        $userId = Auth::id();
        $order = DatHang::with('chiTiets.bienThe')
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

            // 1. Update status and reason
            $order->update([
                'trangthai' => 'cancelled',
                'lydo' => $request->lydo ?? 'Người dùng hủy đơn'
            ]);

            // 2. Return stock
            foreach ($order->chiTiets as $chiTiet) {
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
        $order = DatHang::with('chiTiets.bienThe')
            ->where('id_dathang', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            //  Xóa lý do hủy khi mua lại
            $order->update(['lydo' => null]);
            $addedItemsCount = 0;
            $skippedItems = [];

            foreach ($order->chiTiets as $chiTiet) {
                $bienThe = $chiTiet->bienThe;

                if (!$bienThe || $bienThe->soluong <= 0) {
                    $skippedItems[] = $bienThe ? $bienThe->ten_bienthe : 'Sản phẩm không còn tồn tại';
                    continue;
                }

                // Check if already in cart
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


        $tongTien = 0;
        foreach ($gioHangItems as $item) {
            $tongTien += $item->soluong * $item->bienThe->gia;
        }

        try {
            DB::beginTransaction();

           
            $donHang = DatHang::create([
                'user_id'   => $userId,
                'tongtien'  => $tongTien,
                'trangthai' => 'pending',
                'diachi'    => $request->diachi,
                'PTTT'      => $request->PTTT,
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

            DB::commit();

            // Broadcast to Admin for new order
            event(new NewOrderPlaced($donHang));

            // Nếu là ví điện tử, tạo link thanh toán VNPay
            $payUrl = null;
            if ($request->PTTT === 'Ví điện tử') {
                $vnpay = new VnpayController();
                $payUrl = $vnpay->createPaymentUrl($donHang);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công!',
                'order'   => $donHang,
                'payUrl'  => $payUrl
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
            $order = DatHang::with(['chiTiets.bienThe.sanPham', 'user'])->findOrFail($id);
            
            // Bảo mật: Chỉ người mua mới có quyền kích hoạt gửi mail cho đơn hàng của mình
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
        $orders = DatHang::with(['chiTiets.bienThe.sanPham'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Map để thêm trạng thái đã đánh giá
        $orders->each(function($order) use ($userId) {
            $order->chiTiets->each(function($chiTiet) use ($order, $userId) {
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
        $orders = DatHang::with(['user', 'chiTiets.bienThe.sanPham'])
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

        $order = DatHang::with('chiTiets.bienThe')->findOrFail($id);
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

            // Handle Stock Sync
            // 1. If changing TO cancelled -> Restock
            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                foreach ($order->chiTiets as $chiTiet) {
                    if ($chiTiet->bienThe) {
                        $chiTiet->bienThe->increment('soluong', $chiTiet->soluong);
                    }
                }
            }

            // 2. If changing FROM cancelled back to something else -> Deduct stock again
            if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                foreach ($order->chiTiets as $chiTiet) {
                    if ($chiTiet->bienThe) {
                        // Check if enough stock
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
