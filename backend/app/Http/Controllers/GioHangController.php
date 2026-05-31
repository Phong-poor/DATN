<?php

namespace App\Http\Controllers;

use App\Models\GioHang;
use App\Models\BienThe;
use App\Models\Combo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GioHangController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $items = GioHang::with(['bienThe.sanPham', 'combo'])
            ->where('user_id', $userId)
            ->get();

        // Nhóm các items theo combo_group_id để tính giá phân bổ
        $groupedCombos = $items->filter(fn($item) => $item->id_combo && $item->combo_group_id)
            ->groupBy('combo_group_id');

        $comboItemPrices = []; // id_giohang => gia_da_giam

        // Lấy danh sách ID biến thể thực tế trong giỏ hàng để kiểm tra điều kiện ưu đãi
        $cartVariantIds = $items->pluck('id_bienthe')->toArray();
        $freeComboOffers = DB::table('bienthe_combo_offers')
            ->whereIn('id_bienthe', $cartVariantIds)
            ->where('trangthai', 1)
            ->get()
            ->filter(function ($offer) {
                return \App\Http\Controllers\ComboController::isOfferValid($offer);
            })
            ->keyBy('id_combo');

        foreach ($groupedCombos as $groupId => $comboItems) {
            if ($comboItems->isEmpty()) continue;
            
            $first = $comboItems->first();
            $combo = $first->combo;
            if (!$combo) continue;

            // Xác định giá bán của combo (miễn phí nếu có biến thể kích hoạt ưu đãi, hoặc lấy giá gốc combo)
            $totalComboPrice = (float) $combo->giakhuyenmai;
            if (isset($freeComboOffers[$combo->id_combo])) {
                $offer = $freeComboOffers[$combo->id_combo];
                if ($offer->loai_uudai === 'free') {
                    $totalComboPrice = 0.00;
                } else {
                    $totalComboPrice = (float) ($offer->giakhuyenmai_override ?? $combo->giakhuyenmai);
                }
            }
            
            // Tính tổng giá gốc của các biến thể được chọn trong combo
            $sumOriginalPrice = 0;
            foreach ($comboItems as $item) {
                $sumOriginalPrice += $item->bienThe ? (float)$item->bienThe->gia : 0;
            }

            if ($sumOriginalPrice <= 0) continue;

            // Phân bổ tỷ lệ giá
            $tempSum = 0;
            $itemsCount = $comboItems->count();
            
            foreach ($comboItems as $index => $item) {
                if (!$item->bienThe) continue;
                
                $originalPrice = (float)$item->bienThe->gia;
                
                if ($index === $itemsCount - 1) {
                    // Món cuối cùng: Lấy phần còn lại để khớp chính xác giá combo
                    $allocatedPrice = $totalComboPrice - $tempSum;
                } else {
                    $allocatedPrice = $totalComboPrice > 0 
                        ? round($originalPrice * ($totalComboPrice / $sumOriginalPrice))
                        : 0.00;
                    $tempSum += $allocatedPrice;
                }
                
                $comboItemPrices[$item->id_giohang] = $allocatedPrice;
            }
        }

        $gioHang = $items->map(function ($item) use ($comboItemPrices, $freeComboOffers) {
            $bienThe = $item->bienThe;
            $sanPham = $bienThe?->sanPham;

            // Parse thuoc_tinh_json
            $thuocTinh = [];
            if ($bienThe && $bienThe->thuoc_tinh_json) {
                $thuocTinh = is_string($bienThe->thuoc_tinh_json)
                    ? json_decode($bienThe->thuoc_tinh_json, true)
                    : $bienThe->thuoc_tinh_json;
            }

            // Lấy giá bán (nếu thuộc combo thì lấy giá phân bổ, ngược lại lấy giá gốc của biến thể)
            $unitPrice = isset($comboItemPrices[$item->id_giohang])
                ? $comboItemPrices[$item->id_giohang]
                : ($bienThe?->gia ?? 0);

            $giaCombo = $item->combo?->giakhuyenmai ?? 0;
            if ($item->id_combo && isset($freeComboOffers[$item->id_combo])) {
                $offer = $freeComboOffers[$item->id_combo];
                $giaCombo = $offer->loai_uudai === 'free' ? 0.00 : ($offer->giakhuyenmai_override ?? $giaCombo);
            }

            return [
                'id_giohang'   => $item->id_giohang,
                'id_bienthe'   => $item->id_bienthe,
                'soluong'      => $item->soluong,
                'id_combo'     => $item->id_combo,
                'combo_group_id' => $item->combo_group_id,
                'ten_combo'    => $item->combo?->ten_combo ?? null,
                'hinhanh_combo' => $item->combo?->hinhanh ? asset('storage/' . $item->combo->hinhanh) : null,
                'gia_combo'    => $giaCombo,
                'ten_bienthe'  => $bienThe?->ten_bienthe ?? '',
                'gia_goc'      => $bienThe?->gia ?? 0,
                'gia'          => $unitPrice,
                'ton_kho'      => $bienThe?->soluong ?? 0,
                'thuoc_tinh'   => $thuocTinh,
                'ten_san_pham' => $sanPham?->tenSP ?? '',
                'thong_so_ky_thuat' => $sanPham?->thong_so_ky_thuat ?? [],
                'hinh_anh'     => $sanPham?->hinhanh
                    ? asset('storage/' . $sanPham->hinhanh)
                    : null,
                'thanh_tien'   => $unitPrice * $item->soluong,
            ];
        });

        $tongTien = $gioHang->sum('thanh_tien');

        return response()->json([
            'success'   => true,
            'gio_hang'  => $gioHang,
            'tong_tien' => $tongTien,
            'so_luong_san_pham' => $gioHang->count(),
        ]);
    }

    /**
     * Thêm combo vào giỏ hàng
     */
    public function themCombo(Request $request)
    {
        $request->validate([
            'id_combo' => 'required|exists:combos,id_combo',
            'soluong' => 'required|integer|min:1',
            'selected_variants' => 'required|array|min:1',
            'selected_variants.*' => 'exists:bienthe,id_bienthe',
        ]);

        $userId = Auth::id();
        $idCombo = $request->id_combo;
        $soLuong = $request->soluong;
        $selectedVariants = $request->selected_variants;

        // Kiểm tra tồn kho của tất cả biến thể được chọn
        foreach ($selectedVariants as $idBienThe) {
            $bienThe = BienThe::findOrFail($idBienThe);
            if ($bienThe->soluong < $soLuong) {
                return response()->json([
                    'success' => false,
                    'message' => "Sản phẩm {$bienThe->ten_bienthe} chỉ còn {$bienThe->soluong} sản phẩm trong kho.",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Tìm xem đã có nhóm combo giống hệt trước đó chưa để cộng dồn số lượng
            $existingComboGroupId = null;
            $userComboItems = GioHang::where('user_id', $userId)
                ->where('id_combo', $idCombo)
                ->whereNotNull('combo_group_id')
                ->get()
                ->groupBy('combo_group_id');

            foreach ($userComboItems as $groupId => $items) {
                $groupVariantIds = $items->pluck('id_bienthe')->toArray();
                sort($groupVariantIds);
                sort($selectedVariants);
                if ($groupVariantIds === $selectedVariants) {
                    $existingComboGroupId = $groupId;
                    break;
                }
            }

            if ($existingComboGroupId) {
                // Cộng dồn số lượng cho nhóm cũ
                GioHang::where('combo_group_id', $existingComboGroupId)
                    ->increment('soluong', $soLuong);
            } else {
                // Tạo nhóm mới
                $newGroupId = uniqid('combo_', true);
                foreach ($selectedVariants as $idBienThe) {
                    GioHang::create([
                        'user_id' => $userId,
                        'id_bienthe' => $idBienThe,
                        'soluong' => $soLuong,
                        'id_combo' => $idCombo,
                        'combo_group_id' => $newGroupId,
                    ]);
                }
            }

            // Trừ tồn kho lập tức của từng biến thể con
            foreach ($selectedVariants as $idBienThe) {
                BienThe::where('id_bienthe', $idBienThe)->decrement('soluong', $soLuong);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã thêm combo vào giỏ hàng thành công!',
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi thêm combo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật số lượng combo trong giỏ
     */
    public function capNhatCombo(Request $request, $groupId)
    {
        $request->validate([
            'soluong' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();
        $newSoLuong = $request->soluong;

        $items = GioHang::where('user_id', $userId)
            ->where('combo_group_id', $groupId)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhóm combo',
            ], 404);
        }

        // Kiểm tra tồn kho của tất cả sản phẩm con khi tăng số lượng
        foreach ($items as $item) {
            $bienThe = BienThe::findOrFail($item->id_bienthe);
            $diff = $newSoLuong - $item->soluong;
            if ($diff > 0 && $bienThe->soluong < $diff) {
                return response()->json([
                    'success' => false,
                    'message' => "Sản phẩm {$bienThe->ten_bienthe} không đủ số lượng trong kho.",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $bienThe = BienThe::findOrFail($item->id_bienthe);
                $diff = $newSoLuong - $item->soluong;

                if ($diff > 0) {
                    $bienThe->decrement('soluong', $diff);
                } elseif ($diff < 0) {
                    $bienThe->increment('soluong', abs($diff));
                }

                $item->update(['soluong' => $newSoLuong]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật số lượng combo!',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cập nhật: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa combo khỏi giỏ hàng
     */
    public function xoaCombo($groupId)
    {
        $userId = Auth::id();

        $items = GioHang::where('user_id', $userId)
            ->where('combo_group_id', $groupId)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhóm combo để xóa',
            ], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                if ($item->bienThe) {
                    $item->bienThe->increment('soluong', $item->soluong);
                }
                $item->delete();
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa combo khỏi giỏ hàng và hoàn trả số lượng vào kho.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa combo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function them(Request $request)
    {
        $request->validate([
            'id_bienthe' => 'required|exists:bienthe,id_bienthe',
            'soluong'    => 'required|integer|min:1',
        ]);

        $userId    = Auth::id();
        $idBienThe = $request->id_bienthe;
        $soLuong   = $request->soluong;

        // Kiểm tra tồn kho
        $bienThe = BienThe::findOrFail($idBienThe);
        if ($bienThe->soluong < $soLuong) {
            return response()->json([
                'success' => false,
                'message' => "Chỉ còn {$bienThe->soluong} sản phẩm trong kho.",
            ], 422);
        }

        // Nếu sản phẩm đã có trong giỏ → cộng thêm số lượng
        $existing = GioHang::where('user_id', $userId)
            ->where('id_bienthe', $idBienThe)
            ->whereNull('id_combo')
            ->whereNull('combo_group_id')
            ->first();

        if ($existing) {
            $existing->update(['soluong' => $existing->soluong + $soLuong]);
            $item = $existing;
        } else {
            $item = GioHang::create([
                'user_id'    => $userId,
                'id_bienthe' => $idBienThe,
                'soluong'    => $soLuong,
            ]);
        }

        // Trừ tồn kho ngay lập tức
        $bienThe->decrement('soluong', $soLuong);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng và giữ chỗ sản phẩm!',
            'item'    => $item,
        ], 201);
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     */
    public function capNhat(Request $request, $id)
    {
        $request->validate([
            'soluong' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();

        $item = GioHang::where('id_giohang', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $bienThe = BienThe::findOrFail($item->id_bienthe);

        // Tính toán chênh lệch số lượng
        $diff = $request->soluong - $item->soluong;

        if ($diff > 0) {
            // Nếu tăng số lượng -> kiểm tra kho
            if ($bienThe->soluong < $diff) {
                return response()->json([
                    'success' => false,
                    'message' => "Kho chỉ còn {$bienThe->soluong} sản phẩm.",
                ], 422);
            }
            $bienThe->decrement('soluong', $diff);
        } elseif ($diff < 0) {
            // Nếu giảm số lượng -> cộng lại vào kho
            $bienThe->increment('soluong', abs($diff));
        }

        $item->update(['soluong' => $request->soluong]);

        return response()->json([
            'success'   => true,
            'message'   => 'Đã cập nhật số lượng.',
            'soluong'   => $item->soluong,
            'thanh_tien' => $bienThe->gia * $item->soluong,
        ]);
    }

    /**
     * Xóa một sản phẩm khỏi giỏ hàng
     */
    public function xoa($id)
    {
        $userId = Auth::id();

        $item = GioHang::where('id_giohang', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Cộng lại số lượng vào kho trước khi xóa
        if ($item->bienThe) {
            $item->bienThe->increment('soluong', $item->soluong);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm và trả lại số lượng vào kho.',
        ]);
    }

    /**
     * Xóa toàn bộ giỏ hàng của user
     */
    public function xoaTat()
    {
        $userId = Auth::id();

        $items = GioHang::where('user_id', $userId)->get();
        
        foreach ($items as $item) {
            if ($item->bienThe) {
                $item->bienThe->increment('soluong', $item->soluong);
            }
            $item->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa toàn bộ giỏ hàng và hoàn trả số lượng.',
        ]);
    }

    /**
     * Đếm tổng số sản phẩm trong giỏ (dùng cho badge icon)
     */
    public function demSoLuong()
    {
        $userId = Auth::id();

        $count = GioHang::where('user_id', $userId)->sum('soluong');

        return response()->json([
            'success' => true,
            'count'   => $count,
        ]);
    }
}
