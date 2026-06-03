<?php

namespace App\Http\Controllers;

use App\Models\DanhMuc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DanhMucController extends Controller
{
    public function index(){
        $danhmuc = Cache::remember('danhmuc_all', 120, function () {
            return DanhMuc::all();
        });
        return response()->json(['thongbao' => 'thành công', 'data' => $danhmuc]);
    }

    /**
     * Get all parent categories (top-level categories)
     */
    public function getParentCategories()
    {
        $parents = Cache::remember('danhmuc_parents', 120, function () {
            return \App\Models\DanhMucCha::all();
        });
        return response()->json(['data' => $parents]);
    }

    /**
     * Get children categories of a parent
     */
    public function getChildrenCategories($parentId)
    {
        $children = DanhMuc::where('id_danhmuc_cha', $parentId)->get();
        return response()->json(['data' => $children]);
    }

    /**
     * Get category with its inherited attributes
     */
    public function getCategoryWithInheritedAttributes($categoryId)
    {
        $category = DanhMuc::find($categoryId);
        if (!$category) {
            return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
        }

        $inheritedAttrIds = $category->getInheritedAttributeIds();
        
        return response()->json([
            'data' => [
                'category' => $category,
                'inherited_attribute_ids' => $inheritedAttrIds
            ]
        ]);
    }
    public function store(Request $request){
        $validated = $request->validate([
            'ten_danhmuc' => 'required|string|max:255|unique:danhmuc,ten_danhmuc',
            'trangthai'  => 'required|in:active,hidden',
            'id_danhmuc_cha' => 'required|exists:danhmuc_cha,id_danhmuc_cha',
        ]);

        $danhmuc = DanhMuc::create([
            'ten_danhmuc' => $validated['ten_danhmuc'],
            'trangthai' => $validated['trangthai'],
            'id_danhmuc_cha' => $validated['id_danhmuc_cha']
        ]);
        
        Cache::forget('danhmuc_all');
        Cache::forget('danhmuc_parents');

        return response()->json([
            'thongbao' => 'thành công',
            'message' => 'Thêm danh mục thành công',
            'data' => $danhmuc
        ], 201);
    }
    public function show($id)
    {
        $danhMuc = Cache::remember("danhmuc_show_{$id}", 120, function () use ($id) {
            return DanhMuc::find($id);
        });

        if (!$danhMuc) {
            return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
        }

        return response()->json(['data' => $danhMuc], 200);
    }
    public function update(Request $request, $id)
    {
        $danhMuc = DanhMuc::find($id);

        if (!$danhMuc) {
            return response()->json(['message' => 'Không tìm thấy danh mục để sửa'], 404);
        }

        $validated = $request->validate([
            'ten_danhmuc' => 'required|string|max:255|unique:danhmuc,ten_danhmuc,' . $id . ',id_danhmuc',
            'trangthai'  => 'required|in:active,hidden',
            'id_danhmuc_cha'  => 'required|exists:danhmuc_cha,id_danhmuc_cha',
        ]);

        $danhMuc->update([
            'ten_danhmuc' => $validated['ten_danhmuc'],
            'trangthai' => $validated['trangthai'],
            'id_danhmuc_cha' => $validated['id_danhmuc_cha']
        ]);

        Cache::forget('danhmuc_all');
        Cache::forget('danhmuc_parents');
        Cache::forget("danhmuc_show_{$id}");

        return response()->json([
            'thongbao' => 'thành công',
            'message' => 'Cập nhật thành công',
            'data' => $danhMuc
        ], 200);
        }
        public function destroy($id)
    {
        $danhMuc = DanhMuc::find($id);

        if (!$danhMuc) {
            return response()->json(['message' => 'Không tìm thấy danh mục để xóa'], 404);
        }

        $danhMuc->delete();

        Cache::forget('danhmuc_all');
        Cache::forget("danhmuc_show_{$id}");

        return response()->json([
            'thongbao' => 'thành công',
            'message' => 'Đã xóa danh mục'
        ], 200);
    }
}

