<?php

namespace App\Http\Controllers;

use App\Models\SanPham;
use App\Models\DanhMuc;
use App\Models\BienThe;
use App\Models\ThuocTinh;
use App\Models\BienTheHinhAnh;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;

/**
 * Cung cấp dữ liệu sản phẩm và xử lý tìm kiếm, thêm, sửa, xóa sản phẩm.
 */
class SanPhamController extends Controller
{
    public function index(Request $request)
    {
        $imageVersion = $this->sanPhamCacheVersion();
        
        $user = request()->user('sanctum');
        $isAdmin = $user && ($user->vaitro !== 'user');
        $referer = request()->header('referer');
        $isFromAdminPanel = ($referer && str_contains(strtolower($referer), '/admin')) || $request->has('admin');
        $isAdminRequest = $isAdmin || $isFromAdminPanel;
        
        $isAdminStr = $isAdminRequest ? 'admin' : 'public';
        
        $cacheKey = 'sanpham_index_' . $imageVersion . '_' . $isAdminStr . '_' . md5(json_encode($request->all()));
        
        $sanphams = Cache::remember($cacheKey, 600, function () use ($request, $imageVersion, $isAdminRequest) {
            $query = SanPham::with([
                'danhMuc',
                'thuongHieu',
                'bienThes',
                'hinhAnhs'
            ])
            ->withAvg(['reviews as rating_avg' => function ($q) {
                $q->where('trangthai', 'approved');
            }], 'danhgia')
            ->withCount(['reviews as rating_count' => function ($q) {
                $q->where('trangthai', 'approved');
            }]);

            if (!$isAdminRequest) {
                $query->where(function ($q) {
                    $q->where('trangthai', '1')->orWhere('trangthai', 1)->orWhere('trangthai', 'active');
                });
            }

            $query->orderByDesc('id_sanpham');
        
        $attributesToFilter = [
            'ram'      => 'ram',
            'cpu'      => 'cpu',
            'gpu'      => 'gpu',
            'kichthuoc' => 'kích thước',
            'dophan'   => 'độ phân giải',
            'tamnen'   => 'tấm nền',
            'pin'      => 'pin',
            'sac'      => 'sạc'
        ];

        foreach ($attributesToFilter as $param => $jsonKey) {
            if ($request->filled($param)) {
                $values = explode(',', $request->$param);
                $query->whereHas('bienThes', function ($q) use ($values) {
                    $q->where(function ($subQ) use ($values) {
                        foreach ($values as $val) {
                            $subQ->orWhere('thuoc_tinh_json', 'like', '%' . trim($val) . '%');
                        }
                    });
                });
            }
        }

            $products = $query->get();
            
            // Map to include thong_so_ky_thuat
            return $products->map(function ($p) use ($imageVersion) {
                return [
                    'id_sanpham' => $p->id_sanpham,
                    'tenSP' => $p->tenSP,
                    'SKU' => $p->SKU,
                    'hinhanh' => $p->hinhanh,
                    'trangthai' => $p->trangthai,
                    'khoiluong' => $p->khoiluong,
                    'id_danhmuc' => $p->id_danhmuc,
                    'id_thuonghieu' => $p->id_thuonghieu,
                    'thong_so_ky_thuat' => $p->thong_so_ky_thuat,
                    'updated_at' => $imageVersion,
                    'danh_muc' => $p->danhMuc,
                    'thuong_hieu' => $p->thuongHieu,
                    'hinh_anhs' => $p->hinhAnhs,
                    'bien_thes' => $p->bienThes,
                    'rating_avg' => $p->rating_avg ? round((float)$p->rating_avg, 1) : null,
                    'rating_count' => $p->rating_count ?? 0,
                ];
            });
        });

        return response()->json($sanphams);
    }

    // Gộp 4 API làm 1 để tăng tốc độ load trên môi trường Windows
    public function init(Request $request)
    {
        $cacheKey = 'sanpham_init_' . $this->sanPhamCacheVersion() . '_' . md5(json_encode($request->query()));

        $payload = Cache::remember($cacheKey, 600, function () use ($request) {
            if ($request->has('q')) {
                $sanphams = $this->search($request)->getData(true);
            } else {
                $sanphams = $this->index($request)->getData(true);
            }
            $danhmucs = app(\App\Http\Controllers\DanhMucController::class)->index()->getData(true);
            $thuonghieus = app(\App\Http\Controllers\ThuongHieuController::class)->index()->getData(true);
            $attributes = $this->attributeOptions()->getData(true);

            return [
                'products' => $sanphams,
                'categories' => $danhmucs['data'] ?? $danhmucs,
                'brands' => $thuonghieus['data'] ?? $thuonghieus,
                'attributes' => $attributes
            ];
        });

        return response()->json($payload);
    }

    // Trả về danh sách các giá trị thuộc tính có trong DB
    public function mobileHome()
    {
        $imageVersion = $this->sanPhamCacheVersion();
        $payload = Cache::remember('mobile_home_v2_' . $imageVersion, 120, function () use ($imageVersion) {
            $categories = DanhMuc::query()
                ->select('id_danhmuc', 'ten_danhmuc', 'trangthai', 'id_danhmuc_cha')
                ->orderBy('id_danhmuc')
                ->get();

            $products = SanPham::query()
                ->select(
                    'id_sanpham',
                    'tenSP',
                    'SKU',
                    'hinhanh',
                    'trangthai',
                    'id_danhmuc',
                    'id_thuonghieu',
                )
                ->with([
                    'danhMuc:id_danhmuc,ten_danhmuc,trangthai,id_danhmuc_cha',
                    'thuongHieu:id_thuonghieu,ten_thuonghieu',
                    'bienThes:id_bienthe,id_sanpham,ten_bienthe,gia,soluong,thuoc_tinh_json',
                ])
                ->orderByDesc('id_sanpham')
                ->limit(12)
                ->get()
                ->map(function ($product) use ($imageVersion) {
                    $variants = $product->bienThes
                        ->sortByDesc(fn ($variant) => (int) $variant->soluong > 0)
                        ->take(1)
                        ->values()
                        ->map(function ($variant) {
                            return [
                                'id_bienthe' => $variant->id_bienthe,
                                'ten_bienthe' => $variant->ten_bienthe,
                                'gia' => $variant->gia,
                                'soluong' => $variant->soluong,
                                'thuoc_tinh_json' => $variant->thuoc_tinh_json,
                            ];
                        });

                    return [
                        'id_sanpham' => $product->id_sanpham,
                        'tenSP' => $product->tenSP,
                        'SKU' => $product->SKU,
                        'hinhanh' => $product->hinhanh,
                        'trangthai' => $product->trangthai,
                        'id_danhmuc' => $product->id_danhmuc,
                        'id_thuonghieu' => $product->id_thuonghieu,
                        'updated_at' => $imageVersion,
                        'thong_so_ky_thuat' => [],
                        'danh_muc' => $product->danhMuc,
                        'thuong_hieu' => $product->thuongHieu,
                        'hinh_anhs' => [],
                        'bien_thes' => $variants,
                    ];
                });

            return [
                'products' => $products,
                'categories' => $categories,
            ];
        });

        return response()->json($payload);
    }

    public function attributeOptions()
    {
        $options = Cache::remember('sanpham_attribute_options', 120, function () {
            $attributeIds = [1, 2, 3, 4, 5, 6, 7, 8];

            return \App\Models\GiaTriThuocTinh::whereIn('id_thuoctinh', $attributeIds)
                ->where('trangthai', 1)
                ->orderBy('id_thuoctinh')
                ->orderBy('giatri')
                ->get()
                ->groupBy('id_thuoctinh')
                ->map(function ($items) {
                    return $items->pluck('giatri');
                });
        });

        return response()->json([
            'ram'       => $options->get(1, []),
            'cpu'       => $options->get(2, []),
            'gpu'       => $options->get(3, []),
            'kichthuoc' => $options->get(4, []),
            'dophan'    => $options->get(5, []),
            'tamnen'    => $options->get(6, []),
            'pin'       => $options->get(7, []),
            'sac'       => $options->get(8, [])
        ]);
    }

    // ===== TÌM KIẾM SẢN PHẨM =====
    // Tìm theo tên sản phẩm + tên biến thể, không trùng sản phẩm
    public function search(Request $request)
    {
        $keyword = trim($request->query('q', ''));

        if (strlen($keyword) === 0) {
            return response()->json([]);
        }

        $user = request()->user('sanctum');
        $isAdmin = $user && $user->vaitro !== 'user';
        $referer = request()->header('referer');
        $isFromAdminPanel = $referer && str_contains($referer, '/admin');
        $isAdminRequest = $isAdmin && $isFromAdminPanel;

        $isAdminStr = $isAdminRequest ? 'admin' : 'public';

        $sanphams = Cache::remember('sanpham_search_' . $this->sanPhamCacheVersion() . '_' . $isAdminStr . '_' . md5($keyword), 120, function () use ($keyword, $isAdminRequest) {
            $idsByBienThe = BienThe::where('ten_bienthe', 'LIKE', "%{$keyword}%")
                ->pluck('id_sanpham')
                ->toArray();

            $query = SanPham::with([
                'danhMuc',
                'thuongHieu',
                'bienThes',
                'hinhAnhs'
            ])
            ->withAvg(['reviews as rating_avg' => function ($q) {
                $q->where('trangthai', 'approved');
            }], 'danhgia')
            ->withCount(['reviews as rating_count' => function ($q) {
                $q->where('trangthai', 'approved');
            }]);

            if (!$isAdminRequest) {
                $query->where('trangthai', '!=', 0);
            }

            return $query->where(function ($q) use ($keyword, $idsByBienThe) {
                $q->where('tenSP', 'LIKE', "%{$keyword}%");

                if (!empty($idsByBienThe)) {
                    $q->orWhereIn('id_sanpham', $idsByBienThe);
                }
            })
            ->orderByDesc('id_sanpham')
            ->get();
        });

        return response()->json($sanphams);
    }

    public function show($id)
    {
        $cacheVersion = $this->sanPhamCacheVersion();
        $user = request()->user('sanctum');
        $isAdmin = $user && ($user->vaitro !== 'user');
        $referer = request()->header('referer');
        $isFromAdminPanel = ($referer && str_contains(strtolower($referer), '/admin')) || request()->has('admin');
        $isAdminRequest = $isAdmin || $isFromAdminPanel;

        $isAdminStr = $isAdminRequest ? 'admin' : 'public';

        $result = Cache::remember("sanpham_show_{$cacheVersion}_{$isAdminStr}_{$id}", 600, function () use ($id, $isAdminRequest, $cacheVersion) {
            $query = SanPham::with([
                'danhMuc',
                'thuongHieu',
                'hinhAnhs',
                'bienThes.comboOffers.sanPhams.bienThes'
            ]);

            if (!$isAdminRequest) {
                $query->where(function ($q) {
                    $q->where('trangthai', '1')->orWhere('trangthai', 1)->orWhere('trangthai', 'active');
                });
            }

            $sanpham = $query->find($id);

            if (!$sanpham) return null;

            $allThuocTinhs = ThuocTinh::select('id_thuoctinh', 'ten_thuoctinh')->get()->keyBy('ten_thuoctinh');

        $result = [
            'id_sanpham'     => $sanpham->id_sanpham,
            'tenSP'          => $sanpham->tenSP,
            'SKU'            => $sanpham->SKU,
            'hinhanh'        => $sanpham->hinhanh,
            'trangthai'      => $sanpham->trangthai,
            'khoiluong'      => $sanpham->khoiluong,
            'id_danhmuc'        => $sanpham->id_danhmuc,
            'id_thuonghieu'     => $sanpham->id_thuonghieu,
            'thong_so_ky_thuat' => $sanpham->thong_so_ky_thuat,
            'updated_at'        => $cacheVersion,

            'danh_muc' => $sanpham->danhMuc ? [
                'id_danhmuc'   => $sanpham->danhMuc->id_danhmuc,
                'ten_danhmuc'  => $sanpham->danhMuc->ten_danhmuc,
                'id_danhmuc_cha' => $sanpham->danhMuc->id_danhmuc_cha,
            ] : null,

            'thuong_hieu' => $sanpham->thuongHieu ? [
                'id_thuonghieu'  => $sanpham->thuongHieu->id_thuonghieu,
                'ten_thuonghieu' => $sanpham->thuongHieu->ten_thuonghieu,
            ] : null,

            'hinh_anhs' => $sanpham->hinhAnhs->map(function ($img) {
                return [
                    'duongdan' => $img->duongdan,
                    'thutu'    => $img->thutu
                ];
            })->values(),

            'bien_thes' => $sanpham->bienThes->map(function ($bt) use ($allThuocTinhs) {
                $thuocTinhJson = collect(json_decode($bt->thuoc_tinh_json ?? '[]', true))
                    ->map(function ($item) use ($allThuocTinhs) {
                        $idThuocTinh  = $item['id_thuoctinh'] ?? null;
                        $tenThuocTinh = $item['ten_thuoctinh'] ?? null;

                        if (!$idThuocTinh && $tenThuocTinh) {
                            $thuocTinh   = $allThuocTinhs->get($tenThuocTinh);
                            $idThuocTinh = $thuocTinh?->id_thuoctinh;
                        }

                        return [
                            'id_thuoctinh'  => $idThuocTinh,
                            'ten_thuoctinh' => $tenThuocTinh,
                            'giatri'        => $item['giatri'] ?? null,
                            'ma_mau'        => $item['ma_mau'] ?? ($item['hex'] ?? null),
                        ];
                    })
                    ->values();

                return [
                    'id_bienthe'  => $bt->id_bienthe,
                    'ten_bienthe' => $bt->ten_bienthe,
                    'gia'         => $bt->gia,
                    'soluong'     => $bt->soluong,
                    'hinhanh'     => $bt->hinhanh,
                    'thuoc_tinh'  => $thuocTinhJson,
                    'combo_offers' => $bt->comboOffers->filter(function ($combo) {
                        $offer = (object)[
                            'id_combo' => $combo->id_combo,
                            'trangthai' => $combo->pivot->trangthai,
                            'gioi_han_soluong' => $combo->pivot->gioi_han_soluong,
                            'da_su_dung' => $combo->pivot->da_su_dung,
                            'ngay_het_han' => $combo->pivot->ngay_het_han
                        ];
                        return \App\Http\Controllers\ComboController::isOfferValid($offer);
                    })->map(function ($combo) {
                        return [
                            'id_combo' => $combo->id_combo,
                            'ten_combo' => $combo->ten_combo,
                            'giakhuyenmai' => $combo->pivot->loai_uudai === 'free' ? 0.00 : ($combo->pivot->giakhuyenmai_override ?? $combo->giakhuyenmai),
                            'mota_uudai' => $combo->pivot->mota_uudai,
                            'loai_uudai' => $combo->pivot->loai_uudai,
                            'hinhanh' => $combo->hinhanh ? asset('storage/' . $combo->hinhanh) : null,
                            'products' => $combo->sanPhams->map(function ($p) {
                                return [
                                    'id_sanpham' => $p->id_sanpham,
                                    'tenSP' => $p->tenSP,
                                    'hinhanh' => $p->hinhanh ? asset('storage/' . $p->hinhanh) : null,
                                    'bien_thes' => $p->bienThes->map(function ($v) {
                                        return [
                                            'id_bienthe' => $v->id_bienthe,
                                            'ten_bienthe' => $v->ten_bienthe,
                                            'gia' => $v->gia,
                                            'soluong' => $v->soluong,
                                        ];
                                    })
                                ];
                            })
                        ];
                    })
                ];
            })->values()
            ];

            return $result;
        });

        if (!$result) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm.'
            ], 404);
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_danhmuc'     => 'required|integer|exists:danhmuc,id_danhmuc',
            'id_thuonghieu'  => 'required|integer|exists:thuonghieu,id_thuonghieu',
            'tenSP'          => 'required|string|max:255',
            'trangthai'      => 'required',
            'hinhanh'           => 'nullable|string',
            'khoiluong'         => 'nullable|numeric',
            'thong_so_ky_thuat' => 'nullable|array',

            'bienthes'                => 'nullable|array',
            'bienthes.*.ten_bienthe'  => 'nullable|string|max:255',
            'bienthes.*.gia'          => 'required_with:bienthes|numeric|min:0',
            'bienthes.*.soluong'      => 'required_with:bienthes|integer|min:0',

            'hinh_anhs'               => 'nullable|array',
            'hinh_anhs.*.duongdan'    => 'required_with:hinh_anhs|string',
            'hinh_anhs.*.thutu'       => 'nullable|integer|min:0',
            'hinh_anhs.*.macdinh'     => 'nullable|integer|in:0,1',
        ], [
            'id_danhmuc.required'    => 'Vui lòng chọn danh mục.',
            'id_thuonghieu.required' => 'Vui lòng chọn thương hiệu.',
            'tenSP.required'         => 'Tên sản phẩm không được để trống.',
        ]);

        DB::beginTransaction();

        try {
            $sku       = $this->generateUniqueSKU($request->id_thuonghieu);
            $coverPath = null;
            if ($request->filled('hinhanh')) {
                $coverPath = str_starts_with($request->hinhanh, 'data:image')
                    ? ImageHelper::saveBase64Image($request->hinhanh, 'uploads/sanpham')
                    : ImageHelper::normalizePublicPath($request->hinhanh);
            }

            $sanpham = SanPham::create([
                'id_danhmuc'    => $request->id_danhmuc,
                'id_thuonghieu' => $request->id_thuonghieu,
                'tenSP'         => $request->tenSP,
                'SKU'           => $sku,
                'trangthai'         => $request->trangthai,
                'hinhanh'           => $coverPath,
                'khoiluong'         => $request->khoiluong,
                'thong_so_ky_thuat' => $request->thong_so_ky_thuat,
            ]);

            if ($request->has('hinh_anhs') && is_array($request->hinh_anhs)) {
                foreach ($request->hinh_anhs as $index => $ha) {
                    $rawImagePath = $ha['duongdan'] ?? null;
                    $imagePath = $rawImagePath && str_starts_with($rawImagePath, 'data:image')
                        ? ImageHelper::saveBase64Image($rawImagePath, 'uploads/sanpham')
                        : ImageHelper::normalizePublicPath($rawImagePath);
                    if ($imagePath) {
                        BienTheHinhAnh::create([
                            'id_sanpham' => $sanpham->id_sanpham,
                            'duongdan'   => $imagePath,
                            'thutu'      => $ha['thutu'] ?? $index,
                        ]);
                    }
                }
            }

            if ($request->has('bienthes') && is_array($request->bienthes)) {
                foreach ($request->bienthes as $bt) {
                    BienThe::create([
                        'id_sanpham'      => $sanpham->id_sanpham,
                        'ten_bienthe'     => $bt['ten_bienthe'] ?? null,
                        'gia'             => $bt['gia'],
                        'soluong'         => $bt['soluong'],
                        'thuoc_tinh_json' => json_encode($bt['thuoc_tinh'] ?? [], JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }

            DB::commit();

            $sanpham = SanPham::with(['danhMuc', 'thuongHieu', 'bienThes', 'hinhAnhs'])
                ->find($sanpham->id_sanpham);

            $this->clearSanPhamCaches($sanpham->id_sanpham);

            return response()->json([
                'message' => 'Thêm sản phẩm thành công.',
                'data'    => $sanpham
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Không thêm được sản phẩm.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $sanpham = SanPham::find($id);

        if (!$sanpham) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm.'], 404);
        }

        $request->validate([
            'id_danhmuc'     => 'required|integer|exists:danhmuc,id_danhmuc',
            'id_thuonghieu'  => 'required|integer|exists:thuonghieu,id_thuonghieu',
            'tenSP'          => 'required|string|max:255',
            'trangthai'      => 'required',
            'hinhanh'           => 'nullable|string',
            'khoiluong'         => 'nullable|numeric',
            'thong_so_ky_thuat' => 'nullable|array',

            'hinh_anhs'            => 'nullable|array',
            'hinh_anhs.*.duongdan' => 'required_with:hinh_anhs|string',
            'hinh_anhs.*.thutu'    => 'nullable|integer|min:0',

            'bienthes'                => 'nullable|array',
            'bienthes.*.id_bienthe'   => 'nullable|integer',
            'bienthes.*.ten_bienthe'  => 'nullable|string|max:255',
            'bienthes.*.gia'          => 'required_with:bienthes|numeric|min:0',
            'bienthes.*.soluong'      => 'required_with:bienthes|integer|min:0',
        ], [
            'id_danhmuc.required'    => 'Vui lòng chọn danh mục.',
            'id_thuonghieu.required' => 'Vui lòng chọn thương hiệu.',
            'tenSP.required'         => 'Tên sản phẩm không được để trống.',
            'khoiluong.numeric'      => 'Khối lượng phải là số.',
        ]);

        DB::beginTransaction();

        try {
            $coverPath = $sanpham->hinhanh;

            if ($request->has('hinhanh')) {
                if (blank($request->hinhanh)) {
                    $coverPath = null;
                } elseif (str_starts_with($request->hinhanh, 'data:image')) {
                    $coverPath = ImageHelper::saveBase64Image($request->hinhanh, 'uploads/sanpham');
                } else {
                    $coverPath = ImageHelper::normalizePublicPath($request->hinhanh);
                }
            }

            $sanpham->update([
                'id_danhmuc'    => $request->id_danhmuc,
                'id_thuonghieu' => $request->id_thuonghieu,
                'tenSP'         => $request->tenSP,
                'trangthai'         => $request->trangthai,
                'hinhanh'           => $coverPath,
                'khoiluong'         => $request->khoiluong,
                'thong_so_ky_thuat' => $request->thong_so_ky_thuat,
            ]);

            BienTheHinhAnh::where('id_sanpham', $sanpham->id_sanpham)->delete();

            if ($request->has('hinh_anhs') && is_array($request->hinh_anhs)) {
                foreach ($request->hinh_anhs as $index => $ha) {
                    $imagePath = $ha['duongdan'] ?? null;
                    if (!$imagePath) continue;

                    if (str_starts_with($imagePath, 'data:image')) {
                        $imagePath = ImageHelper::saveBase64Image($imagePath, 'uploads/sanpham');
                    } else {
                        $imagePath = ImageHelper::normalizePublicPath($imagePath);
                    }

                    if ($imagePath) {
                        BienTheHinhAnh::create([
                            'id_sanpham' => $sanpham->id_sanpham,
                            'duongdan'   => $imagePath,
                            'thutu'      => $ha['thutu'] ?? $index,
                        ]);
                    }
                }
            }

            $existingIds = BienThe::where('id_sanpham', $sanpham->id_sanpham)->pluck('id_bienthe')->toArray();

            $incomingIds = collect($request->bienthes)
                ->pluck('id_bienthe')
                ->filter()
                ->map(fn($id) => (int)$id)
                ->toArray();

            // Xóa những biến thể không được gửi lên (đã bị xóa ở giao diện)
            $idsToDelete = array_diff($existingIds, $incomingIds);
            if (!empty($idsToDelete)) {
                BienThe::whereIn('id_bienthe', $idsToDelete)->delete();
            }

            if ($request->has('bienthes') && is_array($request->bienthes)) {
                foreach ($request->bienthes as $bt) {
                    if (!empty($bt['id_bienthe']) && in_array((int)$bt['id_bienthe'], $existingIds)) {
                        // Cập nhật tại chỗ
                        BienThe::where('id_bienthe', $bt['id_bienthe'])->update([
                            'ten_bienthe'     => $bt['ten_bienthe'] ?? null,
                            'gia'             => $bt['gia'],
                            'soluong'         => $bt['soluong'],
                            'thuoc_tinh_json' => json_encode($bt['thuoc_tinh'] ?? [], JSON_UNESCAPED_UNICODE),
                        ]);
                    } else {
                        // Tạo mới
                        BienThe::create([
                            'id_sanpham'      => $sanpham->id_sanpham,
                            'ten_bienthe'     => $bt['ten_bienthe'] ?? null,
                            'gia'             => $bt['gia'],
                            'soluong'         => $bt['soluong'],
                            'thuoc_tinh_json' => json_encode($bt['thuoc_tinh'] ?? [], JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }
            }

            DB::commit();

            $sanpham = SanPham::with(['danhMuc', 'thuongHieu', 'bienThes', 'hinhAnhs'])
                ->find($sanpham->id_sanpham);
            $this->clearSanPhamCaches($id);

            return response()->json([
                'message' => 'Cập nhật sản phẩm thành công.',
                'data'    => $sanpham
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Không cập nhật được sản phẩm.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $sanpham = SanPham::find($id);

        if (!$sanpham) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm.'], 404);
        }

        DB::beginTransaction();
        try {
            // Xóa dữ liệu phụ thuộc trước để tránh lỗi ràng buộc khóa ngoại
            BienThe::where('id_sanpham', $sanpham->id_sanpham)->delete();
            BienTheHinhAnh::where('id_sanpham', $sanpham->id_sanpham)->delete();
            $sanpham->delete();

            DB::commit();
            $this->clearSanPhamCaches($id);

            return response()->json(['message' => 'Xóa sản phẩm thành công.']);
        } catch (QueryException $e) {
            DB::rollBack();
            // SQLSTATE 23000: lỗi ràng buộc khóa ngoại
            if (($e->errorInfo[0] ?? null) === '23000') {
                return response()->json([
                    'message' => 'Không thể xóa sản phẩm vì đang có dữ liệu liên quan (đơn hàng/chi tiết khác).'
                ], 409);
            }
            return response()->json([
                'message' => 'Không xóa được sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Không xóa được sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function clearSanPhamCaches(?int $id = null): void
    {
        Cache::put('sanpham_cache_bust', (string) microtime(true));

        if ($id) {
            Cache::forget("sanpham_show_{$id}");
            Cache::forget("sanpham_show_" . $this->sanPhamCacheVersion() . "_{$id}");
        }

        // Danh sách admin/frontend thường gọi /sanpham không query
        Cache::forget('sanpham_index_' . md5(json_encode([])));
        Cache::forget('mobile_home_v2');
        Cache::forget('sanpham_attribute_options');
    }

    private function sanPhamCacheVersion(): string
    {
        $cacheBust = Cache::get('sanpham_cache_bust', '0');
        $count = SanPham::count();

        return md5($cacheBust . '|' . $count);
    }

    private function generateUniqueSKU($id_thuonghieu)
    {
        do {
            $sku = 'SP-' . $id_thuonghieu . '-' . strtoupper(Str::random(6));
        } while (SanPham::where('SKU', $sku)->exists());

        return $sku;
    }

    public function importStock(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.id_bienthe' => 'required|exists:bienthe,id_bienthe',
            'updates.*.soluong' => 'nullable|integer|min:0',
            'updates.*.gia' => 'nullable|numeric|min:0',
        ]);

        $successCount = 0;
        $productIds = [];
        foreach ($request->updates as $item) {
            $updateData = [];
            if (isset($item['soluong']) && $item['soluong'] !== '') {
                $updateData['soluong'] = $item['soluong'];
            }
            if (isset($item['gia']) && $item['gia'] !== '') {
                $updateData['gia'] = $item['gia'];
            }

            if (!empty($updateData)) {
                $bienThe = BienThe::find($item['id_bienthe']);
                if ($bienThe) {
                    $bienThe->update($updateData);
                    $productIds[$bienThe->id_sanpham] = true;
                    $successCount++;
                }
            }
        }

        foreach (array_keys($productIds) as $id_sanpham) {
            $this->clearSanPhamCaches($id_sanpham);
        }

        return response()->json([
            'message' => "Cập nhật thành công $successCount dòng dữ liệu.",
            'count' => $successCount
        ]);
    }

    public function exportInventory()
    {
        $data = DB::table('sanpham')
            ->join('bienthe', 'sanpham.id_sanpham', '=', 'bienthe.id_sanpham')
            ->leftJoin('danhmuc', 'sanpham.id_danhmuc', '=', 'danhmuc.id_danhmuc')
            ->leftJoin('thuonghieu', 'sanpham.id_thuonghieu', '=', 'thuonghieu.id_thuonghieu')
            ->select(
                'sanpham.id_sanpham',
                'bienthe.id_bienthe',
                'sanpham.SKU as sku',
                'sanpham.tenSP',
                'sanpham.hinhanh as anhdaidien',
                'danhmuc.ten_danhmuc',
                'thuonghieu.ten_thuonghieu as tenthuonghieu',
                'sanpham.khoiluong',
                'bienthe.ten_bienthe',
                'bienthe.gia',
                'bienthe.soluong',
                'sanpham.thong_so_ky_thuat',
                'bienthe.thuoc_tinh_json'
            )
            ->orderBy('sanpham.id_sanpham')
            ->orderBy('bienthe.id_bienthe')
            ->get();

        // Attach album gallery images for each product
        $dataArray = json_decode(json_encode($data), true);
        $productIds = array_unique(array_column($dataArray, 'id_sanpham'));

        $galleryImages = DB::table('bienthe_hinhanh')
            ->whereIn('id_sanpham', $productIds)
            ->get()
            ->groupBy('id_sanpham');

        foreach ($dataArray as &$row) {
            $pId = $row['id_sanpham'];
            if (isset($galleryImages[$pId])) {
                $urls = $galleryImages[$pId]->pluck('duongdan')->toArray();
                $row['hinh_anhs_str'] = implode(',', $urls);
            } else {
                $row['hinh_anhs_str'] = '';
            }
        }

        return response()->json($dataArray);
    }

    public function suggestImages(Request $request)
    {
        $keyword = trim($request->input('keyword', ''));
        $brand = trim($request->input('brand', ''));
        $category = trim($request->input('category', ''));

        if (!$keyword) {
            return response()->json(['images' => []]);
        }

        // 1. Clean slashes & noise year/suffix tags
        $clean = preg_replace('/[\/\\\]/u', ' ', $keyword);
        $clean = preg_replace('/\b(2024|2025|2026|chính hãng|tiêu chuẩn|mới|fullbox|tạo mới)\b/iu', '', $clean);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        if (strlen($clean) < 3) {
            $clean = trim($keyword);
        }

        $catClean = preg_replace('/[\/\\\]/u', ' ', $category);
        $catClean = trim(preg_replace('/\s+/', ' ', $catClean));
        $brandClean = trim($brand);

        // 2. Extract pure model name without generic prefixes
        $modelOnly = preg_replace('/\b(laptop|chuột|bàn phím|máy tính|phụ kiện)\b/iu', '', $clean);
        $modelOnly = trim(preg_replace('/\s+/', ' ', $modelOnly));
        if (strlen($modelOnly) < 3) {
            $modelOnly = $clean;
        }

        $lowerCat = strtolower($catClean);
        $lowerName = strtolower($clean);

        // Clean Noise & Extract Precise Hardware Model Name
        $clean = preg_replace('/[\/\\\]/u', ' ', $keyword);
        $clean = preg_replace('/\b(2024|2025|2026|chính hãng|tiêu chuẩn|mới|fullbox|tạo mới|\d+\s*inch|\d+gb|\d+tb|ram.*|ssd.*|cpu.*)\b/iu', '', $clean);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        $modelOnly = preg_replace('/\b(laptop|chuột|bàn phím)\b/iu', '', $clean);
        $modelOnly = trim(preg_replace('/\s+/', ' ', $modelOnly));

        $isLaptop = str_contains($lowerCat, 'laptop') || str_contains($lowerName, 'laptop') || str_contains($lowerName, 'macbook') || str_contains($lowerName, 'strix') || str_contains($lowerName, 'xps') || str_contains($lowerName, 'legion') || str_contains($lowerName, 'tuf');
        $isMouse = str_contains($lowerCat, 'chuột') || str_contains($lowerName, 'chuột') || str_contains($lowerName, 'logitech') || str_contains($lowerName, 'mouse');
        $isKeyboard = str_contains($lowerCat, 'bàn phím') || str_contains($lowerName, 'bàn phím') || str_contains($lowerName, 'keyboard') || str_contains($lowerName, 'akko');

        if ($isLaptop) {
            $searchQuery = $modelOnly . ' laptop';
        } elseif ($isMouse) {
            $searchQuery = $modelOnly . ' mouse';
        } elseif ($isKeyboard) {
            $searchQuery = $modelOnly . ' keyboard';
        } else {
            $searchQuery = $modelOnly;
        }

        // 2. Query existing DB product images
        $dbImages = DB::table('sanpham')
            ->where('tenSP', 'LIKE', '%' . $clean . '%')
            ->whereNotNull('hinhanh')
            ->where('hinhanh', '!=', '')
            ->pluck('hinhanh')
            ->take(4)
            ->toArray();

        // 3. Real Web Product Image Crawler
        $webImages = [];
        $badWords = [
            'coloring', 'sketch', 'drawing', 'line-art', 'outline', 'page', 'panda', 
            'kung-fu', 'cartoon', 'movie', 'character', 'wallpaper', 'gamepad', 
            'controller', 'joystick', 'xbox', 'neon', 'pluspng', 'pngimg', 'publicdomain', 
            'rawpixel', 'freepik', 'vecteezy', 'apple-logo', 'svg', 'park', 
            'campus', 'fruit', 'apple-fruit', 'tree', 'building', 'storefront', 'architecture', 
            'sleepyhollow', 'dodo', 'bird', 'vector', 'poster', 'salad', 'food', 'recipe', 
            'malayalam', 'thepublive', 'indian-express', 'journaldesfemmes', 'bendo', 
            'lazada', 'naijagospel', 'pikapika', 'prom', 'wedding', 'actor', 'actress', 
            'celebrity', 'portrait', 'dish', 'kebersihan', 'iconsiam', 'apple-iconsiam',
            'windowsloop', 'task-scheduler', 'trunks', 'kids', 'clothing', 'apparel'
        ];

        $allowedDomains = [
            'cellphones.com.vn', 'gearvn.com', 'hstatic.net', 'shopee', 'susercontent.com',
            'tgdd.vn', 'thegioididong.com', 'topzone.vn', 'fptshop.com.vn',
            'asus.com', 'dlcdnrog.asus.com', 'apple.com', 'logitech.com', 'logitechg.com',
            'dell.com', 'lenovo.com', 'acer.com', 'msi.com'
        ];

        $crawlQuery = function($q) use ($badWords, $allowedDomains, $isLaptop) {
            $imgs = [];
            try {
                $searchUrl = "https://www.bing.com/images/search?q=" . urlencode($q);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $searchUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
                curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $html = curl_exec($ch);
                curl_close($ch);

                if ($html && preg_match_all('/murl&quot;:&quot;(https?:\/\/[^&]+)&quot;/i', $html, $matches)) {
                    foreach ($matches[1] as $url) {
                        // Unescape backslashes & HTML entities
                        $cleanUrl = stripslashes(html_entity_decode($url));

                        // Extract inner direct URL if wrapped by CellphoneS CDN proxy (plain/https://...)
                        if (preg_match('/plain\/(https?:\/\/[^\s"]+)/i', $cleanUrl, $m)) {
                            $cleanUrl = $m[1];
                        }

                        $u = strtolower($cleanUrl);

                        // STRICT DOMAIN CHECK: MUST originate directly from Shopee, GearVN, CellphoneS, TGDD, FPTShop, or Official Brand site
                        $isAllowed = false;
                        foreach ($allowedDomains as $d) {
                            if (str_contains($u, $d)) {
                                $isAllowed = true;
                                break;
                            }
                        }

                        if (!$isAllowed) {
                            continue; // HARD DROP ALL THIRD PARTY WEBSITES (blogspot, wikimedia, etc.)
                        }

                        $isBad = false;
                        foreach ($badWords as $bw) {
                            if (str_contains($u, $bw)) {
                                $isBad = true;
                                break;
                            }
                        }

                        if ($isLaptop) {
                            if (str_contains($u, 'watch') || str_contains($u, 'phone') || str_contains($u, 'fruit') || str_contains($u, 'apple-store')) {
                                $isBad = true;
                            }
                        }

                        if (!$isBad) {
                            $imgs[] = $cleanUrl;
                        }
                    }
                }
            } catch (\Exception $e) {}
            return $imgs;
        };

        // Multi-pass queries targeting Shopee, CellphoneS, GearVN
        $pass1 = $crawlQuery('site:shopee.vn ' . $modelOnly);
        $pass2 = $crawlQuery('site:cellphones.com.vn ' . $modelOnly);
        $pass3 = $crawlQuery('site:gearvn.com ' . $modelOnly);
        $pass4 = $crawlQuery($searchQuery);

        $crawled = array_values(array_unique(array_merge($pass1, $pass2, $pass3, $pass4)));

        // Smart Scoring & Ranking: Official brand & retailer sites first
        $scoreImage = function($url) use ($lowerCat, $lowerName) {
            $u = strtolower($url);
            $score = 0;

            if (str_contains($u, 'dell.com') || str_contains($u, 'asus.com') || str_contains($u, 'apple.com') || str_contains($u, 'logitech')) $score += 150;
            if (str_contains($u, 'cellphones.com.vn') || str_contains($u, 'gearvn.com') || str_contains($u, 'hstatic.net')) $score += 140;
            if (str_contains($u, 'tgdd.vn') || str_contains($u, 'thegioididong') || str_contains($u, 'fptshop')) $score += 130;
            if (str_contains($u, 'laptopmedia.com') || str_contains($u, 'notebookcheck')) $score += 100;
            if (str_contains($u, 'susercontent.com') || str_contains($u, 'shopee')) $score += 90;

            if (str_contains($u, 'png') || str_contains($u, 'transparent') || str_contains($u, 'white-bg')) $score += 30;

            return $score;
        };

        usort($crawled, function($a, $b) use ($scoreImage) {
            return $scoreImage($b) <=> $scoreImage($a);
        });

        $webImages = $crawled;

        $allImages = array_values(array_unique(array_merge($dbImages, $webImages)));
        $allImages = array_filter($allImages, function ($img) {
            return !empty($img) && !str_contains($img, 'placeholder');
        });

        // Paginate 6 images per page
        $page = max(1, (int)$request->input('page', 1));
        $perPage = 6;
        $allImagesList = array_values($allImages);
        $totalFound = count($allImagesList);
        $offset = ($page - 1) * $perPage;

        if ($totalFound > 0 && $offset >= $totalFound) {
            $offset = $offset % $totalFound;
        }

        $pagedImages = array_slice($allImagesList, $offset, $perPage);
        if (empty($pagedImages) && !empty($allImagesList)) {
            $pagedImages = array_slice($allImagesList, 0, $perPage);
        }

        return response()->json([
            'keyword' => $keyword,
            'clean_keyword' => $clean,
            'brand' => $brandClean,
            'category' => $catClean,
            'full_query' => $searchQuery,
            'search_query' => $searchQuery,
            'page' => $page,
            'total_found' => $totalFound,
            'images' => $pagedImages
        ]);
    }

    public function uploadExcelImages(Request $request)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:10240'
        ]);

        $uploadedUrls = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('uploads/sanpham', $filename, 'public');
                $uploadedUrls[] = asset('storage/' . $path);
            }
        }

        return response()->json([
            'message' => 'Tải ảnh lên thành công',
            'urls' => $uploadedUrls
        ]);
    }

    public function importBulk(Request $request)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json(['message' => 'Dữ liệu nhập không được để trống'], 400);
        }

        $createdCount = 0;
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            // Group items by composite product identifier (product_id + product_name_slug)
            $grouped = [];
            foreach ($items as $item) {
                $pNameSlug = !empty($item['tenSP']) ? Str::slug(trim($item['tenSP'])) : 'row_' . ($item['stt'] ?? rand(1, 9999));
                $pIdPrefix = !empty($item['product_id']) ? 'pid_' . trim($item['product_id']) . '_' : '';
                $groupKey = $pIdPrefix . $pNameSlug;

                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [];
                }
                $grouped[$groupKey][] = $item;
            }

            foreach ($grouped as $groupKey => $variantRows) {
                $firstRow = $variantRows[0];
                $productName = trim($firstRow['tenSP'] ?? '');

                if (!$productName) continue;

                // Category lookup or AUTO-CREATE if not exists
                $catId = null;
                $catName = trim($firstRow['ten_danhmuc'] ?? '');
                if (!empty($firstRow['id_danhmuc'])) {
                    $catId = $firstRow['id_danhmuc'];
                } elseif ($catName) {
                    $catObj = DB::table('danhmuc')->where('ten_danhmuc', $catName)->first();
                    if ($catObj) {
                        $catId = $catObj->id_danhmuc;
                    } else {
                        // Auto-create new category
                        $catId = DB::table('danhmuc')->insertGetId([
                            'ten_danhmuc' => $catName,
                            'trangthai' => 1
                        ]);
                    }
                }
                if (!$catId) {
                    $catObj = DB::table('danhmuc')->first();
                    $catId = $catObj ? $catObj->id_danhmuc : 1;
                }

                // Brand lookup or AUTO-CREATE if not exists
                $brandId = null;
                $brandName = trim($firstRow['tenthuonghieu'] ?? '');
                if (!empty($firstRow['id_thuonghieu'])) {
                    $brandId = $firstRow['id_thuonghieu'];
                } elseif ($brandName) {
                    $brandObj = DB::table('thuonghieu')->where('ten_thuonghieu', $brandName)->first();
                    if ($brandObj) {
                        $brandId = $brandObj->id_thuonghieu;
                    } else {
                        // Auto-create new brand
                        $brandId = DB::table('thuonghieu')->insertGetId([
                            'ten_thuonghieu' => $brandName,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }

                // Fast helper: preserve raw image URL string directly
                $saveRemoteImg = function ($url) {
                    return trim($url);
                };

                // Main image URL & gallery images processing
                $mainImgUrl = trim($firstRow['anhdaidien'] ?? ($firstRow['hinhanh'] ?? ''));
                $galleryImgUrls = !empty($firstRow['hinh_anhs_str']) ? preg_split('/[,;\n\r]+/', $firstRow['hinh_anhs_str']) : [];
                $galleryImgUrls = array_values(array_filter(array_map('trim', $galleryImgUrls)));

                $localMainImg = $mainImgUrl ? $saveRemoteImg($mainImgUrl) : null;

                // Check if product exists by SKU, ID, or (Same Name + Same Tech Specs)
                $existingProduct = null;
                if (!empty($firstRow['sku'])) {
                    $existingProduct = SanPham::where('SKU', trim($firstRow['sku']))->first();
                }
                if (!$existingProduct && !empty($firstRow['product_id']) && is_numeric($firstRow['product_id'])) {
                    $existingProduct = SanPham::find($firstRow['product_id']);
                }
                if (!$existingProduct) {
                    $incomingSpecs = trim($firstRow['thong_so_ky_thuat'] ?? '');
                    if ($incomingSpecs) {
                        // If same name AND same specs -> treat as existing product to update
                        $existingProduct = SanPham::where('tenSP', $productName)
                            ->where('thong_so_ky_thuat', $incomingSpecs)
                            ->first();
                    } else {
                        // If no specs provided in Excel -> match by name
                        $existingProduct = SanPham::where('tenSP', $productName)->first();
                    }
                }

                $specsJson = $this->parseTechSpecsToJson($firstRow['thong_so_ky_thuat'] ?? '');

                if ($existingProduct) {
                    $existingProduct->update([
                        'tenSP' => $productName,
                        'id_danhmuc' => $catId,
                        'id_thuonghieu' => $brandId,
                        'khoiluong' => !empty($firstRow['khoiluong']) ? (float)$firstRow['khoiluong'] : 1.5,
                        'thong_so_ky_thuat' => $specsJson ?: $existingProduct->thong_so_ky_thuat,
                        'hinhanh' => $localMainImg ?: $existingProduct->hinhanh,
                    ]);
                    $productId = $existingProduct->id_sanpham;
                    $updatedCount++;
                } else {
                    $sku = !empty($firstRow['sku']) ? trim($firstRow['sku']) : 'SKU-' . strtoupper(Str::random(6));
                    $productId = DB::table('sanpham')->insertGetId([
                        'tenSP' => $productName,
                        'SKU' => $sku,
                        'id_danhmuc' => $catId,
                        'id_thuonghieu' => $brandId,
                        'khoiluong' => !empty($firstRow['khoiluong']) ? (float)$firstRow['khoiluong'] : 1.5,
                        'thong_so_ky_thuat' => $specsJson ?: '',
                        'hinhanh' => $localMainImg ?: 'uploads/products/default.jpg',
                        'trangthai' => 1
                    ]);
                    $createdCount++;
                }

                // Insert / update gallery images
                if (!empty($galleryImgUrls)) {
                    foreach ($galleryImgUrls as $gUrl) {
                        $gUrl = trim($gUrl);
                        if ($gUrl) {
                            $localGUrl = $saveRemoteImg($gUrl);
                            $exists = DB::table('bienthe_hinhanh')
                                ->where('id_sanpham', $productId)
                                ->where('duongdan', $localGUrl)
                                ->exists();

                            if (!$exists) {
                                DB::table('bienthe_hinhanh')->insert([
                                    'id_sanpham' => $productId,
                                    'duongdan' => $localGUrl,
                                    'thutu' => 0
                                ]);
                            }
                        }
                    }
                }

                // Create or update variant rows for this product
                foreach ($variantRows as $vRow) {
                    $vName = trim($vRow['ten_bienthe'] ?? 'Cấu hình tiêu chuẩn');
                    $vPrice = isset($vRow['gia']) ? (float)$vRow['gia'] : 0;
                    $vStock = isset($vRow['soluong']) ? (int)$vRow['soluong'] : 0;

                    $parsedV = $this->parseVariantStringToJson($vName);

                    // If variant ID exists, update
                    if (!empty($vRow['id_bienthe'])) {
                        DB::table('bienthe')->where('id_bienthe', $vRow['id_bienthe'])->update([
                            'ten_bienthe' => $parsedV['name'],
                            'gia' => $vPrice,
                            'soluong' => $vStock,
                            'thuoc_tinh_json' => $parsedV['json']
                        ]);
                    } else {
                        // Check if variant with exact name exists under product
                        $existingV = DB::table('bienthe')
                            ->where('id_sanpham', $productId)
                            ->where(function($q) use ($vName, $parsedV) {
                                $q->where('ten_bienthe', $vName)->orWhere('ten_bienthe', $parsedV['name']);
                            })
                            ->first();

                        if ($existingV) {
                            DB::table('bienthe')->where('id_bienthe', $existingV->id_bienthe)->update([
                                'ten_bienthe' => $parsedV['name'],
                                'gia' => $vPrice,
                                'soluong' => $vStock,
                                'thuoc_tinh_json' => $parsedV['json'] ?: $existingV->thuoc_tinh_json
                            ]);
                        } else {
                            DB::table('bienthe')->insert([
                                'id_sanpham' => $productId,
                                'ten_bienthe' => $parsedV['name'],
                                'gia' => $vPrice,
                                'soluong' => $vStock,
                                'thuoc_tinh_json' => $parsedV['json']
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            // Clear cache
            Cache::flush();

            return response()->json([
                'message' => "Nhập Excel thành công! Đã tạo mới $createdCount sản phẩm, cập nhật $updatedCount sản phẩm.",
                'created_count' => $createdCount,
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi lưu dữ liệu Excel: ' . $e->getMessage()], 500);
        }
    }

    private function getOrCreateAttributeId($name)
    {
        $name = trim($name);
        if (!$name) return "1";

        $existing = DB::table('thuoctinh')
            ->whereRaw('LOWER(ten_thuoctinh) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return (string)$existing->id_thuoctinh;
        }

        // Tự động tạo thuộc tính mới vào bảng thuoctinh nếu chưa tồn tại
        try {
            $newId = DB::table('thuoctinh')->insertGetId([
                'ten_thuoctinh' => $name,
                'id_nhom' => 1,
                'trangthai' => 1
            ]);
            return (string)$newId;
        } catch (\Exception $e) {
            return "1";
        }
    }

    private function parseVariantStringToJson($variantStr)
    {
        $variantStr = trim($variantStr);
        if (!$variantStr) return ['name' => 'Cấu hình tiêu chuẩn', 'json' => null];

        if (str_starts_with($variantStr, '[')) {
            return ['name' => $variantStr, 'json' => $variantStr];
        }

        $parts = array_map('trim', explode('/', $variantStr));
        if (count($parts) === 1) {
            $parts = array_map('trim', explode('-', $variantStr));
        }

        $attrList = [];
        $nameParts = [];

        foreach ($parts as $p) {
            $p = trim($p);
            if (!$p) continue;
            $nameParts[] = $p;

            if (preg_match('#^(\d+\s*(GB|TB|MB))$#i', $p)) {
                $val = strtoupper($p);
                $type = (str_contains($val, 'TB') || (int)$val >= 128) ? 'Dung lượng' : 'RAM';
                $attrList[] = [
                    'id_thuoctinh' => $this->getOrCreateAttributeId($type),
                    'ten_thuoctinh' => $type,
                    'giatri' => $p,
                    'hex' => null
                ];
            } elseif (preg_match('#(Intel|AMD|Apple|Ryzen|Core|i3|i5|i7|i9|M1|M2|M3|M4|Snapdragon)#i', $p)) {
                $attrList[] = [
                    'id_thuoctinh' => $this->getOrCreateAttributeId('CPU'),
                    'ten_thuoctinh' => 'CPU',
                    'giatri' => $p,
                    'hex' => null
                ];
            } elseif (preg_match('#(Màu|Đen|Trắng|Vàng|Xám|Bạc|Platinum|Graphite|Gold|Silver|Stealth|Eclipse|Onyx|Blue|Red)#i', $p)) {
                $cleanColor = preg_replace('#^Màu\s+#i', '', $p);
                $attrList[] = [
                    'id_thuoctinh' => $this->getOrCreateAttributeId('Màu sắc'),
                    'ten_thuoctinh' => 'Màu sắc',
                    'giatri' => $cleanColor,
                    'hex' => null
                ];
            } else {
                $attrList[] = [
                    'id_thuoctinh' => $this->getOrCreateAttributeId('Thuộc tính'),
                    'ten_thuoctinh' => 'Thuộc tính',
                    'giatri' => $p,
                    'hex' => null
                ];
            }
        }

        $formattedName = count($nameParts) > 0 ? implode(' - ', $nameParts) : $variantStr;
        $jsonStr = count($attrList) > 0 ? json_encode($attrList, JSON_UNESCAPED_UNICODE) : null;

        return [
            'name' => $formattedName,
            'json' => $jsonStr
        ];
    }

    private function parseTechSpecsToJson($specStr)
    {
        $specStr = trim($specStr);
        if (!$specStr) return null;

        if (str_starts_with($specStr, '[')) {
            return $specStr;
        }

        $parts = preg_split('#[|;\n\r]+#', $specStr);
        $specList = [];

        foreach ($parts as $p) {
            $p = trim($p);
            if (!$p) continue;

            if (str_contains($p, ':')) {
                list($key, $val) = explode(':', $p, 2);
                $key = trim($key);
                $val = trim($val);
                $specList[] = [
                    'id_thuoctinh' => $this->getOrCreateAttributeId($key),
                    'ten_thuoctinh' => $key,
                    'giatri' => $val
                ];
            } else {
                $specList[] = [
                    'id_thuoctinh' => $this->getOrCreateAttributeId('Thông số'),
                    'ten_thuoctinh' => 'Thông số',
                    'giatri' => $p
                ];
            }
        }

        return count($specList) > 0 ? json_encode($specList, JSON_UNESCAPED_UNICODE) : $specStr;
    }
}
