<?php

namespace App\Http\Controllers;

use App\Models\SanPham;
use App\Models\BienThe;
use App\Models\ThuocTinh;
use App\Models\BienTheHinhAnh;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class SanPhamController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'sanpham_index_' . md5(json_encode($request->all()));
        
        $sanphams = Cache::remember($cacheKey, 120, function () use ($request) {
            $query = SanPham::with([
                'danhMuc',
                'thuongHieu',
                'bienThes',
                'hinhAnhs'
            ])->orderByDesc('id_sanpham');

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
            return $products->map(function ($p) {
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
                    'danh_muc' => $p->danhMuc,
                    'thuong_hieu' => $p->thuongHieu,
                    'hinh_anhs' => $p->hinhAnhs,
                    'bien_thes' => $p->bienThes
                ];
            });
        });

        return response()->json($sanphams);
    }

    // Trả về danh sách các giá trị thuộc tính có trong DB
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

        $sanphams = Cache::remember('sanpham_search_' . md5($keyword), 120, function () use ($keyword) {
            $idsByBienThe = BienThe::where('ten_bienthe', 'LIKE', "%{$keyword}%")
                ->pluck('id_sanpham')
                ->toArray();

            return SanPham::with([
                'danhMuc',
                'thuongHieu',
                'bienThes'
            ])
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
        $result = Cache::remember("sanpham_show_{$id}", 120, function () use ($id) {
            $sanpham = SanPham::with([
                'danhMuc',
                'thuongHieu',
                'hinhAnhs',
                'bienThes'
            ])->find($id);

            if (!$sanpham) return null;

            $allThuocTinhs = ThuocTinh::all()->keyBy('ten_thuoctinh');

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

            'danh_muc' => $sanpham->danhMuc ? [
                'id_danhmuc'   => $sanpham->danhMuc->id_danhmuc,
                'ten_danhmuc'  => $sanpham->danhMuc->ten_danhmuc,
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
            $coverPath = ImageHelper::saveBase64Image($request->hinhanh, 'uploads/sanpham');

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
                    $imagePath = ImageHelper::saveBase64Image($ha['duongdan'] ?? null, 'uploads/sanpham');
                    if ($imagePath) {
                        BienTheHinhAnh::create([
                            'id_sanpham' => $sanpham->id_sanpham,
                            'duongdan'   => $imagePath,
                            'thutu'      => $ha['thutu'] ?? $index,
                            'macdinh'    => $ha['macdinh'] ?? 0,
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

            if ($request->filled('hinhanh') && str_starts_with($request->hinhanh, 'data:image')) {
                $coverPath = ImageHelper::saveBase64Image($request->hinhanh, 'uploads/sanpham');
            }

            if ($request->filled('hinhanh') && !str_starts_with($request->hinhanh, 'data:image')) {
                $incoming = $request->hinhanh;
                if (str_contains($incoming, '/storage/')) {
                    $coverPath = ltrim(str_replace('http://127.0.0.1:8000/storage/', '', $incoming), '/');
                    $coverPath = ltrim(str_replace('/storage/', '', $coverPath), '/');
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
                        $imagePath = ltrim(str_replace('http://127.0.0.1:8000/storage/', '', $imagePath), '/');
                        $imagePath = ltrim(str_replace('/storage/', '', $imagePath), '/');
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

            BienThe::where('id_sanpham', $sanpham->id_sanpham)->delete();

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

            Cache::forget("sanpham_show_{$id}");

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

        $sanpham->delete();

        Cache::forget("sanpham_show_{$id}");

        return response()->json(['message' => 'Xóa sản phẩm thành công.']);
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
        foreach ($request->updates as $item) {
            $updateData = [];
            if (isset($item['soluong']) && $item['soluong'] !== '') {
                $updateData['soluong'] = $item['soluong'];
            }
            if (isset($item['gia']) && $item['gia'] !== '') {
                $updateData['gia'] = $item['gia'];
            }

            if (!empty($updateData)) {
                BienThe::where('id_bienthe', $item['id_bienthe'])->update($updateData);
                $successCount++;
            }
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