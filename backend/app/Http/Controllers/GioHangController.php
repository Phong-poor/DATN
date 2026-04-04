<?php

namespace App\Http\Controllers;

use App\Models\GioHang;
use App\Models\BienThe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GioHangController extends Controller
{
   
    public function index()
    {
        $userId = Auth::id();

        $gioHang = GioHang::with(['bienThe.sanPham'])
            ->where('user_id', $userId)
            ->get()
            ->map(function ($item) {
                $bienThe = $item->bienThe;
                $sanPham = $bienThe?->sanPham;

                // Parse thuoc_tinh_json
                $thuocTinh = [];
                if ($bienThe && $bienThe->thuoc_tinh_json) {
                    $thuocTinh = is_string($bienThe->thuoc_tinh_json)
                        ? json_decode($bienThe->thuoc_tinh_json, true)
                        : $bienThe->thuoc_tinh_json;
                }

                return [
                    'id_giohang'   => $item->id_giohang,
                    'id_bienthe'   => $item->id_bienthe,
                    'soluong'      => $item->soluong,
                    'ten_bienthe'  => $bienThe?->ten_bienthe ?? '',
                    'gia'          => $bienThe?->gia ?? 0,
                    'ton_kho'      => $bienThe?->soluong ?? 0,
                    'thuoc_tinh'   => $thuocTinh,
                    'ten_san_pham' => $sanPham?->tenSP ?? '',
                    'hinh_anh'     => $sanPham?->hinhanh
                        ? asset('storage/' . $sanPham->hinhanh)
                        : null,
                    'thanh_tien'   => ($bienThe?->gia ?? 0) * $item->soluong,
                ];
            });

        $tongTien = $gioHang->sum('thanh_tien');

        return response()->json([
            'success'   => true,
            'gio_hang'  => $gioHang,
            'tong_tien' => $tongTien,
            'so_luong_san_pham' => $gioHang->count(),
        ]);
    }

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function them(Request $request)
    {
        $request->validate([
            'id_bienthe' => 'required|exists:bienthe,id_bienthe',
            'soluong'    => 'required|integer|min:1',
        ]);

        $userId    = Auth::id();
        $idBienThe = $request->id_bienthe;
        $soLuong   = $request->soluong;

        // Kiểm tra tồn kho
        $bienThe = BienThe::findOrFail($idBienThe);
        if ($bienThe->soluong < $soLuong) {
            return response()->json([
                'success' => false,
                'message' => "Chỉ còn {$bienThe->soluong} sản phẩm trong kho.",
            ], 422);
        }

        // Nếu sản phẩm đã có trong giỏ → cộng thêm số lượng
        $existing = GioHang::where('user_id', $userId)
            ->where('id_bienthe', $idBienThe)
            ->first();

        if ($existing) {
            $soLuongMoi = $existing->soluong + $soLuong;

            if ($soLuongMoi > $bienThe->soluong) {
                return response()->json([
                    'success' => false,
                    'message' => "Giỏ hàng đã có {$existing->soluong} sản phẩm. Kho chỉ còn {$bienThe->soluong}.",
                ], 422);
            }

            $existing->update(['soluong' => $soLuongMoi]);
            $item = $existing;
        } else {
            $item = GioHang::create([
                'user_id'    => $userId,
                'id_bienthe' => $idBienThe,
                'soluong'    => $soLuong,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng!',
            'item'    => $item,
        ], 201);
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     */
    public function capNhat(Request $request, $id)
    {
        $request->validate([
            'soluong' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();

        $item = GioHang::where('id_giohang', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Kiểm tra tồn kho
        $bienThe = BienThe::findOrFail($item->id_bienthe);
        if ($request->soluong > $bienThe->soluong) {
            return response()->json([
                'success' => false,
                'message' => "Kho chỉ còn {$bienThe->soluong} sản phẩm.",
            ], 422);
        }

        $item->update(['soluong' => $request->soluong]);

        return response()->json([
            'success'   => true,
            'message'   => 'Đã cập nhật số lượng.',
            'soluong'   => $item->soluong,
            'thanh_tien' => $bienThe->gia * $item->soluong,
        ]);
    }

    /**
     * Xóa một sản phẩm khỏi giỏ hàng
     */
    public function xoa($id)
    {
        $userId = Auth::id();

        $item = GioHang::where('id_giohang', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
        ]);
    }

    /**
     * Xóa toàn bộ giỏ hàng của user
     */
    public function xoaTat()
    {
        $userId = Auth::id();

        GioHang::where('user_id', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa toàn bộ giỏ hàng.',
        ]);
    }

    /**
     * Đếm tổng số sản phẩm trong giỏ (dùng cho badge icon)
     */
    public function demSoLuong()
    {
        $userId = Auth::id();

        $count = GioHang::where('user_id', $userId)->sum('soluong');

        return response()->json([
            'success' => true,
            'count'   => $count,
        ]);
    }
}