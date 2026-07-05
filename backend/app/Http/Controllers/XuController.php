<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CauHinhXu;
use Illuminate\Support\Facades\Auth;

class XuController extends Controller
{
    /**
     * Lấy số dư Xu hiện tại của người dùng
     */
    public function getBalance(Request $request)
    {
        return response()->json([
            'success' => true,
            'xu' => $request->user()->xu ?? 0
        ]);
    }



    /**
     * Lấy cấu hình Xu hiện tại (dành cho phía Client/Checkout)
     */
    public function getPublicSettings()
    {
        $cauhinh = CauHinhXu::first() ?? CauHinhXu::create([
            'ti_le_quy_doi' => 1,
            'ti_le_tich_luy' => 1.00,
            'phan_tram_giam_toi_da' => 50,
            'trang_thai' => true
        ]);

        return response()->json([
            'success' => true,
            'settings' => $cauhinh
        ]);
    }

    /**
     * Admin: Lấy cấu hình Xu hiện tại
     */
    public function getAdminSettings()
    {
        $cauhinh = CauHinhXu::first() ?? CauHinhXu::create([
            'ti_le_quy_doi' => 1,
            'ti_le_tich_luy' => 1.00,
            'phan_tram_giam_toi_da' => 50,
            'trang_thai' => true
        ]);

        return response()->json([
            'success' => true,
            'settings' => $cauhinh
        ]);
    }

    /**
     * Admin: Cập nhật cấu hình Xu
     */
    public function updateAdminSettings(Request $request)
    {
        $request->validate([
            'ti_le_quy_doi' => 'required|integer|min:1',
            'ti_le_tich_luy' => 'required|numeric|min:0|max:100',
            'phan_tram_giam_toi_da' => 'required|integer|min:0|max:100',
            'trang_thai' => 'required|boolean',
        ], [
            'ti_le_quy_doi.required' => 'Vui lòng nhập tỷ lệ quy đổi.',
            'ti_le_quy_doi.integer' => 'Tỷ lệ quy đổi phải là số nguyên.',
            'ti_le_quy_doi.min' => 'Tỷ lệ quy đổi tối thiểu là 1đ/xu.',
            'ti_le_tich_luy.required' => 'Vui lòng nhập tỷ lệ tích lũy.',
            'ti_le_tich_luy.numeric' => 'Tỷ lệ tích lũy phải là số.',
            'ti_le_tich_luy.min' => 'Tỷ lệ tích lũy tối thiểu là 0%.',
            'ti_le_tich_luy.max' => 'Tỷ lệ tích lũy tối đa là 100%.',
            'phan_tram_giam_toi_da.required' => 'Vui lòng nhập phần trăm giảm tối đa.',
            'phan_tram_giam_toi_da.integer' => 'Phần trăm giảm tối đa phải là số nguyên.',
            'phan_tram_giam_toi_da.min' => 'Phần trăm tối thiểu là 0%.',
            'phan_tram_giam_toi_da.max' => 'Phần trăm tối đa là 100%.',
            'trang_thai.required' => 'Vui lòng chọn trạng thái kích hoạt.',
        ]);

        $cauhinh = CauHinhXu::first();
        if (!$cauhinh) {
            $cauhinh = new CauHinhXu();
        }

        $cauhinh->fill([
            'ti_le_quy_doi' => $request->ti_le_quy_doi,
            'ti_le_tich_luy' => $request->ti_le_tich_luy,
            'phan_tram_giam_toi_da' => $request->phan_tram_giam_toi_da,
            'trang_thai' => $request->trang_thai,
        ]);
        $cauhinh->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cấu hình hệ thống xu thành công!',
            'settings' => $cauhinh
        ]);
    }
}
