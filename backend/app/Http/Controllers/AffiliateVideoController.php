<?php

namespace App\Http\Controllers;

use App\Models\AffiliateProfile;
use App\Models\AffiliateVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AffiliateVideoController extends Controller
{
    public function publicIndex(Request $request)
    {
        $limit = min(12, max(1, (int) $request->query('limit', 4)));

        $videos = AffiliateVideo::with([
            'affiliateUser:id,ten,email',
            'product:id_sanpham,tenSP,hinhanh',
        ])
            ->where('trangthai', 'approved')
            ->orderByDesc('noi_bat')
            ->orderByDesc('duoc_duyet_luc')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return response()->json($videos);
    }

    public function myVideos(Request $request)
    {
        $user = $request->user();

        $videos = AffiliateVideo::with('product:id_sanpham,tenSP,hinhanh')
            ->where('id_affiliate_khachhang', $user->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($videos);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $profile = AffiliateProfile::where('id_khachhang', $user->id)->first();

        if (!$profile || $profile->trangthai !== 'active') {
            return response()->json([
                'message' => 'Tai khoan affiliate chua duoc kich hoat.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|min:5|max:160',
            'description' => 'nullable|string|max:800',
            'product_id' => 'nullable|integer|exists:sanpham,id_sanpham',
            'video_url' => 'nullable|url|max:500',
            'video' => 'nullable|file|max:204800',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (!$request->hasFile('video') && empty($validated['video_url'])) {
            return response()->json([
                'message' => 'Vui long tai video len hoac nhap link video.',
            ], 422);
        }

        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $extension = strtolower($file->getClientOriginalExtension());
            $mime = strtolower((string) $file->getMimeType());
            $allowedExtensions = ['mp4', 'webm', 'mov', 'avi', 'm4v', 'mkv'];

            if (!in_array($extension, $allowedExtensions, true) || (!str_starts_with($mime, 'video/') && $mime !== 'application/octet-stream')) {
                return response()->json([
                    'message' => 'File video khong dung dinh dang. Vui long chon MP4, WebM, MOV, AVI, M4V hoac MKV.',
                    'errors' => [
                        'video' => ['File video khong dung dinh dang.'],
                    ],
                ], 422);
            }
        }

        $videoPath = $request->hasFile('video')
            ? $request->file('video')->store('affiliate-videos/videos', 'public')
            : null;

        $thumbnailPath = $request->hasFile('thumbnail')
            ? $request->file('thumbnail')->store('affiliate-videos/thumbnails', 'public')
            : null;

        $video = AffiliateVideo::create([
            'id_affiliate_khachhang' => $user->id,
            'id_sanpham' => $validated['product_id'] ?? null,
            'tieu_de' => $validated['title'],
            'mo_ta' => $validated['description'] ?? null,
            'video_path' => $videoPath,
            'video_url' => $validated['video_url'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'trangthai' => 'pending',
            'noi_bat' => false,
        ]);

        $video->load('product:id_sanpham,tenSP,hinhanh');

        return response()->json([
            'message' => 'Da gui video affiliate, vui long cho admin duyet.',
            'video' => $video,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $profile = AffiliateProfile::where('id_khachhang', $user->id)->first();

        if (!$profile || $profile->trangthai !== 'active') {
            return response()->json([
                'message' => 'Tai khoan affiliate chua duoc kich hoat.',
            ], 403);
        }

        $video = AffiliateVideo::where('id_affiliate_khachhang', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|min:5|max:160',
            'description' => 'nullable|string|max:800',
            'product_id' => 'nullable|integer|exists:sanpham,id_sanpham',
            'video_url' => 'nullable|url|max:500',
            'video' => 'nullable|file|max:204800',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (
            !$request->hasFile('video')
            && empty($validated['video_url'])
            && empty($video->video_path)
            && empty($video->video_url)
        ) {
            return response()->json([
                'message' => 'Vui long tai video len hoac nhap link video.',
            ], 422);
        }

        if ($request->hasFile('video')) {
            $this->validateVideoFile($request->file('video'));
            if ($video->video_path) {
                Storage::disk('public')->delete($video->video_path);
            }
            $video->video_path = $request->file('video')->store('affiliate-videos/videos', 'public');
            $video->video_url = null;
        } elseif (!empty($validated['video_url'])) {
            if ($video->video_path) {
                Storage::disk('public')->delete($video->video_path);
            }
            $video->video_path = null;
            $video->video_url = $validated['video_url'];
        }

        if ($request->hasFile('thumbnail')) {
            if ($video->thumbnail_path) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }
            $video->thumbnail_path = $request->file('thumbnail')->store('affiliate-videos/thumbnails', 'public');
        }

        $video->id_sanpham = $validated['product_id'] ?? null;
        $video->tieu_de = $validated['title'];
        $video->mo_ta = $validated['description'] ?? null;
        $video->trangthai = 'pending';
        $video->noi_bat = false;
        $video->ly_do_tu_choi = null;
        $video->duoc_duyet_luc = null;
        $video->save();

        $video->load('product:id_sanpham,tenSP,hinhanh');

        return response()->json([
            'message' => 'Da cap nhat video affiliate, vui long cho admin duyet lai.',
            'video' => $video,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $video = AffiliateVideo::where('id_affiliate_khachhang', $user->id)->findOrFail($id);

        $this->deleteMedia($video);
        $video->delete();

        return response()->json([
            'message' => 'Da xoa video affiliate.',
        ]);
    }

    public function adminIndex()
    {
        $videos = AffiliateVideo::with([
            'affiliateUser:id,ten,email',
            'product:id_sanpham,tenSP,hinhanh',
        ])
            ->orderByDesc('id')
            ->get();

        return response()->json($videos);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'hidden'])],
            'reject_reason' => 'nullable|string|max:500',
            'featured' => 'nullable|boolean',
        ]);

        $video = AffiliateVideo::findOrFail($id);
        $video->trangthai = $validated['status'];
        $video->ly_do_tu_choi = $validated['reject_reason'] ?? null;

        if (array_key_exists('featured', $validated)) {
            $video->noi_bat = (bool) $validated['featured'];
        }

        if ($validated['status'] === 'approved' && !$video->duoc_duyet_luc) {
            $video->duoc_duyet_luc = now();
        }

        if ($validated['status'] !== 'approved') {
            $video->noi_bat = false;
        }

        $video->save();
        $video->load(['affiliateUser:id,ten,email', 'product:id_sanpham,tenSP,hinhanh']);

        return response()->json([
            'message' => 'Da cap nhat trang thai video affiliate.',
            'video' => $video,
        ]);
    }

    public function track(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['view', 'click'])],
        ]);

        $column = $validated['type'] === 'click' ? 'luot_click' : 'luot_xem';
        AffiliateVideo::where('id', $id)->where('trangthai', 'approved')->increment($column);

        return response()->json(['ok' => true]);
    }

    private function deleteMedia(AffiliateVideo $video): void
    {
        foreach (['video_path', 'thumbnail_path'] as $field) {
            if ($video->{$field}) {
                Storage::disk('public')->delete($video->{$field});
            }
        }
    }

    private function validateVideoFile($file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());
        $allowedExtensions = ['mp4', 'webm', 'mov', 'avi', 'm4v', 'mkv'];

        if (!in_array($extension, $allowedExtensions, true) || (!str_starts_with($mime, 'video/') && $mime !== 'application/octet-stream')) {
            abort(response()->json([
                'message' => 'File video khong dung dinh dang. Vui long chon MP4, WebM, MOV, AVI, M4V hoac MKV.',
                'errors' => [
                    'video' => ['File video khong dung dinh dang.'],
                ],
            ], 422));
        }
    }
}
