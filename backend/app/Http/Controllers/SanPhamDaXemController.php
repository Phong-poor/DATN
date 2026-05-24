<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SanPhamDaXem;
use App\Models\SanPham;
use Carbon\Carbon;

class SanPhamDaXemController extends Controller
{
    /**
     * Ghi nhận lượt xem sản phẩm của người dùng
     */
    public function logView(Request $request, $id_sanpham)
    {
        $user = $request->user();

        // Kiểm tra sản phẩm có tồn tại không
        $sanpham = SanPham::find($id_sanpham);
        if (!$sanpham) {
            return response()->json(['message' => 'Sản phẩm không tồn tại'], 404);
        }

        // Tìm hoặc tạo mới bản ghi
        $viewedProduct = SanPhamDaXem::firstOrNew([
            'id_user' => $user->id,
            'id_sanpham' => $id_sanpham
        ]);

        // Cập nhật thời gian xem thành hiện tại
        $viewedProduct->viewed_at = Carbon::now();
        $viewedProduct->save();

        return response()->json(['message' => 'Đã ghi nhận lượt xem', 'data' => $viewedProduct], 200);
    }

    /**
     * Lấy danh sách sản phẩm đã xem gần đây (trong 12 giờ qua)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Lấy thời điểm 12 tiếng trước
        $twelveHoursAgo = Carbon::now()->subHours(12);

        // Lấy danh sách các sản phẩm đã xem trong 12h qua, sắp xếp mới nhất lên đầu
        $recentlyViewed = SanPhamDaXem::where('id_user', $user->id)
            ->where('viewed_at', '>=', $twelveHoursAgo)
            ->orderBy('viewed_at', 'desc')
            ->get();

        // Nạp thông tin sản phẩm và các biến thể tương tự như API lấy danh sách sản phẩm
        $idSanPhamList = $recentlyViewed->pluck('id_sanpham')->toArray();

        // Fetch products maintaining the ordered list
        if (empty($idSanPhamList)) {
            return response()->json([], 200);
        }

        $products = SanPham::with(['danhMuc', 'thuongHieu', 'bienThes', 'hinhAnhs'])
            ->whereIn('id_sanpham', $idSanPhamList)
            ->get()
            ->keyBy('id_sanpham');

        $result = [];
        foreach ($recentlyViewed as $view) {
            if (isset($products[$view->id_sanpham])) {
                $product = $products[$view->id_sanpham]->toArray();
                $product['viewed_at'] = $view->viewed_at; // Gắn thêm thời gian xem
                $result[] = $product;
            }
        }

        return response()->json($result, 200);
    }
}
