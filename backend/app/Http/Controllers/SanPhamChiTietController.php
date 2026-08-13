<?php

namespace App\Http\Controllers;

use App\Models\SanPham;

class SanPhamChiTietController extends Controller
{
    public function show($id)
    {
        $sanPham = SanPham::with([
            'thuongHieu',
            'hinhAnhs',
            'bienThes.bienTheThuocTinhs.giaTriThuocTinh.thuocTinh.nhomThuocTinh',
        ])->find($id);

        if (! $sanPham) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm',
            ], 404);
        }

        $result = [
            'id_sanpham' => $sanPham->id_sanpham,
            'tenSP' => $sanPham->tenSP,
            'gia' => $sanPham->gia,
            'SKU' => $sanPham->SKU ?? null,
            'hinhanh' => $sanPham->hinhanh,
            'mo_ta' => $sanPham->mo_ta ?? null,

            'thuong_hieu' => $sanPham->thuongHieu ? [
                'id_thuonghieu' => $sanPham->thuongHieu->id_thuonghieu,
                'ten_thuonghieu' => $sanPham->thuongHieu->ten_thuonghieu,
            ] : null,

            'hinh_anhs' => $sanPham->hinhAnhs->map(function ($img) {
                return [
                    'id' => $img->id ?? null,
                    'duongdan' => $img->duongdan ?? null,
                ];
            })->values(),

            'bien_thes' => $sanPham->bienThes->map(function ($bienThe) {
                return [
                    'id_bienthe' => $bienThe->id_bienthe,
                    'id_sanpham' => $bienThe->id_sanpham,
                    'ten_bienthe' => $bienThe->ten_bienthe,
                    'gia' => $bienThe->gia,
                    'soluong' => $bienThe->soluong,

                    'thuoc_tinh' => $bienThe->bienTheThuocTinhs->map(function ($btThuocTinh) {
                        $giaTri = $btThuocTinh->giaTriThuocTinh;
                        $thuocTinh = $giaTri?->thuocTinh;

                        return [
                            'id_thuoctinh' => $thuocTinh?->id_thuoctinh,
                            'ten_thuoctinh' => $thuocTinh?->ten_thuoctinh,
                            'id_nhom' => $thuocTinh?->id_nhom,
                            'ten_nhom' => $thuocTinh?->nhomThuocTinh?->ten_nhom,
                            'id_giatri' => $giaTri?->id_giatri,
                            'giatri' => $giaTri?->giatri,
                            'trangthai' => $giaTri?->trangthai,
                            'ma_mau' => $giaTri && isset($giaTri->ma_mau) ? $giaTri->ma_mau : null,
                        ];
                    })->filter(function ($item) {
                        return ! empty($item['ten_thuoctinh']) && ! empty($item['giatri']);
                    })->values(),
                ];
            })->values(),
        ];

        return response()->json($result);
    }
}
