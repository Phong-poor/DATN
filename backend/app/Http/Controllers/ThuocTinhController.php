<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NhomThuocTinh;
use App\Models\ThuocTinh;
use App\Models\GiaTriThuocTinh;

class ThuocTinhController extends Controller
{
    // ================= NHÓM =================

    public function getNhom()
    {
        return response()->json(
            NhomThuocTinh::all()
        );
    }

    public function addNhom(Request $request)
    {
        $data = $request->validate([
            'ten_nhom' => 'required|string|max:255'
        ]);

        return response()->json(
            NhomThuocTinh::create($data)
        );
    }

    public function deleteNhom($id)
    {
        NhomThuocTinh::destroy($id);

        return response()->json(['message' => 'Xóa nhóm thành công']);
    }
    public function updateNhom(Request $request, $id)
    {
        $data = $request->validate([
            'ten_nhom' => 'required|string|max:255'
        ]);

        $nhom = NhomThuocTinh::findOrFail($id);
        $nhom->update($data);

        return response()->json($nhom);
    }


    // ================= THUỘC TÍNH =================

    public function getThuocTinh()
    {
        return response()->json(
            ThuocTinh::with(['nhomThuocTinh'])
                ->withCount('giatriThuocTinhs') // đếm số biến thể
                ->get()
        );
    }

    public function addThuocTinh(Request $request)
    {
        $data = $request->validate([
            'ten_thuoctinh' => 'required|string|max:255',
            'id_nhom'       => 'required|exists:nhom_thuoctinh,id_nhom'
        ]);

        return response()->json(
            ThuocTinh::create($data)
        );
    }

    public function deleteThuocTinh($id)
    {
        ThuocTinh::destroy($id);

        return response()->json(['message' => 'Xóa thuộc tính thành công']);
    }
    public function updateThuocTinh(Request $request, $id)
    {
        $data = $request->validate([
            'ten_thuoctinh' => 'required|string|max:255',
            'id_nhom'       => 'required|exists:nhom_thuoctinh,id_nhom'
        ]);

        $thuocTinh = ThuocTinh::findOrFail($id);
        $thuocTinh->update($data);

        return response()->json($thuocTinh);
    }

    // ================= GIÁ TRỊ (BIẾN THỂ) =================

    public function getGiaTri($id_thuoctinh)
    {
        return response()->json(
            GiaTriThuocTinh::where('id_thuoctinh', $id_thuoctinh)->get()
        );
    }

    public function addGiaTri(Request $request)
    {
        $data = $request->validate([
            'id_thuoctinh'  => 'required|exists:thuoctinh,id_thuoctinh',
            'giatri'        => 'required|string|max:255',
            'gia_cong_them' => 'nullable|numeric|min:0',
            'trangthai'     => 'nullable|boolean',
        ]);

        $data['gia_cong_them'] = $data['gia_cong_them'] ?? 0;
        $data['trangthai'] = $data['trangthai'] ?? 1;

        return response()->json(
            GiaTriThuocTinh::create($data)
        );
    }

    public function deleteGiaTri($id)
    {
        GiaTriThuocTinh::destroy($id);

        return response()->json(['message' => 'Xóa giá trị thành công']);
    }

    public function updateGiaTri(Request $request, $id)
    {
        $data = $request->validate([
            'id_thuoctinh'  => 'required|exists:thuoctinh,id_thuoctinh',
            'giatri'        => 'required|string|max:255',
            'gia_cong_them' => 'nullable|numeric|min:0',
            'trangthai'     => 'nullable|boolean',
        ]);

        $data['gia_cong_them'] = $data['gia_cong_them'] ?? 0;
        $data['trangthai'] = $data['trangthai'] ?? 1;

        $giaTri = GiaTriThuocTinh::findOrFail($id);
        $giaTri->update($data);

        return response()->json($giaTri);
    }
    // ================= BONUS =================

    // lấy full data (phù hợp render 1 lần bên Vue)
    public function getAll()
    {
        $nhoms = NhomThuocTinh::with([
            'thuocTinhs.giatriThuocTinhs'
        ])->get();

        return response()->json(
            $nhoms->map(function ($group) {
                return [
                    'id_nhom' => $group->id_nhom,
                    'ten_nhom' => $group->ten_nhom,
                    'thuoc_tinhs' => $group->thuocTinhs->map(function ($attr) {
                        return [
                            'id_thuoctinh' => $attr->id_thuoctinh,
                            'ten_thuoctinh' => $attr->ten_thuoctinh,
                            'giatri_thuoc_tinhs' => $attr->giatriThuocTinhs->map(function ($gt) {
                                return [
                                    'id_giatri' => $gt->id_giatri,
                                    'giatri' => $gt->giatri,
                                    'gia_cong_them' => $gt->gia_cong_them ?? 0,
                                    'trangthai' => $gt->trangthai ?? 1,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values()
        );
    }
}