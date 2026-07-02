<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\BienTheHinhAnh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BienTheHinhAnhController extends Controller
{
    public function index()
    {
        $items = BienTheHinhAnh::with('sanPham')
            ->orderByDesc('id_bienthe_hinhanh')
            ->get();

        return response()->json($items);
    }

    public function getBySanPham($id_sanpham)
    {
        $items = BienTheHinhAnh::where('id_sanpham', $id_sanpham)
            ->orderBy('thutu')
            ->get();

        return response()->json($items);
    }

    public function show($id)
    {
        $item = BienTheHinhAnh::find($id);

        if (! $item) {
            return response()->json([
                'message' => 'Không tìm thấy hình ảnh phụ sản phẩm.',
            ], 404);
        }

        return response()->json($item);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_sanpham' => 'required|integer|exists:sanpham,id_sanpham',
            'duongdan' => 'required|string',
            'thutu' => 'nullable|integer|min:0',
        ], [
            'id_sanpham.required' => 'Vui lòng chọn sản phẩm.',
            'id_sanpham.exists' => 'Sản phẩm không tồn tại.',
            'duongdan.required' => 'Đường dẫn ảnh không được để trống.',
        ]);

        $imagePath = str_starts_with($request->duongdan, 'data:image')
            ? ImageHelper::saveBase64Image($request->duongdan, 'uploads/sanpham')
            : ImageHelper::normalizePublicPath($request->duongdan);

        if (! $imagePath) {
            return response()->json([
                'message' => 'Lưu ảnh thất bại.',
            ], 422);
        }

        $item = BienTheHinhAnh::create([
            'id_sanpham' => $request->id_sanpham,
            'duongdan' => $imagePath,
            'thutu' => $request->thutu ?? 0,
        ]);

        $this->touchProductImageCaches((int) $request->id_sanpham);

        return response()->json([
            'message' => 'Thêm hình ảnh phụ thành công.',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $item = BienTheHinhAnh::find($id);

        if (! $item) {
            return response()->json([
                'message' => 'Không tìm thấy hình ảnh phụ sản phẩm.',
            ], 404);
        }

        $request->validate([
            'id_sanpham' => 'required|integer|exists:sanpham,id_sanpham',
            'duongdan' => 'required|string',
            'thutu' => 'nullable|integer|min:0',
        ]);

        $imagePath = str_starts_with($request->duongdan, 'data:image')
            ? ImageHelper::saveBase64Image($request->duongdan, 'uploads/sanpham')
            : ImageHelper::normalizePublicPath($request->duongdan);

        if (! $imagePath) {
            return response()->json([
                'message' => 'Cập nhật ảnh thất bại.',
            ], 422);
        }

        $item->update([
            'id_sanpham' => $request->id_sanpham,
            'duongdan' => $imagePath,
            'thutu' => $request->thutu ?? 0,
        ]);

        $this->touchProductImageCaches((int) $request->id_sanpham);

        return response()->json([
            'message' => 'Cập nhật hình ảnh phụ thành công.',
            'data' => $item,
        ]);
    }

    public function destroy($id)
    {
        $item = BienTheHinhAnh::find($id);

        if (! $item) {
            return response()->json([
                'message' => 'Không tìm thấy hình ảnh phụ sản phẩm.',
            ], 404);
        }

        $productId = (int) $item->id_sanpham;
        $item->delete();
        $this->touchProductImageCaches($productId);

        return response()->json([
            'message' => 'Xóa hình ảnh phụ thành công.',
        ]);
    }

    private function touchProductImageCaches(int $productId): void
    {
        Cache::put('sanpham_cache_bust', (string) microtime(true));
        Cache::forget("sanpham_show_{$productId}");
        Cache::forget('mobile_home_v2');
    }
}
