<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\YeuThich;
use Illuminate\Support\Facades\Auth;

class YeuThichController extends Controller
{
  
    public function index()
    {
        $userId = Auth::id();
        
       
        $wishlist = YeuThich::with('bienthe.sanpham')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $wishlist
        ]);
    }

    
    public function them(Request $request)
    {
        $request->validate([
            'id_bienthe' => 'required|exists:bienthe,id_bienthe',
            'soluong' => 'nullable|integer|min:1'
        ]);

        $userId = Auth::id();
        $idBienthe = $request->id_bienthe;
        $soluong = $request->soluong ?? 1;

       
        $yeuThich = YeuThich::where('user_id', $userId)
                            ->where('id_bienthe', $idBienthe)
                            ->first();

        if ($yeuThich) {
            
            $yeuThich->soluong += $soluong;
            $yeuThich->save();
        } else {
            
            YeuThich::create([
                'user_id' => $userId,
                'id_bienthe' => $idBienthe,
                'soluong' => $soluong
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Đã thêm vào danh sách yêu thích!'
        ]);
    }

   
    public function capNhat(Request $request, $id)
    {
        $request->validate([
            'soluong' => 'required|integer|min:1'
        ]);

      
        $yeuThich = YeuThich::where('user_id', Auth::id())->findOrFail($id);
        
        $yeuThich->soluong = $request->soluong;
        $yeuThich->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Đã cập nhật số lượng!'
        ]);
    }

    
    public function xoa($id)
    {
        $yeuThich = YeuThich::where('user_id', Auth::id())->findOrFail($id);
        $yeuThich->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Đã xoá khỏi danh sách yêu thích!'
        ]);
    }
}