<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NhomThuocTinh;
use App\Models\ThuocTinh;
use App\Models\GiaTriThuocTinh;
use Illuminate\Support\Facades\Cache;

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
            'ten_nhom' => 'required|string|max:255',
            'danh_muc_ids'  => 'nullable|array',
            'danh_muc_ids.*'=> 'integer'
        ]);

        Cache::forget('thuoctinh_getall');

        return response()->json(
            NhomThuocTinh::create($data)
        );
    }

    public function deleteNhom($id)
    {
        Cache::forget('thuoctinh_getall');
        NhomThuocTinh::destroy($id);

        return response()->json(['message' => 'Xóa nhóm thành công']);
    }

    public function updateNhom(Request $request, $id)
    {
        $data = $request->validate([
            'ten_nhom' => 'required|string|max:255',
            'danh_muc_ids'  => 'nullable|array',
            'danh_muc_ids.*'=> 'integer'
        ]);

        Cache::forget('thuoctinh_getall');
        $nhom = NhomThuocTinh::findOrFail($id);
        $nhom->update($data);

        \Illuminate\Support\Facades\Cache::forget('thuoctinh_getall');

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
            'id_nhom'       => 'required|exists:nhom_thuoctinh,id_nhom',
            'trangthai'     => 'nullable|boolean',
        ]);

        $data['trangthai'] = $data['trangthai'] ?? 1;

        Cache::forget('thuoctinh_getall');

        return response()->json(
            ThuocTinh::create($data)
        );
    }

    public function deleteThuocTinh($id)
    {
        Cache::forget('thuoctinh_getall');
        ThuocTinh::destroy($id);

        return response()->json(['message' => 'Xóa thuộc tính thành công']);
    }

    public function updateThuocTinh(Request $request, $id)
    {
        $data = $request->validate([
            'ten_thuoctinh' => 'required|string|max:255',
            'id_nhom'       => 'required|exists:nhom_thuoctinh,id_nhom',
            'trangthai'     => 'nullable|boolean',
        ]);

        $data['trangthai'] = $data['trangthai'] ?? 1;

        Cache::forget('thuoctinh_getall');
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
            'danh_muc_ids'  => 'nullable|array',
            'danh_muc_ids.*'=> 'integer'
        ]);

        $data['gia_cong_them'] = $data['gia_cong_them'] ?? 0;
        $data['trangthai'] = $data['trangthai'] ?? 1;

        Cache::forget('thuoctinh_getall');

        return response()->json(
            GiaTriThuocTinh::create($data)
        );
    }

    public function deleteGiaTri($id)
    {
        Cache::forget('thuoctinh_getall');
        GiaTriThuocTinh::destroy($id);
        \Illuminate\Support\Facades\Cache::forget('thuoctinh_getall');

        return response()->json(['message' => 'Xóa giá trị thành công']);
    }

    public function updateGiaTri(Request $request, $id)
    {
        $data = $request->validate([
            'id_thuoctinh'  => 'required|exists:thuoctinh,id_thuoctinh',
            'giatri'        => 'required|string|max:255',
            'gia_cong_them' => 'nullable|numeric|min:0',
            'trangthai'     => 'nullable|boolean',
            'danh_muc_ids'  => 'nullable|array',
            'danh_muc_ids.*'=> 'integer'
        ]);

        $data['gia_cong_them'] = $data['gia_cong_them'] ?? 0;
        $data['trangthai'] = $data['trangthai'] ?? 1;

        Cache::forget('thuoctinh_getall');
        $giaTri = GiaTriThuocTinh::findOrFail($id);
        $giaTri->update($data);

        \Illuminate\Support\Facades\Cache::forget('thuoctinh_getall');

        return response()->json($giaTri);
    }
    // ================= BONUS =================

    // lấy full data (phù hợp render 1 lần bên Vue)
    public function getAll()
    {
        $data = Cache::remember('thuoctinh_getall', 120, function () {
            $nhoms = NhomThuocTinh::with([
                'thuocTinhs.giatriThuocTinhs'
            ])->get();

            return $nhoms->map(function ($group) {
                return [
                    'id_nhom' => $group->id_nhom,
                    'ten_nhom' => $group->ten_nhom,
                    'danh_muc_ids' => $group->danh_muc_ids,
                    'thuoc_tinhs' => $group->thuocTinhs->map(function ($attr) {
                        return [
                            'id_thuoctinh' => $attr->id_thuoctinh,
                            'ten_thuoctinh' => $attr->ten_thuoctinh,
                            'trangthai' => $attr->trangthai ?? 1,
                            'giatri_thuoc_tinhs' => $attr->giatriThuocTinhs->map(function ($gt) {
                                return [
                                    'id_giatri' => $gt->id_giatri,
                                    'giatri' => $gt->giatri,
                                    'gia_cong_them' => $gt->gia_cong_them ?? 0,
                                    'trangthai' => $gt->trangthai ?? 1,
                                    'danh_muc_ids' => $gt->danh_muc_ids,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values();
        });

        return response()->json($data);
    }
}