<?php

namespace App\Http\Controllers;

use App\Models\DatHang;
use App\Models\DatHangChiTiet;
use App\Models\GioHang;
use App\Models\BienThe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DatHangController extends Controller
{
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

                // Trừ tồn kho
                $item->bienThe->decrement('soluong', $item->soluong);
            }

            // 5. Xóa giỏ hàng
            GioHang::where('user_id', $userId)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công!',
                'order'   => $donHang
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    public function orders()
    {
        $userId = Auth::id();
        $orders = DatHang::with('chiTiets.bienThe.sanPham')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

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

        $order = DatHang::findOrFail($id);
        $order->update(['trangthai' => $request->trangthai]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!',
            'order'   => $order
        ]);
    }
}
