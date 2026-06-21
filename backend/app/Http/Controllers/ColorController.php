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
            'ten' => 'required|string|max:100|unique:mausac,ten',
            'mamau' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/', 'unique:mausac,mamau'],
        ], [
            'ten.required' => 'Tên màu không được để trống.',
            'ten.unique' => 'Tên màu đã tồn tại.',
            'ten.max' => 'Tên màu không được vượt quá 100 ký tự.',

            'mamau.required' => 'Mã HEX không được để trống.',
            'mamau.regex' => 'Mã HEX không đúng định dạng. Ví dụ: #1A1A1A',
            'mamau.unique' => 'Mã HEX đã tồn tại.',
        ]);

        $color = Color::create([
            'ten' => $request->ten,
            'mamau' => strtoupper($request->mamau),
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
            'ten' => 'required|string|max:100|unique:mausac,ten,' . $id,
            'mamau' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/', 'unique:mausac,mamau,' . $id],
        ], [
            'ten.required' => 'Tên màu không được để trống.',
            'ten.unique' => 'Tên màu đã tồn tại.',
            'ten.max' => 'Tên màu không được vượt quá 100 ký tự.',

            'mamau.required' => 'Mã HEX không được để trống.',
            'mamau.regex' => 'Mã HEX không đúng định dạng. Ví dụ: #1A1A1A',
            'mamau.unique' => 'Mã HEX đã tồn tại.',
        ]);

        $color->update([
            'ten' => $request->ten,
            'mamau' => strtoupper($request->mamau),
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