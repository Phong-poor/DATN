<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function index()
    {
        $colors = Color::orderByDesc('id')->get();

        return response()->json($colors);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:colors,name',
            'hex_code' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/', 'unique:colors,hex_code'],
        ], [
            'name.required' => 'Tên màu không được để trống.',
            'name.unique' => 'Tên màu đã tồn tại.',
            'name.max' => 'Tên màu không được vượt quá 100 ký tự.',

            'hex_code.required' => 'Mã HEX không được để trống.',
            'hex_code.regex' => 'Mã HEX không đúng định dạng. Ví dụ: #1A1A1A',
            'hex_code.unique' => 'Mã HEX đã tồn tại.',
        ]);

        $color = Color::create([
            'name' => $request->name,
            'hex_code' => strtoupper($request->hex_code),
        ]);

        return response()->json([
            'message' => 'Thêm màu thành công.',
            'data' => $color,
        ], 201);
    }

    public function show($id)
    {
        $color = Color::find($id);

        if (!$color) {
            return response()->json([
                'message' => 'Không tìm thấy màu.',
            ], 404);
        }

        return response()->json($color);
    }

    public function update(Request $request, $id)
    {
        $color = Color::find($id);

        if (!$color) {
            return response()->json([
                'message' => 'Không tìm thấy màu.',
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:100|unique:colors,name,' . $id,
            'hex_code' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/', 'unique:colors,hex_code,' . $id],
        ], [
            'name.required' => 'Tên màu không được để trống.',
            'name.unique' => 'Tên màu đã tồn tại.',
            'name.max' => 'Tên màu không được vượt quá 100 ký tự.',

            'hex_code.required' => 'Mã HEX không được để trống.',
            'hex_code.regex' => 'Mã HEX không đúng định dạng. Ví dụ: #1A1A1A',
            'hex_code.unique' => 'Mã HEX đã tồn tại.',
        ]);

        $color->update([
            'name' => $request->name,
            'hex_code' => strtoupper($request->hex_code),
        ]);

        return response()->json([
            'message' => 'Cập nhật màu thành công.',
            'data' => $color,
        ]);
    }

    public function destroy($id)
    {
        $color = Color::find($id);

        if (!$color) {
            return response()->json([
                'message' => 'Không tìm thấy màu.',
            ], 404);
        }

        $color->delete();

        return response()->json([
            'message' => 'Xóa màu thành công.',
        ]);
    }
}