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

class SanPhamController extends Controller
{
    public function index()
    {
        $sanphams = SanPham::with([
            'danhMuc',
            'thuongHieu',
            'bienThes'
            // Đã bỏ hinhAnhs ra khỏi get() vì API danh sách (Products.vue, Producpage.vue) không dùng mảng hinh_anhs phụ, giúp JSON trả về nhẹ hơn 50%.
        ])
        ->orderByDesc('id_sanpham')
        ->get();

        return response()->json($sanphams);
    }

    // ===== TÌM KIẾM SẢN PHẨM =====
    public function search(Request $request)
    {
        $keyword = $request->query('q', '');

        if (strlen(trim($keyword)) === 0) {
            return response()->json([]);
        }

        $sanphams = SanPham::with([
            'danhMuc',
            'thuongHieu',
            'bienThes'
        ])
        ->where('tenSP', 'LIKE', "%{$keyword}%")
        ->orderByDesc('id_sanpham')
        ->get();

        return response()->json($sanphams);
    }

    public function show($id)
    {
        $sanpham = SanPham::with([
            'danhMuc',
            'thuongHieu',
            'hinhAnhs',
            'bienThes'
        ])->find($id);

        if (!$sanpham) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm.'
            ], 404);
        }

        // TỐI ƯU CỰC KỲ QUAN TRỌNG: Pre-load tất cả ThuocTinh 1 lần duy nhất thay vì query cho mỗi thuộc tính của mỗi biến thể (Lỗi N+1)
        $allThuocTinhs = ThuocTinh::all()->keyBy('ten_thuoctinh');

        $result = [
            'id_sanpham' => $sanpham->id_sanpham,
            'tenSP' => $sanpham->tenSP,
            'SKU' => $sanpham->SKU,
            'hinhanh' => $sanpham->hinhanh,
            'trangthai' => $sanpham->trangthai,
            'khoiluong' => $sanpham->khoiluong,

            // thêm 2 dòng này
            'id_danhmuc' => $sanpham->id_danhmuc,
            'id_thuonghieu' => $sanpham->id_thuonghieu,

            // thêm block này
            'danh_muc' => $sanpham->danhMuc ? [
                'id_danhmuc' => $sanpham->danhMuc->id_danhmuc,
                'ten_danhmuc' => $sanpham->danhMuc->ten_danhmuc,
            ] : null,

            'thuong_hieu' => $sanpham->thuongHieu ? [
                'id_thuonghieu' => $sanpham->thuongHieu->id_thuonghieu,
                'ten_thuonghieu' => $sanpham->thuongHieu->ten_thuonghieu,
            ] : null,

            'hinh_anhs' => $sanpham->hinhAnhs->map(function ($img) {
                return [
                    'duongdan' => $img->duongdan,
                    'thutu' => $img->thutu
                ];
            })->values(),

            'bien_thes' => $sanpham->bienThes->map(function ($bt) use ($allThuocTinhs) {
                $thuocTinhJson = collect(json_decode($bt->thuoc_tinh_json ?? '[]', true))
                    ->map(function ($item) use ($allThuocTinhs) {
                        $idThuocTinh = $item['id_thuoctinh'] ?? null;
                        $tenThuocTinh = $item['ten_thuoctinh'] ?? null;

                        if (!$idThuocTinh && $tenThuocTinh) {
                            $thuocTinh = $allThuocTinhs->get($tenThuocTinh);
                            $idThuocTinh = $thuocTinh?->id_thuoctinh;
                        }

                        return [
                            'id_thuoctinh' => $idThuocTinh,
                            'ten_thuoctinh' => $tenThuocTinh,
                            'giatri' => $item['giatri'] ?? null,
                            'ma_mau' => $item['ma_mau'] ?? ($item['hex'] ?? null),
                        ];
                    })
                    ->values();

                return [
                    'id_bienthe' => $bt->id_bienthe,
                    'ten_bienthe' => $bt->ten_bienthe,
                    'gia' => $bt->gia,
                    'soluong' => $bt->soluong,
                    'thuoc_tinh' => $thuocTinhJson,
                ];
            })->values()
        ];

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_danhmuc'     => 'required|integer|exists:danhmuc,id_danhmuc',
            'id_thuonghieu'  => 'required|integer|exists:thuonghieu,id_thuonghieu',
            'tenSP'          => 'required|string|max:255',
            'trangthai'      => 'required',
            'hinhanh'        => 'nullable|string',
            'khoiluong'      => 'nullable|numeric',

            'bienthes'                    => 'nullable|array',
            'bienthes.*.ten_bienthe'      => 'nullable|string|max:255',
            'bienthes.*.gia'              => 'required_with:bienthes|numeric|min:0',
            'bienthes.*.soluong'          => 'required_with:bienthes|integer|min:0',

            'hinh_anhs'                   => 'nullable|array',
            'hinh_anhs.*.duongdan'        => 'required_with:hinh_anhs|string',
            'hinh_anhs.*.thutu'           => 'nullable|integer|min:0',
            'hinh_anhs.*.macdinh'         => 'nullable|integer|in:0,1',
        ], [
            'id_danhmuc.required'    => 'Vui lòng chọn danh mục.',
            'id_thuonghieu.required' => 'Vui lòng chọn thương hiệu.',
            'tenSP.required'         => 'Tên sản phẩm không được để trống.',
        ]);

        DB::beginTransaction();

        try {
            $sku = $this->generateUniqueSKU($request->id_thuonghieu);
            $coverPath = ImageHelper::saveBase64Image($request->hinhanh, 'uploads/sanpham');

            $sanpham = SanPham::create([
                'id_danhmuc'    => $request->id_danhmuc,
                'id_thuonghieu' => $request->id_thuonghieu,
                'tenSP'         => $request->tenSP,
                'SKU'           => $sku,
                'trangthai'     => $request->trangthai,
                'hinhanh'       => $coverPath,
                'khoiluong'     => $request->khoiluong,
            ]);

            // Lưu ảnh phụ của sản phẩm
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

            // Lưu biến thể
            if ($request->has('bienthes') && is_array($request->bienthes)) {
                foreach ($request->bienthes as $bt) {
                    BienThe::create([
                        'id_sanpham'  => $sanpham->id_sanpham,
                        'ten_bienthe' => $bt['ten_bienthe'] ?? null,
                        'gia'         => $bt['gia'],
                        'soluong'     => $bt['soluong'],
                         'thuoc_tinh_json' => json_encode($bt['thuoc_tinh'] ?? [], JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }

            DB::commit();

            $sanpham = SanPham::with([
                'danhMuc',
                'thuongHieu',
                'bienThes',
                'hinhAnhs'
            ])->find($sanpham->id_sanpham);

            return response()->json([
                'message' => 'Thêm sản phẩm thành công.',
                'data' => $sanpham
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Không thêm được sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $sanpham = SanPham::find($id);

        if (!$sanpham) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm.'
            ], 404);
        }

        $request->validate([
            'id_danhmuc'     => 'required|integer|exists:danhmuc,id_danhmuc',
            'id_thuonghieu'  => 'required|integer|exists:thuonghieu,id_thuonghieu',
            'tenSP'          => 'required|string|max:255',
            'trangthai'      => 'required',
            'hinhanh'        => 'nullable|string',
            'khoiluong'      => 'nullable|numeric',

            'hinh_anhs'            => 'nullable|array',
            'hinh_anhs.*.duongdan' => 'required_with:hinh_anhs|string',
            'hinh_anhs.*.thutu'    => 'nullable|integer|min:0',

            'bienthes'                    => 'nullable|array',
            'bienthes.*.ten_bienthe'      => 'nullable|string|max:255',
            'bienthes.*.gia'              => 'required_with:bienthes|numeric|min:0',
            'bienthes.*.soluong'          => 'required_with:bienthes|integer|min:0',
        ], [
            'id_danhmuc.required'    => 'Vui lòng chọn danh mục.',
            'id_thuonghieu.required' => 'Vui lòng chọn thương hiệu.',
            'tenSP.required'         => 'Tên sản phẩm không được để trống.',
            'khoiluong.numeric'      => 'Khối lượng phải là số.',
        ]);

        DB::beginTransaction();

        try {
            $coverPath = $sanpham->hinhanh;

            // Ảnh chính mới là base64 thì lưu file mới
            if ($request->filled('hinhanh') && str_starts_with($request->hinhanh, 'data:image')) {
                $coverPath = ImageHelper::saveBase64Image($request->hinhanh, 'uploads/sanpham');
            }

            // Nếu frontend gửi lại path /storage/... thì giữ ảnh cũ
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
                'trangthai'     => $request->trangthai,
                'hinhanh'       => $coverPath,
                'khoiluong'     => $request->khoiluong,
            ]);

            // Sync ảnh phụ sản phẩm
            BienTheHinhAnh::where('id_sanpham', $sanpham->id_sanpham)->delete();

            if ($request->has('hinh_anhs') && is_array($request->hinh_anhs)) {
                foreach ($request->hinh_anhs as $index => $ha) {
                    $imagePath = $ha['duongdan'] ?? null;
                    if (!$imagePath) continue;

                    // Ảnh mới dạng base64
                    if (str_starts_with($imagePath, 'data:image')) {
                        $imagePath = ImageHelper::saveBase64Image($imagePath, 'uploads/sanpham');
                    } else {
                        // Ảnh cũ đang hiển thị dạng full URL /storage/...
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

            // Sync biến thể luôn cho đồng bộ
            BienThe::where('id_sanpham', $sanpham->id_sanpham)->delete();

            if ($request->has('bienthes') && is_array($request->bienthes)) {
                foreach ($request->bienthes as $bt) {
                    BienThe::create([
                        'id_sanpham'  => $sanpham->id_sanpham,
                        'ten_bienthe' => $bt['ten_bienthe'] ?? null,
                        'gia'         => $bt['gia'],
                        'soluong'     => $bt['soluong'],
                        'thuoc_tinh_json' => json_encode($bt['thuoc_tinh'] ?? [], JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }

            DB::commit();

            $sanpham = SanPham::with([
                'danhMuc',
                'thuongHieu',
                'bienThes',
                'hinhAnhs'
            ])->find($sanpham->id_sanpham);

            return response()->json([
                'message' => 'Cập nhật sản phẩm thành công.',
                'data' => $sanpham
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Không cập nhật được sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $sanpham = SanPham::find($id);

        if (!$sanpham) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm.'
            ], 404);
        }

        // Nếu DB đã set foreign key cascade thì chỉ cần xóa sản phẩm
        $sanpham->delete();

        return response()->json([
            'message' => 'Xóa sản phẩm thành công.'
        ]);
    }

    private function generateUniqueSKU($id_thuonghieu)
    {
        do {
            $sku = 'SP-' . $id_thuonghieu . '-' . strtoupper(Str::random(6));
        } while (SanPham::where('SKU', $sku)->exists());

        return $sku;
    }
}