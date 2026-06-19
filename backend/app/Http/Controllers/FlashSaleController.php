<?php

namespace App\Http\Controllers;

use App\Models\FlashSaleSession;
use App\Models\FlashSaleProduct;
use App\Models\BienThe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FlashSaleController extends Controller
{
    /**
     * Get all flash sale sessions
     */
    public function index()
    {
        $sessions = FlashSaleSession::withCount('products')
            ->orderBy('thoi_gian_bat_dau', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'sessions' => $sessions
        ]);
    }

    /**
     * Store a new flash sale session
     */
    public function store(Request $request)
    {
        $request->validate([
            'ten_dot' => 'required|string|max:255',
            'thoi_gian_bat_dau' => 'required|date',
            'thoi_gian_ket_thuc' => 'required|date|after:thoi_gian_bat_dau',
            'trang_thai' => 'nullable|integer|in:0,1',
        ]);

        $session = FlashSaleSession::create([
            'ten_dot' => $request->ten_dot,
            'thoi_gian_bat_dau' => $request->thoi_gian_bat_dau,
            'thoi_gian_ket_thuc' => $request->thoi_gian_ket_thuc,
            'trang_thai' => $request->input('trang_thai', 1),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo đợt Flash Sale thành công!',
            'session' => $session
        ], 201);
    }

    /**
     * Show details of a flash sale session, including products
     */
    public function show($id)
    {
        $session = FlashSaleSession::findOrFail($id);
        
        $products = FlashSaleProduct::with(['bienThe.sanPham'])
            ->where('session_id', $id)
            ->get();

        return response()->json([
            'success' => true,
            'session' => $session,
            'products' => $products
        ]);
    }

    /**
     * Update an existing flash sale session
     */
    public function update(Request $request, $id)
    {
        $session = FlashSaleSession::findOrFail($id);

        $request->validate([
            'ten_dot' => 'required|string|max:255',
            'thoi_gian_bat_dau' => 'required|date',
            'thoi_gian_ket_thuc' => 'required|date|after:thoi_gian_bat_dau',
            'trang_thai' => 'nullable|integer|in:0,1',
        ]);

        $session->update([
            'ten_dot' => $request->ten_dot,
            'thoi_gian_bat_dau' => $request->thoi_gian_bat_dau,
            'thoi_gian_ket_thuc' => $request->thoi_gian_ket_thuc,
            'trang_thai' => $request->input('trang_thai', $session->trang_thai),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật đợt Flash Sale thành công!',
            'session' => $session
        ]);
    }

    /**
     * Delete a flash sale session
     */
    public function destroy($id)
    {
        $session = FlashSaleSession::findOrFail($id);
        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa đợt Flash Sale thành công!'
        ]);
    }

    /**
     * Add/update products in a flash sale session (bulk or single)
     */
    public function saveProducts(Request $request, $id)
    {
        $session = FlashSaleSession::findOrFail($id);

        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.id_bienthe' => 'required|exists:bienthe,id_bienthe',
            'products.*.gia_flash_sale' => 'required|numeric|min:0',
            'products.*.so_luong_gioi_han' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $saved = [];
            foreach ($request->products as $prodData) {
                // Check if this variant is already in this session
                $existing = FlashSaleProduct::where('session_id', $id)
                    ->where('id_bienthe', $prodData['id_bienthe'])
                    ->first();

                if ($existing) {
                    $existing->update([
                        'gia_flash_sale' => $prodData['gia_flash_sale'],
                        'so_luong_gioi_han' => $prodData['so_luong_gioi_han'],
                    ]);
                    $saved[] = $existing;
                } else {
                    $newProd = FlashSaleProduct::create([
                        'session_id' => $id,
                        'id_bienthe' => $prodData['id_bienthe'],
                        'gia_flash_sale' => $prodData['gia_flash_sale'],
                        'so_luong_gioi_han' => $prodData['so_luong_gioi_han'],
                        'so_luong_da_ban' => 0,
                    ]);
                    $saved[] = $newProd;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật danh sách sản phẩm sale!',
                'products' => $saved
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lưu sản phẩm: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a product from a flash sale session
     */
    public function removeProduct($id, $productId)
    {
        $product = FlashSaleProduct::where('session_id', $id)
            ->where('id_flash_sale_product', $productId)
            ->firstOrFail();

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi đợt Flash Sale!'
        ]);
    }
}
