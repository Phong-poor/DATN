<?php

namespace App\Http\Controllers;

use App\Models\FlashSaleSession;
use App\Models\FlashSaleProduct;
use Illuminate\Http\Request;

class FlashSaleWebController extends Controller
{
    /**
     * Get the active or upcoming flash sale session
     */
    public function getCurrentSession()
    {
        $now = now();

        // 1. Try to find the active session
        $session = FlashSaleSession::where('trang_thai', 1)
            ->where('thoi_gian_bat_dau', '<=', $now)
            ->where('thoi_gian_ket_thuc', '>=', $now)
            ->first();

        $status = 'active';

        // 2. If no active session, find the nearest upcoming one
        if (!$session) {
            $session = FlashSaleSession::where('trang_thai', 1)
                ->where('thoi_gian_bat_dau', '>', $now)
                ->orderBy('thoi_gian_bat_dau', 'asc')
                ->first();
            $status = $session ? 'upcoming' : 'none';
        }

        if (!$session) {
            return response()->json([
                'success' => true,
                'status' => 'none',
                'session' => null,
                'products' => []
            ]);
        }

        // Get products in this session
        $rawProducts = FlashSaleProduct::with(['bienThe.sanPham.thuongHieu', 'bienThe.sanPham.danhMuc'])
            ->where('session_id', $session->id_session)
            ->get();

        $products = $rawProducts->map(function ($item) {
            $bienThe = $item->bienThe;
            $sanPham = $bienThe?->sanPham;

            if (!$bienThe || !$sanPham) {
                return null;
            }

            // Extract specs
            $ram = '16GB';
            $ssd = '512GB';
            $mausac = '';
            
            $generalSpecs = [];
            try {
                $tskt = is_string($sanPham->thong_so_ky_thuat)
                    ? json_decode($sanPham->thong_so_ky_thuat, true)
                    : ($sanPham->thong_so_ky_thuat ?: []);
                if (is_array($tskt)) {
                    foreach ($tskt as $specItem) {
                        if (isset($specItem['giatri'])) {
                            $generalSpecs[] = $specItem['giatri'];
                        }
                    }
                }
            } catch (\Exception $e) {}

            try {
                $tt = is_string($bienThe->thuoc_tinh_json) 
                    ? json_decode($bienThe->thuoc_tinh_json, true) 
                    : ($bienThe->thuoc_tinh_json ?: []);
                if (is_array($tt)) {
                    foreach ($tt as $attr) {
                        $name = strtolower($attr['ten_thuoctinh'] ?? '');
                        if (strpos($name, 'ram') !== false) {
                            $ram = $attr['giatri'] ?? $ram;
                        }
                        if (strpos($name, 'ssd') !== false || strpos($name, 'ổ cứng') !== false) {
                            $ssd = $attr['giatri'] ?? $ssd;
                        }
                        if (strpos($name, 'màu') !== false) {
                            $mausac = $attr['giatri'] ?? $mausac;
                        }
                    }
                }
            } catch (\Exception $e) {}

            // Image URL resolution
            $imagePath = $bienThe->hinhanh ?: $sanPham->hinhanh;
            $imageUrl = $imagePath ? asset('storage/' . $imagePath) : null;

            return [
                'id_flash_sale_product' => $item->id_flash_sale_product,
                'id_sanpham' => $sanPham->id_sanpham,
                'id_bienthe' => $item->id_bienthe,
                'tenSP' => $sanPham->tenSP,
                'ten_bienthe' => $bienThe->ten_bienthe,
                'brand' => $sanPham->thuongHieu?->ten_thuonghieu ?? $sanPham->brand ?? 'ASUS',
                'category' => $sanPham->danhMuc?->ten_danhmuc ?? $sanPham->category ?? 'Laptop Gaming',
                'gia' => (float) $item->gia_flash_sale,
                'oldPrice' => (float) $bienThe->gia, // Variant's original price is the oldPrice
                'specs' => count($generalSpecs) > 0 ? array_slice($generalSpecs, 0, 4) : [$ram, $ssd, $mausac, 'IPS FHD'],
                'image' => $imageUrl ?: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500',
                'rating' => $sanPham->rating_avg !== null ? (float) $sanPham->rating_avg : 4.8,
                'reviews' => $sanPham->rating_count !== null ? (int) $sanPham->rating_count : 0,
                'promo' => $sanPham->mota_ngan ?: 'Tặng kèm Balo Predator + Chuột Gaming',
                'so_luong_gioi_han' => $item->so_luong_gioi_han,
                'so_luong_da_ban' => $item->so_luong_da_ban,
                'inStock' => ($bienThe->soluong > 0) && ($item->so_luong_da_ban < $item->so_luong_gioi_han),
                'ram' => $ram,
                'ssd' => $ssd,
                'mausac' => $mausac
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'status' => $status,
            'session' => [
                'id_session' => $session->id_session,
                'ten_dot' => $session->ten_dot,
                'thoi_gian_bat_dau' => $session->thoi_gian_bat_dau->toIso8601String(),
                'thoi_gian_ket_thuc' => $session->thoi_gian_ket_thuc->toIso8601String(),
                'server_time' => now()->toIso8601String(),
            ],
            'products' => $products
        ]);
    }
}
