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
    public function store(Request $request){
        $validated = $request->validate([
            'ten_danhmuc' => 'required|string|max:255',
            'trangthai'  => 'required|in:active,hidden'
        ]);

        $danhmuc = DanhMuc::create($validated);
        
        Cache::forget('danhmuc_all');

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
            'ten_danhmuc' => 'required|string|max:255',
            'trangthai'  => 'required|in:active,hidden'
        ]);

        
        $danhMuc->update($validated);

        Cache::forget('danhmuc_all');
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

    

