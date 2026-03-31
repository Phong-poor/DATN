<?php

namespace App\Http\Controllers;

use App\Models\BienThe;
use Illuminate\Http\Request;

class BienTheController extends Controller
{
    public function index()
    {
        $bienthes = BienThe::with('sanPham')
            ->orderByDesc('id_bienthe')
            ->get();

        return response()->json($bienthes);
    }

    public function getBySanPham($id_sanpham)
    {
        $bienthes = BienThe::where('id_sanpham', $id_sanpham)
            ->orderByDesc('id_bienthe')
            ->get();

        return response()->json($bienthes);
    }

    public function show($id)
    {
        $bienthe = BienThe::with('sanPham')->find($id);

        if (!$bienthe) {
            return response()->json([
                'message' => 'Không tìm thấy biến thể.'
            ], 404);
        }

        return response()->json($bienthe);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_sanpham'   => 'required|integer|exists:sanpham,id_sanpham',
            'ten_bienthe'  => 'nullable|string|max:255',
            'gia'          => 'required|numeric|min:0',
            'soluong'      => 'required|integer|min:0',
        ], [
            'id_sanpham.required' => 'Vui lòng chọn sản phẩm.',
            'id_sanpham.exists'   => 'Sản phẩm không tồn tại.',

            'ten_bienthe.max'     => 'Tên biến thể không được vượt quá 255 ký tự.',

            'gia.required'        => 'Giá không được để trống.',
            'gia.numeric'         => 'Giá phải là số.',
            'gia.min'             => 'Giá phải lớn hơn hoặc bằng 0.',

            'soluong.required'    => 'Số lượng không được để trống.',
            'soluong.integer'     => 'Số lượng phải là số nguyên.',
            'soluong.min'         => 'Số lượng phải lớn hơn hoặc bằng 0.',
        ]);

        $bienthe = BienThe::create([
            'id_sanpham'  => $request->id_sanpham,
            'ten_bienthe' => $request->ten_bienthe,
            'gia'         => $request->gia,
            'soluong'     => $request->soluong,
            'thuoc_tinh_json' => json_encode($request->thuoc_tinh ?? [], JSON_UNESCAPED_UNICODE),
        ]);

        return response()->json([
            'message' => 'Thêm biến thể thành công.',
            'data' => $bienthe
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $bienthe = BienThe::find($id);

        if (!$bienthe) {
            return response()->json([
                'message' => 'Không tìm thấy biến thể.'
            ], 404);
        }

        $request->validate([
            'id_sanpham'   => 'required|integer|exists:sanpham,id_sanpham',
            'ten_bienthe'  => 'nullable|string|max:255',
            'gia'          => 'required|numeric|min:0',
            'soluong'      => 'required|integer|min:0',
        ], [
            'id_sanpham.required' => 'Vui lòng chọn sản phẩm.',
            'id_sanpham.exists'   => 'Sản phẩm không tồn tại.',

            'ten_bienthe.max'     => 'Tên biến thể không được vượt quá 255 ký tự.',

            'gia.required'        => 'Giá không được để trống.',
            'gia.numeric'         => 'Giá phải là số.',
            'gia.min'             => 'Giá phải lớn hơn hoặc bằng 0.',

            'soluong.required'    => 'Số lượng không được để trống.',
            'soluong.integer'     => 'Số lượng phải là số nguyên.',
            'soluong.min'         => 'Số lượng phải lớn hơn hoặc bằng 0.',
        ]);

        $bienthe->update([
            'id_sanpham'  => $request->id_sanpham,
            'ten_bienthe' => $request->ten_bienthe,
            'gia'         => $request->gia,
            'soluong'     => $request->soluong,
            'thuoc_tinh_json' => json_encode($request->thuoc_tinh ?? [], JSON_UNESCAPED_UNICODE),
        ]);

        return response()->json([
            'message' => 'Cập nhật biến thể thành công.',
            'data' => $bienthe
        ]);
    }

    public function destroy($id)
    {
        $bienthe = BienThe::find($id);

        if (!$bienthe) {
            return response()->json([
                'message' => 'Không tìm thấy biến thể.'
            ], 404);
        }

        $bienthe->delete();

        return response()->json([
            'message' => 'Xóa biến thể thành công.'
        ]);
    }
}
