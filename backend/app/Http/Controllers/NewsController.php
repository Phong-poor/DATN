<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = News::query();

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if (($request->scope ?? '') === 'public') {
            $query->where('status', 'published')
                ->where(function ($q) {
                    $q->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                });
        }

        $perPage = (int) $request->get('per_page', 9);
        $perPage = max(1, min($perPage, 50));

        return response()->json(
            $query->orderByDesc('published_at')->orderByDesc('id')->paginate($perPage)
        );
    }

    public function show($id)
    {
        $news = News::where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->findOrFail($id);

        $news->increment('views');
        $news->refresh();

        return response()->json($news);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'author' => 'nullable|string|max:100',
            'status' => 'required|in:draft,scheduled,published',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|string',
            'image_alt' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
        ]);

        $imagePath = null;
        if ($request->filled('image') && str_starts_with($request->image, 'data:image')) {
            $imagePath = ImageHelper::saveBase64Image($request->image, 'uploads/news');
        } elseif ($request->filled('image')) {
            $imagePath = $request->image;
        }

        $news = News::create([
            'title' => $request->title,
            'slug' => $this->makeUniqueSlug($request->title),
            'category' => $request->category,
            'author' => $this->resolveAuthor($request),
            'status' => $request->status,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'image' => $imagePath,
            'image_alt' => $this->smartImageAlt($request->image_alt, $request->title, $request->category, 'thumbnail'),
            'published_at' => $this->resolvePublishedAt($request),
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
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'author' => 'nullable|string|max:100',
            'status' => 'required|in:draft,scheduled,published',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|string',
            'image_alt' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
        ]);

        $imagePath = $news->image;
        if ($request->has('image')) {
            if (blank($request->image)) {
                $imagePath = null;
            } elseif (str_starts_with($request->image, 'data:image')) {
                $imagePath = ImageHelper::saveBase64Image($request->image, 'uploads/news');
            } else {
                $imagePath = ltrim(str_replace('http://127.0.0.1:8000/storage/', '', $request->image), '/');
                $imagePath = ltrim(str_replace('/storage/', '', $imagePath), '/');
            }
        }

        $news->update([
            'title' => $request->title,
            'slug' => $this->makeUniqueSlug($request->title, $news->id),
            'category' => $request->category,
            'author' => $this->resolveAuthor($request, $news->author),
            'status' => $request->status,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'image' => $imagePath,
            'image_alt' => $this->smartImageAlt($request->image_alt, $request->title, $request->category, 'thumbnail'),
            'published_at' => $this->resolvePublishedAt($request, $news),
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
            'published' => News::where('status', 'published')->count(),
            'scheduled' => News::where('status', 'scheduled')->count(),
            'draft' => News::where('status', 'draft')->count(),
            'views' => (int) News::sum('views'),
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
        $name = $request->user()?->name ?: $fallback ?: $request->author ?: 'Admin';

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
        if ($request->status === 'published') {
            if ($request->filled('published_at')) {
                return $request->published_at;
            }

            if ($news?->status === 'published' && $news->published_at) {
                return $news->published_at;
            }

            return now();
        }

        if ($request->status === 'scheduled' && $request->filled('published_at')) {
            return $request->published_at;
        }

        return null;
    }
}
