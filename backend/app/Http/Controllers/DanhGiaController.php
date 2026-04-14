<?php

namespace App\Http\Controllers;

use App\Models\DanhGia;
use App\Models\DatHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DanhGiaController extends Controller
{
    /**
     * Lấy danh sách đánh giá công khai cho một sản phẩm (chỉ những bài đã duyệt)
     */
    public function index($productId)
    {
        $reviews = DanhGia::with(['user', 'bienThe'])
            ->whereHas('bienThe', function ($q) use ($productId) {
                $q->where('id_sanpham', $productId);
            })
            ->where('trangthai', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'reviews' => $reviews
        ]);
    }

    /**
     * Admin: Lấy toàn bộ danh sách đánh giá
     */
    public function adminIndex()
    {
        $reviews = DanhGia::with(['user', 'bienThe.sanPham'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'reviews' => $reviews
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_dathang' => 'required|exists:dathang,id_dathang',
            'id_bienthe' => 'required|exists:bienthe,id_bienthe',
            'danhgia'    => 'required|integer|min:1|max:5',
            'binhluan'   => 'nullable|string'
        ]);

        $userId = Auth::id();

        $order = DatHang::where('id_dathang', $request->id_dathang)
            ->where('user_id', $userId)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Đơn hàng không hợp lệ.'], 403);
        }

        if ($order->trangthai !== 'done') {
            return response()->json(['success' => false, 'message' => 'Bạn chỉ có thể đánh giá sau khi đơn hàng đã hoàn thành.'], 400);
        }

        $hasItem = $order->chi_tiets()->where('id_bienthe', $request->id_bienthe)->exists();
        if (!$hasItem) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm này không nằm trong đơn hàng.'], 400);
        }

        $exists = DanhGia::where('id_dathang', $request->id_dathang)
            ->where('id_bienthe', $request->id_bienthe)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này cho đơn hàng này rồi.'], 400);
        }

        $danhGia = DanhGia::create([
            'id_dathang' => $request->id_dathang,
            'id_bienthe' => $request->id_bienthe,
            'user_id'    => $userId,
            'danhgia'    => $request->danhgia,
            'binhluan'   => $request->binhluan,
            'trangthai'  => 'pending' // Mặc định chờ duyệt
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Cảm ơn bạn đã đánh giá sản phẩm! Đánh giá của bạn sẽ được hiển thị sau khi được duyệt.',
            'danh_gia'  => $danhGia
        ]);
    }

    /**
     * Admin: Cập nhật trạng thái đánh giá (Duyệt/Spam)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'trangthai' => 'required|in:pending,approved,spam'
        ]);

        $review = DanhGia::findOrFail($id);
        $review->update(['trangthai' => $request->trangthai]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái đánh giá thành công!',
            'review'  => $review
        ]);
    }

    /**
     * Admin/User: Xóa đánh giá
     */
    public function destroy($id)
    {
        $review = DanhGia::findOrFail($id);
        
        // Nếu không phải admin thì chỉ được xóa review của chính mình
        if (Auth::user()->role !== 'admin' && $review->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xóa đánh giá này.'], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa đánh giá thành công.'
        ]);
    }
}
