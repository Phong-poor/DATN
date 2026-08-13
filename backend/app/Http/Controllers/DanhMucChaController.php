<?php

namespace App\Http\Controllers;

use App\Models\DanhMuc;
use App\Models\DanhMucCha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DanhMucChaController extends Controller
{
    public function index()
    {
        $parents = Cache::remember('danhmuc_cha_all', 120, function () {
            return DanhMucCha::all();
        });

        return response()->json(['data' => $parents]);
    }

    public function show($id)
    {
        $parent = DanhMucCha::find($id);
        if (! $parent) {
            return response()->json(['message' => 'Không tìm thấy danh mục cha'], 404);
        }

        return response()->json(['data' => $parent]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_danhmuc' => 'required|string|max:255|unique:danhmuc_cha,ten_danhmuc',
            'trangthai' => 'required|in:active,hidden',
        ]);

        $parent = DanhMucCha::create($validated);
        Cache::forget('danhmuc_cha_all');
        Cache::forget('danhmuc_all'); // Clear all related caches

        return response()->json(['message' => 'Thêm danh mục cha thành công', 'data' => $parent], 201);
    }

    public function update(Request $request, $id)
    {
        $parent = DanhMucCha::find($id);
        if (! $parent) {
            return response()->json(['message' => 'Không tìm thấy danh mục cha'], 404);
        }

        $validated = $request->validate([
            'ten_danhmuc' => 'required|string|max:255|unique:danhmuc_cha,ten_danhmuc,'.$id.',id_danhmuc_cha',
            'trangthai' => 'required|in:active,hidden',
        ]);

        $parent->update($validated);
        Cache::forget('danhmuc_cha_all');
        Cache::forget('danhmuc_all');

        return response()->json(['message' => 'Cập nhật danh mục cha thành công', 'data' => $parent]);
    }

    public function destroy($id)
    {
        $parent = DanhMucCha::find($id);
        if (! $parent) {
            return response()->json(['message' => 'Không tìm thấy danh mục cha'], 404);
        }

        // Check if there are child categories
        $hasChildren = DanhMuc::where('id_danhmuc_cha', $id)->exists();
        if ($hasChildren) {
            return response()->json(['message' => 'Không thể xóa vì danh mục cha này đang có danh mục con'], 400);
        }

        $parent->delete();
        Cache::forget('danhmuc_cha_all');
        Cache::forget('danhmuc_all');

        return response()->json(['message' => 'Xóa danh mục cha thành công']);
    }
}
