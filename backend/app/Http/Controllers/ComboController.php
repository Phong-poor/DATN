<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Combo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComboController extends Controller
{
    /**
     * Storefront: Get active combos with products and variants
     */
    public function index()
    {
        $combos = Cache::remember('combos_storefront_active', 120, function () {
            return Combo::with(['sanPhams.bienThes', 'sanPhams.hinhAnhs'])
                ->where('trangthai', 1)
                ->get()
                ->filter(function ($combo) {
                    return self::isComboInStock($combo->id_combo);
                })
                ->values()
                ->map(function ($combo) {
                    return [
                        'id_combo' => $combo->id_combo,
                        'ten_combo' => $combo->ten_combo,
                        'mota' => $combo->mota,
                        'giakhuyenmai' => $combo->giakhuyenmai,
                        'hinhanh' => $combo->hinhanh,
                        'products' => $combo->sanPhams->map(function ($p) {
                            return [
                                'id_sanpham' => $p->id_sanpham,
                                'tenSP' => $p->tenSP,
                                'SKU' => $p->SKU,
                                'hinhanh' => $p->hinhanh ? asset('storage/'.$p->hinhanh) : null,
                                'bien_thes' => $p->bienThes->map(function ($bt) {
                                    return [
                                        'id_bienthe' => $bt->id_bienthe,
                                        'ten_bienthe' => $bt->ten_bienthe,
                                        'gia' => $bt->gia,
                                        'soluong' => $bt->soluong,
                                        'thuoc_tinh' => is_string($bt->thuoc_tinh_json)
                                            ? json_decode($bt->thuoc_tinh_json, true)
                                            : $bt->thuoc_tinh_json,
                                    ];
                                }),
                            ];
                        }),
                    ];
                });
        });

        return response()->json([
            'success' => true,
            'data' => $combos,
        ]);
    }

    /**
     * Storefront: Get specific combo details
     */
    public function show($id)
    {
        $combo = Combo::with(['sanPhams.bienThes', 'sanPhams.hinhAnhs'])
            ->find($id);

        if (! $combo) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy combo',
            ], 404);
        }

        $formattedCombo = [
            'id_combo' => $combo->id_combo,
            'ten_combo' => $combo->ten_combo,
            'mota' => $combo->mota,
            'giakhuyenmai' => $combo->giakhuyenmai,
            'trangthai' => $combo->trangthai,
            'hinhanh' => $combo->hinhanh,
            'products' => $combo->sanPhams->map(function ($p) {
                return [
                    'id_sanpham' => $p->id_sanpham,
                    'tenSP' => $p->tenSP,
                    'SKU' => $p->SKU,
                    'hinhanh' => $p->hinhanh ? asset('storage/'.$p->hinhanh) : null,
                    'bien_thes' => $p->bienThes->map(function ($bt) {
                        return [
                            'id_bienthe' => $bt->id_bienthe,
                            'ten_bienthe' => $bt->ten_bienthe,
                            'gia' => $bt->gia,
                            'soluong' => $bt->soluong,
                            'thuoc_tinh' => is_string($bt->thuoc_tinh_json)
                                ? json_decode($bt->thuoc_tinh_json, true)
                                : $bt->thuoc_tinh_json,
                        ];
                    }),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $formattedCombo,
        ]);
    }

    /**
     * Admin: Get all combos
     */
    public function adminIndex()
    {
        $combos = Combo::with('sanPhams')
            ->orderByDesc('id_combo')
            ->get()
            ->map(function ($combo) {
                return [
                    'id_combo' => $combo->id_combo,
                    'ten_combo' => $combo->ten_combo,
                    'mota' => $combo->mota,
                    'giakhuyenmai' => $combo->giakhuyenmai,
                    'trangthai' => $combo->trangthai,
                    'hinhanh' => $combo->hinhanh,
                    'is_in_stock' => self::isComboInStock($combo->id_combo),
                    'products_count' => $combo->sanPhams->count(),
                    'products' => $combo->sanPhams->map(function ($p) {
                        return [
                            'id_sanpham' => $p->id_sanpham,
                            'tenSP' => $p->tenSP,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $combos,
        ]);
    }

    /**
     * Admin: Create a new combo
     */
    public function store(Request $request)
    {
        $request->validate([
            'ten_combo' => 'required|string|max:255',
            'giakhuyenmai' => 'required|numeric|min:0',
            'trangthai' => 'required|in:0,1',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:sanpham,id_sanpham',
            'hinhanh' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $coverPath = null;
            if ($request->filled('hinhanh')) {
                $coverPath = str_starts_with($request->hinhanh, 'data:image')
                    ? ImageHelper::saveBase64Image($request->hinhanh, 'uploads/sanpham')
                    : ImageHelper::normalizePublicPath($request->hinhanh);
            }

            $combo = Combo::create([
                'ten_combo' => $request->ten_combo,
                'giakhuyenmai' => $request->giakhuyenmai,
                'trangthai' => $request->trangthai,
                'mota' => $request->mota,
                'hinhanh' => $coverPath,
            ]);

            $combo->sanPhams()->attach($request->product_ids);

            DB::commit();
            $this->clearComboCaches();

            return response()->json([
                'success' => true,
                'message' => 'Thêm combo mới thành công!',
                'data' => $combo,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Update combo
     */
    public function update(Request $request, $id)
    {
        $combo = Combo::find($id);

        if (! $combo) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy combo để cập nhật',
            ], 404);
        }

        $request->validate([
            'ten_combo' => 'required|string|max:255',
            'giakhuyenmai' => 'required|numeric|min:0',
            'trangthai' => 'required|in:0,1',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:sanpham,id_sanpham',
            'hinhanh' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $coverPath = $combo->hinhanh;

            if ($request->has('hinhanh')) {
                if (blank($request->hinhanh)) {
                    $coverPath = null;
                } elseif (str_starts_with($request->hinhanh, 'data:image')) {
                    $coverPath = ImageHelper::saveBase64Image($request->hinhanh, 'uploads/sanpham');
                } else {
                    $coverPath = ImageHelper::normalizePublicPath($request->hinhanh);
                }
            }

            $combo->update([
                'ten_combo' => $request->ten_combo,
                'giakhuyenmai' => $request->giakhuyenmai,
                'trangthai' => $request->trangthai,
                'mota' => $request->mota,
                'hinhanh' => $coverPath,
            ]);

            $combo->sanPhams()->sync($request->product_ids);

            DB::commit();
            $this->clearComboCaches();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật combo thành công!',
                'data' => $combo,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Delete combo
     */
    public function destroy($id)
    {
        $combo = Combo::find($id);

        if (! $combo) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy combo để xóa',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $combo->sanPhams()->detach();
            $combo->delete();

            DB::commit();
            $this->clearComboCaches();

            return response()->json([
                'success' => true,
                'message' => 'Xóa combo thành công!',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Lỗi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Get all variant combo offers
     */
    public function adminOffersIndex()
    {
        $offers = DB::table('bienthe_combo_offers')
            ->join('bienthe', 'bienthe_combo_offers.id_bienthe', '=', 'bienthe.id_bienthe')
            ->join('sanpham', 'bienthe.id_sanpham', '=', 'sanpham.id_sanpham')
            ->join('combos', 'bienthe_combo_offers.id_combo', '=', 'combos.id_combo')
            ->select(
                'bienthe_combo_offers.id',
                'bienthe_combo_offers.id_bienthe',
                'bienthe_combo_offers.id_combo',
                'bienthe_combo_offers.loai_uudai',
                'bienthe_combo_offers.giakhuyenmai_override',
                'bienthe_combo_offers.mota_uudai',
                'bienthe_combo_offers.gioi_han_soluong',
                'bienthe_combo_offers.da_su_dung',
                'bienthe_combo_offers.ngay_het_han',
                'bienthe_combo_offers.trangthai',
                'bienthe.ten_bienthe',
                'bienthe.gia as bienthe_gia',
                'sanpham.tenSP as sanpham_ten',
                'sanpham.id_sanpham',
                'combos.ten_combo as combo_ten',
                'combos.giakhuyenmai as combo_gia'
            )
            ->orderByDesc('bienthe_combo_offers.id')
            ->get()
            ->map(function ($offer) {
                $offer->is_combo_in_stock = self::isComboInStock($offer->id_combo);
                $offer->is_valid = self::isOfferValid($offer);

                return $offer;
            });

        return response()->json([
            'success' => true,
            'data' => $offers,
        ]);
    }

    /**
     * Admin: Create a new variant combo offer
     */
    public function storeOffer(Request $request)
    {
        $request->validate([
            'id_bienthe' => 'required|exists:bienthe,id_bienthe',
            'id_combo' => 'required|exists:combos,id_combo',
            'loai_uudai' => 'required|in:free,discount',
            'giakhuyenmai_override' => 'nullable|numeric|min:0',
            'mota_uudai' => 'nullable|string|max:255',
            'gioi_han_soluong' => 'nullable|integer|min:1',
            'ngay_het_han' => 'nullable|date',
            'trangthai' => 'required|in:0,1',
        ]);

        // Check if there is an existing offer for this variant and combo
        $existing = DB::table('bienthe_combo_offers')
            ->where('id_bienthe', $request->id_bienthe)
            ->where('id_combo', $request->id_combo)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Biến thể này đã được gán ưu đãi với combo này rồi!',
            ], 400);
        }

        $id = DB::table('bienthe_combo_offers')->insertGetId([
            'id_bienthe' => $request->id_bienthe,
            'id_combo' => $request->id_combo,
            'loai_uudai' => $request->loai_uudai,
            'giakhuyenmai_override' => $request->loai_uudai === 'free' ? 0.00 : $request->giakhuyenmai_override,
            'mota_uudai' => $request->mota_uudai,
            'gioi_han_soluong' => $request->gioi_han_soluong,
            'da_su_dung' => 0,
            'ngay_het_han' => $request->ngay_het_han,
            'trangthai' => $request->trangthai,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newOffer = DB::table('bienthe_combo_offers')
            ->join('bienthe', 'bienthe_combo_offers.id_bienthe', '=', 'bienthe.id_bienthe')
            ->join('sanpham', 'bienthe.id_sanpham', '=', 'sanpham.id_sanpham')
            ->join('combos', 'bienthe_combo_offers.id_combo', '=', 'combos.id_combo')
            ->select(
                'bienthe_combo_offers.id',
                'bienthe_combo_offers.id_bienthe',
                'bienthe_combo_offers.id_combo',
                'bienthe_combo_offers.loai_uudai',
                'bienthe_combo_offers.giakhuyenmai_override',
                'bienthe_combo_offers.mota_uudai',
                'bienthe_combo_offers.gioi_han_soluong',
                'bienthe_combo_offers.da_su_dung',
                'bienthe_combo_offers.ngay_het_han',
                'bienthe_combo_offers.trangthai',
                'bienthe.ten_bienthe',
                'sanpham.tenSP as sanpham_ten',
                'combos.ten_combo as combo_ten'
            )
            ->where('bienthe_combo_offers.id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Tạo ưu đãi biến thể thành công!',
            'data' => $newOffer,
        ], 201);
    }

    /**
     * Admin: Update an existing variant combo offer
     */
    public function updateOffer(Request $request, $id)
    {
        $request->validate([
            'id_bienthe' => 'required|exists:bienthe,id_bienthe',
            'id_combo' => 'required|exists:combos,id_combo',
            'loai_uudai' => 'required|in:free,discount',
            'giakhuyenmai_override' => 'nullable|numeric|min:0',
            'mota_uudai' => 'nullable|string|max:255',
            'gioi_han_soluong' => 'nullable|integer|min:1',
            'ngay_het_han' => 'nullable|date',
            'trangthai' => 'required|in:0,1',
        ]);

        $offer = DB::table('bienthe_combo_offers')->where('id', $id)->first();
        if (! $offer) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ưu đãi cần cập nhật',
            ], 404);
        }

        // Check if there is another duplicate
        $dup = DB::table('bienthe_combo_offers')
            ->where('id_bienthe', $request->id_bienthe)
            ->where('id_combo', $request->id_combo)
            ->where('id', '<>', $id)
            ->first();

        if ($dup) {
            return response()->json([
                'success' => false,
                'message' => 'Biến thể này đã được gán ưu đãi với combo này rồi!',
            ], 400);
        }

        DB::table('bienthe_combo_offers')
            ->where('id', $id)
            ->update([
                'id_bienthe' => $request->id_bienthe,
                'id_combo' => $request->id_combo,
                'loai_uudai' => $request->loai_uudai,
                'giakhuyenmai_override' => $request->loai_uudai === 'free' ? 0.00 : $request->giakhuyenmai_override,
                'mota_uudai' => $request->mota_uudai,
                'gioi_han_soluong' => $request->gioi_han_soluong,
                'ngay_het_han' => $request->ngay_het_han,
                'trangthai' => $request->trangthai,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ưu đãi biến thể thành công!',
        ]);
    }

    /**
     * Admin: Delete a variant combo offer
     */
    public function deleteOffer($id)
    {
        $offer = DB::table('bienthe_combo_offers')->where('id', $id)->first();
        if (! $offer) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ưu đãi để xóa',
            ], 404);
        }

        DB::table('bienthe_combo_offers')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa ưu đãi thành công!',
        ]);
    }

    /**
     * Helper: Check if all accessory products in a combo have at least one variant with stock > 0
     */
    public static function isComboInStock($comboId)
    {
        $combo = Combo::with('sanPhams.bienThes')->find($comboId);
        if (! $combo) {
            return false;
        }

        foreach ($combo->sanPhams as $product) {
            $productHasStock = false;
            foreach ($product->bienThes as $variant) {
                if ($variant->soluong > 0) {
                    $productHasStock = true;
                    break;
                }
            }
            if (! $productHasStock) {
                // At least one accessory product in this combo has no stock in any variants!
                return false;
            }
        }

        return true;
    }

    /**
     * Helper: Check if a promo offer is valid (active status, within limit, not expired, and combo in stock)
     */
    public static function isOfferValid($offer)
    {
        if (! $offer) {
            return false;
        }

        // 1. Basic status check
        if (isset($offer->trangthai) && $offer->trangthai == 0) {
            return false;
        }

        // 2. Expiration date check
        if (isset($offer->ngay_het_han) && $offer->ngay_het_han !== null) {
            if (now()->gt(Carbon::parse($offer->ngay_het_han))) {
                return false;
            }
        }

        // 3. Usage limit check
        if (isset($offer->gioi_han_soluong) && $offer->gioi_han_soluong !== null) {
            if ($offer->da_su_dung >= $offer->gioi_han_soluong) {
                return false;
            }
        }

        // 4. Stock check of accessories in the combo
        if (isset($offer->id_combo)) {
            if (! self::isComboInStock($offer->id_combo)) {
                return false;
            }
        }

        return true;
    }

    private function clearComboCaches()
    {
        Cache::forget('combos_storefront_active');
    }
}
