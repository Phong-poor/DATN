<?php

namespace App\Http\Controllers;

use App\Models\VaiTro;
use Illuminate\Http\Request;

class VaiTroController extends Controller
{
    /**
     * Danh sách đầy đủ tất cả các mã quyền trong hệ thống
     */
    private function getAllSystemPermissions()
    {
        return [
            'san_pham_xem', 'san_pham_sua', 'nhap_xuat_kho',
            'danh_muc_xem', 'danh_muc_sua',
            'thuong_hieu_xem', 'thuong_hieu_sua',
            'bien_the_xem', 'bien_the_sua',
            'don_hang_xem', 'don_hang_sua', 'hoa_don_xem',
            'marketing_quan_ly', 'affiliate_quan_ly',
            'xu_quan_ly', 'vong_quay_quan_ly', 'diem_danh_quan_ly',
            'tin_tuc_quan_ly', 'binh_luan_quan_ly', 'banner_quan_ly',
            'lien_he_quan_ly', 'chat_quan_ly',
            'tai_khoan_quan_ly', 'vai_tro_quan_ly', 'nhat_ky_quan_ly', 'quan_ly_cham_cong'
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allPermissions = $this->getAllSystemPermissions();

        // Đảm bảo vai trò 'admin' trong cơ sở dữ liệu luôn tự động được cập nhật đầy đủ 100% các quyền
        $adminRole = VaiTro::where('ma_vaitro', 'admin')->first();
        if ($adminRole) {
            $adminRole->quyen = $allPermissions;
            $adminRole->saveQuietly();
        }

        $roles = VaiTro::orderBy('id_vaitro', 'asc')->get();

        // Trả về dữ liệu vai trò admin luôn có đủ tất cả các quyền
        $roles->transform(function ($r) use ($allPermissions) {
            if ($r->ma_vaitro === 'admin') {
                $r->quyen = $allPermissions;
            }
            return $r;
        });

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_vaitro' => 'required|string|max:255',
            'ma_vaitro' => 'required|string|max:255|unique:vai_tro,ma_vaitro',
            'mo_ta' => 'nullable|string',
            'quyen' => 'nullable|array',
        ], [
            'ten_vaitro.required' => 'Vui lòng nhập tên vai trò.',
            'ma_vaitro.required' => 'Vui lòng nhập mã vai trò.',
            'ma_vaitro.unique' => 'Mã vai trò này đã tồn tại.',
        ]);

        $maVaitro = strtolower(trim($validated['ma_vaitro']));
        $quyen = $validated['quyen'] ?? [];

        if ($maVaitro === 'admin') {
            $quyen = $this->getAllSystemPermissions();
        }

        $role = VaiTro::create([
            'ten_vaitro' => $validated['ten_vaitro'],
            'ma_vaitro' => $maVaitro,
            'mo_ta' => $validated['mo_ta'] ?? null,
            'quyen' => $quyen,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo vai trò thành công',
            'data' => $role
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = VaiTro::findOrFail($id);
        if ($role->ma_vaitro === 'admin') {
            $role->quyen = $this->getAllSystemPermissions();
        }
        return response()->json([
            'success' => true,
            'data' => $role
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $role = VaiTro::findOrFail($id);

        $validated = $request->validate([
            'ten_vaitro' => 'required|string|max:255',
            'ma_vaitro' => 'required|string|max:255|unique:vai_tro,ma_vaitro,' . $id . ',id_vaitro',
            'mo_ta' => 'nullable|string',
            'quyen' => 'nullable|array',
        ], [
            'ten_vaitro.required' => 'Vui lòng nhập tên vai trò.',
            'ma_vaitro.required' => 'Vui lòng nhập mã vai trò.',
            'ma_vaitro.unique' => 'Mã vai trò này đã tồn tại.',
        ]);

        $maVaitro = strtolower(trim($validated['ma_vaitro']));

        // Ngăn chặn đổi mã vai trò của admin tối cao
        if ($role->ma_vaitro === 'admin' && $maVaitro !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể đổi mã vai trò của Quản trị viên tối cao (admin)'
            ], 422);
        }

        $quyen = $validated['quyen'] ?? [];
        if ($role->ma_vaitro === 'admin' || $maVaitro === 'admin') {
            $quyen = $this->getAllSystemPermissions();
        }

        $role->update([
            'ten_vaitro' => $validated['ten_vaitro'],
            'ma_vaitro' => $maVaitro,
            'mo_ta' => $validated['mo_ta'] ?? null,
            'quyen' => $quyen,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật vai trò thành công',
            'data' => $role
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = VaiTro::findOrFail($id);

        if ($role->ma_vaitro === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vai trò Quản trị viên tối cao (admin)'
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa vai trò thành công'
        ]);
    }
}
