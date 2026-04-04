<?php

namespace App\Http\Controllers;

use App\Models\SanPham;
use App\Models\BienThe;
use App\Models\BienTheHinhAnh;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SanPhamController extends Controller
{
    public function index(Request $request)
{
    $query = SanPham::with([
        'danhMuc',
        'thuongHieu',
        'bienThes',
        'hinhAnhs'
    ])->orderByDesc('id_sanpham');

    // Cấu hình các thuộc tính cần lọc dựa trên query params
    $attributesToFilter = [
        'ram' => 'ram',
        'cpu' => 'cpu',
        'gpu' => 'gpu',
        'kichthuoc' => 'kích thước',
        'dophan' => 'độ phân giải',
        'tamnen' => 'tấm nền',
        'pin' => 'pin',
        'sac' => 'sạc'
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

    $sanphams = $query->get();

    return response()->json($sanphams);
}

    // Trả về danh sách các giá trị thuộc tính có trong DB (để populate sidebar)
    public function attributeOptions()
    {
        // Danh sách các ID thuộc tính cần thiết theo ảnh cung cấp:
        // 1: RAM, 2: CPU, 3: GPU, 4: Kích thước, 5: Độ phân giải, 6: Tấm nền, 7: Pin, 8: Sạc
        $attributeIds = [1, 2, 3, 4, 5, 6, 7, 8];
        
        $options = \App\Models\GiaTriThuocTinh::whereIn('id_thuoctinh', $attributeIds)
            ->where('trangthai', 1)
            ->orderBy('id_thuoctinh')
            ->orderBy('giatri')
            ->get()
            ->groupBy('id_thuoctinh')
            ->map(function ($items) {
                return $items->pluck('giatri');
            });

        return response()->json([
            'ram' => $options->get(1, []),
            'cpu' => $options->get(2, []),
            'gpu' => $options->get(3, []),
            'kichthuoc' => $options->get(4, []),
            'dophan' => $options->get(5, []),
            'tamnen' => $options->get(6, []),
            'pin' => $options->get(7, []),
            'sac' => $options->get(8, [])
        ]);
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

        $result = [
            'id_sanpham' => $sanpham->id_sanpham,
            'tenSP' => $sanpham->tenSP,
            'SKU' => $sanpham->SKU,
            'hinhanh' => $sanpham->hinhanh,
            'trangthai' => $sanpham->trangthai,
            'khoiluong' => $sanpham->khoiluong,

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

            'bien_thes' => $sanpham->bienThes->map(function ($bt) {
                return [
                    'id_bienthe' => $bt->id_bienthe,
                    'ten_bienthe' => $bt->ten_bienthe,
                    'gia' => $bt->gia,
                    'soluong' => $bt->soluong,
                    'thuoc_tinh' => json_decode($bt->thuoc_tinh_json ?? '[]', true),
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