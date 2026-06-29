<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = News::query();

        if ($request->filled('danhmuc') && $request->danhmuc !== 'all') {
            $query->where('danhmuc', $request->danhmuc);
        }

        if ($request->filled('trangthai') && $request->trangthai !== 'all') {
            $query->where('trangthai', $request->trangthai);
        }

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('tieude', 'like', "%{$keyword}%")
                    ->orWhere('tacgia', 'like', "%{$keyword}%");
            });
        }

        if (($request->scope ?? '') === 'public') {
            $query->where('trangthai', 'published')
                ->where(function ($q) {
                    $q->whereNull('dang_luc')
                        ->orWhere('dang_luc', '<=', now());
                });
        }

        $perPage = (int) $request->get('per_page', 9);
        $perPage = max(1, min($perPage, 50));

        $query->orderByDesc('dang_luc')->orderByDesc('id');

        if (($request->scope ?? '') === 'public') {
            $cacheKey = 'news_public_index_' . md5(json_encode($request->query()));

            return response()->json(
                Cache::remember($cacheKey, 60, fn () => $query->paginate($perPage))
            );
        }

        return response()->json($query->paginate($perPage));
    }

    public function show($id)
    {
        $news = News::where('trangthai', 'published')
            ->where(function ($q) {
                $q->whereNull('dang_luc')
                    ->orWhere('dang_luc', '<=', now());
            })
            ->findOrFail($id);

        $news->increment('luotxem');
        $news->refresh();

        return response()->json($news);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tieude' => 'required|string|max:255',
            'danhmuc' => 'required|string|max:100',
            'tacgia' => 'nullable|string|max:100',
            'trangthai' => 'required|in:draft,scheduled,published',
            'tomtat' => 'nullable|string',
            'noidung' => 'nullable|string',
            'hinhanh' => 'nullable|string',
            'mota_hinhanh' => 'nullable|string|max:255',
            'dang_luc' => 'nullable|date',
        ]);

        $imagePath = null;
        if ($request->filled('hinhanh') && str_starts_with($request->hinhanh, 'data:image')) {
            $imagePath = ImageHelper::saveBase64Image($request->hinhanh, 'uploads/news');
        } elseif ($request->filled('hinhanh')) {
            $imagePath = ImageHelper::normalizePublicPath($request->hinhanh);
        }

        $news = News::create([
            'tieude' => $request->tieude,
            'slug' => $this->makeUniqueSlug($request->tieude),
            'danhmuc' => $request->danhmuc,
            'tacgia' => $this->resolveAuthor($request),
            'trangthai' => $request->trangthai,
            'tomtat' => $request->tomtat,
            'noidung' => $request->noidung,
            'hinhanh' => $imagePath,
            'mota_hinhanh' => $this->smartImageAlt($request->mota_hinhanh, $request->tieude, $request->danhmuc, 'thumbnail'),
            'dang_luc' => $this->resolvePublishedAt($request),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo bài viết thành công.',
            'data' => $news,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $news = News::findOrFail($id);

        $request->validate([
            'tieude' => 'required|string|max:255',
            'danhmuc' => 'required|string|max:100',
            'tacgia' => 'nullable|string|max:100',
            'trangthai' => 'required|in:draft,scheduled,published',
            'tomtat' => 'nullable|string',
            'noidung' => 'nullable|string',
            'hinhanh' => 'nullable|string',
            'mota_hinhanh' => 'nullable|string|max:255',
            'dang_luc' => 'nullable|date',
        ]);

        $imagePath = $news->hinhanh;
        if ($request->has('hinhanh')) {
            if (blank($request->hinhanh)) {
                $imagePath = null;
            } elseif (str_starts_with($request->hinhanh, 'data:image')) {
                $imagePath = ImageHelper::saveBase64Image($request->hinhanh, 'uploads/news');
            } else {
                $imagePath = ImageHelper::normalizePublicPath($request->hinhanh);
            }
        }

        $news->update([
            'tieude' => $request->tieude,
            'slug' => $this->makeUniqueSlug($request->tieude, $news->id),
            'danhmuc' => $request->danhmuc,
            'tacgia' => $this->resolveAuthor($request, $news->tacgia),
            'trangthai' => $request->trangthai,
            'tomtat' => $request->tomtat,
            'noidung' => $request->noidung,
            'hinhanh' => $imagePath,
            'mota_hinhanh' => $this->smartImageAlt($request->mota_hinhanh, $request->tieude, $request->danhmuc, 'thumbnail'),
            'dang_luc' => $this->resolvePublishedAt($request, $news),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bài viết thành công.',
            'data' => $news->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $news = News::findOrFail($id);
        $news->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa bài viết thành công.',
        ]);
    }

    public function uploadContentImage(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'alt' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
        ]);

        if (!str_starts_with($request->image, 'data:image')) {
            return response()->json([
                'message' => 'Định dạng ảnh không hợp lệ.',
            ], 422);
        }

        $imagePath = ImageHelper::saveBase64Image($request->image, 'uploads/news/content');
        if (!$imagePath) {
            return response()->json([
                'message' => 'Không thể lưu ảnh.',
            ], 422);
        }

        $alt = $this->smartImageAlt(
            $request->alt,
            $request->title ?: 'bài viết VinaTech',
            $request->category,
            'content'
        );

        return response()->json([
            'success' => true,
            'data' => [
                'path' => $imagePath,
                'url' => asset("storage/{$imagePath}"),
                'alt' => $alt,
                'markdown' => "![{$alt}]({$imagePath})",
            ],
        ]);
    }

    public function stats()
    {
        return response()->json([
            'total' => News::count(),
            'published' => News::where('trangthai', 'published')->count(),
            'scheduled' => News::where('trangthai', 'scheduled')->count(),
            'draft' => News::where('trangthai', 'draft')->count(),
            'views' => (int) News::sum('luotxem'),
        ]);
    }

    private function smartImageAlt(?string $alt, string $title = '', ?string $category = null, string $type = 'thumbnail'): string
    {
        if (filled($alt)) {
            return trim(Str::limit(Str::squish($alt), 255, ''));
        }

        $rawTitle = $title ?: 'bài viết VinaTech';
        $keyword = trim(Str::before(Str::before(Str::squish($rawTitle), '|'), ':'));
        $keyword = $keyword !== '' ? Str::limit($keyword, 90, '') : 'bài viết VinaTech';
        $categoryText = trim(Str::squish($category ?: 'tin tức công nghệ'));

        $generated = $type === 'content'
            ? "Ảnh minh họa {$categoryText} về {$keyword}"
            : "Ảnh đại diện {$categoryText} về {$keyword}";

        return trim(Str::limit($generated, 255, ''));
    }

    private function resolveAuthor(Request $request, ?string $fallback = null): string
    {
        $name = $request->user()?->ten ?: $fallback ?: $request->tacgia ?: 'Admin';

        return trim(Str::limit(Str::squish($name), 100, ''));
    }

    private function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $baseSlug = $base ?: 'bai-viet';
        $slug = $baseSlug;
        $i = 1;

        while (
            News::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function resolvePublishedAt(Request $request, ?News $news = null)
    {
        if ($request->trangthai === 'published') {
            if ($request->filled('dang_luc')) {
                return $request->dang_luc;
            }

            if ($news?->trangthai === 'published' && $news->dang_luc) {
                return $news->dang_luc;
            }

            return now();
        }

        if ($request->trangthai === 'scheduled' && $request->filled('dang_luc')) {
            return $request->dang_luc;
        }

        return null;
    }
}
