<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    private function resolveMediaType($file): string
    {
        $mime = (string) $file->getMimeType();
        return str_starts_with($mime, 'video/') ? 'video' : 'image';
    }

    public function index()
    {
        $now = now();

        $banners = Banner::query()
            ->where('trangthai', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('batdauluc')->orWhere('batdauluc', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ketthucluc')->orWhere('ketthucluc', '>=', $now);
            })
            ->orderBy('vitri')
            ->orderByDesc('id')
            ->get();

        return response()->json($banners);
    }

    public function adminIndex()
    {
        return response()->json(
            Banner::query()->orderBy('vitri')->orderByDesc('id')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tieude' => 'required|string|max:255',
            'phude' => 'nullable|string|max:255',
            'chudenho' => 'nullable|string|max:120',
            'noibat' => 'nullable|string|max:180',
            'mota' => 'nullable|string|max:1000',
            'duongdan' => 'nullable|string|max:500',
            'id_sanpham' => 'nullable|integer|exists:sanpham,id_sanpham',
            'nhanchinh' => 'nullable|string|max:60',
            'nhanphu' => 'nullable|string|max:60',
            'huyhieu_sanpham' => 'nullable|string|max:80',
            'dactinh_sanpham' => 'nullable|string|max:120',
            'vitri' => 'nullable|integer|min:0',
            'trangthai' => 'nullable|boolean',
            'batdauluc' => 'nullable|date',
            'ketthucluc' => 'nullable|date|after_or_equal:batdauluc',
            'hinhanh' => 'required|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,avi|max:51200',
            'hinhanh_mobile' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,avi|max:51200',
        ]);

        $data['hinhanh'] = $request->file('hinhanh')->store('banners', 'public');
        $data['loaimedia'] = $this->resolveMediaType($request->file('hinhanh'));
        if ($request->hasFile('hinhanh_mobile')) {
            $data['hinhanh_mobile'] = $request->file('hinhanh_mobile')->store('banners', 'public');
            $data['loai_media_mobile'] = $this->resolveMediaType($request->file('hinhanh_mobile'));
        }
        $data['vitri'] = $data['vitri'] ?? 0;
        $data['trangthai'] = $data['trangthai'] ?? true;

        $banner = Banner::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tao banner thanh cong',
            'data' => $banner,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $data = $request->validate([
            'tieude' => 'required|string|max:255',
            'phude' => 'nullable|string|max:255',
            'chudenho' => 'nullable|string|max:120',
            'noibat' => 'nullable|string|max:180',
            'mota' => 'nullable|string|max:1000',
            'duongdan' => 'nullable|string|max:500',
            'id_sanpham' => 'nullable|integer|exists:sanpham,id_sanpham',
            'nhanchinh' => 'nullable|string|max:60',
            'nhanphu' => 'nullable|string|max:60',
            'huyhieu_sanpham' => 'nullable|string|max:80',
            'dactinh_sanpham' => 'nullable|string|max:120',
            'vitri' => 'nullable|integer|min:0',
            'trangthai' => 'nullable|boolean',
            'batdauluc' => 'nullable|date',
            'ketthucluc' => 'nullable|date|after_or_equal:batdauluc',
            'hinhanh' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,avi|max:51200',
            'hinhanh_mobile' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,avi|max:51200',
        ]);

        if ($request->hasFile('hinhanh')) {
            if ($banner->hinhanh) {
                Storage::disk('public')->delete($banner->hinhanh);
            }
            $data['hinhanh'] = $request->file('hinhanh')->store('banners', 'public');
            $data['loaimedia'] = $this->resolveMediaType($request->file('hinhanh'));
        }

        if ($request->hasFile('hinhanh_mobile')) {
            if ($banner->hinhanh_mobile) {
                Storage::disk('public')->delete($banner->hinhanh_mobile);
            }
            $data['hinhanh_mobile'] = $request->file('hinhanh_mobile')->store('banners', 'public');
            $data['loai_media_mobile'] = $this->resolveMediaType($request->file('hinhanh_mobile'));
        }

        $banner->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cap nhat banner thanh cong',
            'data' => $banner,
        ]);
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        if ($banner->hinhanh) {
            Storage::disk('public')->delete($banner->hinhanh);
        }
        if ($banner->hinhanh_mobile) {
            Storage::disk('public')->delete($banner->hinhanh_mobile);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoa banner thanh cong',
        ]);
    }
}
