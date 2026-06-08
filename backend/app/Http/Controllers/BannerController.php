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
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('position')
            ->orderByDesc('id')
            ->get();

        return response()->json($banners);
    }

    public function adminIndex()
    {
        return response()->json(
            Banner::query()->orderBy('position')->orderByDesc('id')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'eyebrow' => 'nullable|string|max:120',
            'highlight' => 'nullable|string|max:180',
            'description' => 'nullable|string|max:1000',
            'link_url' => 'nullable|string|max:500',
            'product_id' => 'nullable|integer|exists:sanpham,id_sanpham',
            'primary_label' => 'nullable|string|max:60',
            'secondary_label' => 'nullable|string|max:60',
            'product_badge' => 'nullable|string|max:80',
            'product_feature' => 'nullable|string|max:120',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,avi|max:51200',
            'mobile_image' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,avi|max:51200',
        ]);

        $data['image'] = $request->file('image')->store('banners', 'public');
        $data['media_type'] = $this->resolveMediaType($request->file('image'));
        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $request->file('mobile_image')->store('banners', 'public');
            $data['mobile_media_type'] = $this->resolveMediaType($request->file('mobile_image'));
        }
        $data['position'] = $data['position'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

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
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'eyebrow' => 'nullable|string|max:120',
            'highlight' => 'nullable|string|max:180',
            'description' => 'nullable|string|max:1000',
            'link_url' => 'nullable|string|max:500',
            'product_id' => 'nullable|integer|exists:sanpham,id_sanpham',
            'primary_label' => 'nullable|string|max:60',
            'secondary_label' => 'nullable|string|max:60',
            'product_badge' => 'nullable|string|max:80',
            'product_feature' => 'nullable|string|max:120',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,avi|max:51200',
            'mobile_image' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,avi|max:51200',
        ]);

        if ($request->hasFile('image')) {
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $request->file('image')->store('banners', 'public');
            $data['media_type'] = $this->resolveMediaType($request->file('image'));
        }

        if ($request->hasFile('mobile_image')) {
            if ($banner->mobile_image) {
                Storage::disk('public')->delete($banner->mobile_image);
            }
            $data['mobile_image'] = $request->file('mobile_image')->store('banners', 'public');
            $data['mobile_media_type'] = $this->resolveMediaType($request->file('mobile_image'));
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

        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }
        if ($banner->mobile_image) {
            Storage::disk('public')->delete($banner->mobile_image);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoa banner thanh cong',
        ]);
    }
}
