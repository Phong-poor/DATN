<?php

namespace App\Http\Controllers;

use App\Models\VaiTro;
use Illuminate\Http\Request;

class VaiTroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = VaiTro::orderBy('id_vaitro', 'asc')->get();
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

        $role = VaiTro::create([
            'ten_vaitro' => $validated['ten_vaitro'],
            'ma_vaitro' => strtolower(trim($validated['ma_vaitro'])),
            'mo_ta' => $validated['mo_ta'] ?? null,
            'quyen' => $validated['quyen'] ?? [],
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

        // Ngăn chặn đổi mã vai trò của admin tối cao
        if ($role->ma_vaitro === 'admin' && strtolower(trim($validated['ma_vaitro'])) !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể đổi mã vai trò của Quản trị viên tối cao (admin)'
            ], 422);
        }

        $role->update([
            'ten_vaitro' => $validated['ten_vaitro'],
            'ma_vaitro' => strtolower(trim($validated['ma_vaitro'])),
            'mo_ta' => $validated['mo_ta'] ?? null,
            'quyen' => $validated['quyen'] ?? [],
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
