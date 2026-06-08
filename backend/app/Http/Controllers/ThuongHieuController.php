<?php

namespace App\Http\Controllers;

use App\Models\ThuongHieu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ThuongHieuController extends Controller
{
    
    public function index(){
        $thuonghieu = Cache::remember('thuonghieu_all', 120, function () {
            return ThuongHieu::all();
        });
        return response()->json(['thongbao' => 'thành công', 'data' => $thuonghieu]);
    }

    /**
     * Get brands filtered by category ID(s)
     */
    public function getByCategory($categoryId)
    {
        $brands = ThuongHieu::where(function ($q) use ($categoryId) {
            $q->whereNull('danh_muc_ids')
              ->orWhereJsonContains('danh_muc_ids', (int)$categoryId);
        })->get();

        return response()->json(['data' => $brands]);
    }
    public function store(Request $request){
        $validated = $request->validate([
            'ten_thuonghieu' => 'required|string|max:255|unique:thuonghieu,ten_thuonghieu',
            'danh_muc_ids'   => 'nullable|array',
            'danh_muc_ids.*' => 'integer|exists:danhmuc,id_danhmuc',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048'
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $thuonghieu = ThuongHieu::create($validated);
        
        Cache::forget('thuonghieu_all');

        return response()->json([
            'thongbao' => 'thành công',
            'message' => 'Thêm thương hiệu thành công',
            'data' => $thuonghieu
        ], 201);
    }
    public function show($id)
    {
        $thuonghieu = Cache::remember("thuonghieu_show_{$id}", 120, function () use ($id) {
            return ThuongHieu::find($id);
        });

        if (!$thuonghieu) {
            return response()->json(['message' => 'Không tìm thấy thương hiệu'], 404);
        }

        return response()->json(['data' => $thuonghieu], 200);
    }
    public function update(Request $request, $id)
    {
        $thuonghieu = ThuongHieu::find($id);

        if (!$thuonghieu) {
            return response()->json(['message' => 'Không tìm thấy thương hiệu để sửa'], 404);
        }

        $validated = $request->validate([
            'ten_thuonghieu' => 'sometimes|required|string|max:255|unique:thuonghieu,ten_thuonghieu,' . $id . ',id_thuonghieu',
            'danh_muc_ids'   => 'nullable|array',
            'danh_muc_ids.*' => 'integer|exists:danhmuc,id_danhmuc',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048'
        ]);

        if ($request->hasFile('logo')) {
            if ($thuonghieu->logo && Storage::disk('public')->exists($thuonghieu->logo)) {
                Storage::disk('public')->delete($thuonghieu->logo);
            }
            $validated['logo'] = $request->file('logo')->store('brands', 'public');
        }

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
            return response()->json(['message' => 'Không tìm thấy thương hiệu để xóa'], 404);
        }

        if ($thuonghieu->logo && Storage::disk('public')->exists($thuonghieu->logo)) {
            Storage::disk('public')->delete($thuonghieu->logo);
        }

        $thuonghieu->delete();

        Cache::forget('thuonghieu_all');
        Cache::forget("thuonghieu_show_{$id}");

        return response()->json([
            'thongbao' => 'thành công',
            'message' => 'Đã xóa thương hiệu'
        ], 200);
    }

}
