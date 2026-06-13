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

class SanPhamController extends Controller
{
    public function index(Request $request)
    {
        $imageVersion = $this->sanPhamCacheVersion();
        $cacheKey = 'sanpham_index_' . $imageVersion . '_' . md5(json_encode($request->all()));
        
        $sanphams = Cache::remember($cacheKey, 600, function () use ($request, $imageVersion) {
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
            }])
            ->orderByDesc('id_sanpham');
        
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
        if ($request->has('q')) {
            $sanphams = $this->search($request)->getData(true);
        } else {
            $sanphams = $this->index($request)->getData(true);
        }
        $danhmucs = app(\App\Http\Controllers\DanhMucController::class)->index()->getData(true);
        $thuonghieus = app(\App\Http\Controllers\ThuongHieuController::class)->index()->getData(true);
        $attributes = $this->attributeOptions()->getData(true);

        return response()->json([
            'products' => $sanphams,
            'categories' => $danhmucs['data'] ?? $danhmucs,
            'brands' => $thuonghieus['data'] ?? $thuonghieus,
            'attributes' => $attributes
        ]);
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
                ->map(function ($product) {
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

        $sanphams = Cache::remember('sanpham_search_' . $this->sanPhamCacheVersion() . '_' . md5($keyword), 120, function () use ($keyword) {
            $idsByBienThe = BienThe::where('ten_bienthe', 'LIKE', "%{$keyword}%")
                ->pluck('id_sanpham')
                ->toArray();

            return SanPham::with([
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
            }])
            ->where(function ($q) use ($keyword, $idsByBienThe) {
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
        $result = Cache::remember("sanpham_show_{$id}", 600, function () use ($id) {
            $sanpham = SanPham::with([
                'danhMuc',
                'thuongHieu',
                'hinhAnhs',
                'bienThes.comboOffers.sanPhams.bienThes'
            ])->find($id);

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
            'updated_at'        => $this->sanPhamCacheVersion(),

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
            ->select(
                'bienthe.id_bienthe',
                'sanpham.tenSP',
                'bienthe.ten_bienthe',
                'bienthe.gia',
                'bienthe.soluong'
            )
            ->orderBy('sanpham.id_sanpham')
            ->get();

        return response()->json($data);
    }
}
