<?php

namespace App\Http\Controllers;

use App\Models\ThuongHieu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThuongHieuController extends Controller
{
    
    public function index(){
        $thuonghieu = Cache::remember('thuonghieu_all', 120, function () {
            return ThuongHieu::all();
        });
        return response()->json(['thongbao' => 'thành công', 'data' => $thuonghieu]);
    }
    public function store(Request $request){
        $validated = $request->validate([
            'ten_thuonghieu' => 'required|string|max:255|unique:thuonghieu,ten_thuonghieu'
            
        ]);

        $danhmuc = ThuongHieu::create($validated);
        
        Cache::forget('thuonghieu_all');

        return response()->json([
            'thongbao' => 'thành công',
            'message' => 'Thêm danh mục thành công',
            'data' => $danhmuc
        ], 201);
    }
    public function show($id)
    {
        $thuonghieu = Cache::remember("thuonghieu_show_{$id}", 120, function () use ($id) {
            return ThuongHieu::find($id);
        });

        if (!$thuonghieu) {
            return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
        }

        return response()->json(['data' => $thuonghieu], 200);
    }
    public function update(Request $request, $id)
    {
        $thuonghieu = ThuongHieu::find($id);

        if (!$thuonghieu) {
            return response()->json(['message' => 'Không tìm thấy danh mục để sửa'], 404);
        }

        $validated = $request->validate([
            'ten_thuonghieu' => 'sometimes|required|string|max:255|unique:thuonghieu,ten_thuonghieu,' . $id . ',id_thuonghieu'
            
        ]);

        
        $thuonghieu->update($validated);

        Cache::forget('thuonghieu_all');
        Cache::forget("thuonghieu_show_{$id}");

        return response()->json([
            'thongbao' => 'thành công',
            'message' => 'Cập nhật thành công',
            'data' => $thuonghieu
        ], 200);
        }
        public function destroy($id)
    {
        $thuonghieu = ThuongHieu::find($id);

        if (!$thuonghieu) {
            return response()->json(['message' => 'Không tìm thấy danh mục để xóa'], 404);
        }

        $thuonghieu->delete();

        Cache::forget('thuonghieu_all');
        Cache::forget("thuonghieu_show_{$id}");

        return response()->json([
            'thongbao' => 'thành công',
            'message' => 'Đã xóa danh mục'
        ], 200);
    }

}
