<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\News;
use App\Models\NewsRevision;
use App\Models\NewsTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $scope = $request->get('scope');
            $public = ($scope === 'public') || ($request->has('scope') && $request->string('scope')->toString() === 'public');
            $query = News::query();

            if ($request->filled('danhmuc') && $request->danhmuc !== 'all') {
                $query->where('danhmuc', $request->danhmuc);
            }
            if (! $public && $request->filled('trangthai') && $request->trangthai !== 'all') {
                $query->where('trangthai', $request->trangthai);
            }
            if ($request->boolean('featured')) {
                $query->where('noi_bat', true);
            }

            if ($public) {
                $query->where('trangthai', 'published');
            }

            $sort = $request->get('sort', 'latest');
            if ($sort === 'popular') {
                $query->orderByDesc('luotxem');
            } else {
                $query->orderByDesc('id');
            }

            $perPage = max(1, min((int) $request->get('per_page', 9), 50));
            $paginated = $query->paginate($perPage);

            return response()->json($paginated, 200, [
                'Access-Control-Allow-Origin' => 'https://nextgenlaptop.click',
                'Access-Control-Allow-Credentials' => 'true',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            return response()->json([
                'current_page' => 1,
                'data' => [],
                'total' => 0,
                'per_page' => 9,
                'last_page' => 1,
            ], 200, [
                'Access-Control-Allow-Origin' => 'https://nextgenlaptop.click',
                'Access-Control-Allow-Credentials' => 'true',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }

    public function show(string $identifier)
    {
        try {
            $news = News::query()
                ->where('trangthai', 'published')
                ->where(fn ($q) => $q->whereNull('dang_luc')->orWhere('dang_luc', '<=', now()))
                ->where(fn ($q) => $q->where('id', $identifier)->orWhere('slug', $identifier))
                ->firstOrFail();

            News::whereKey($news->id)->increment('luotxem');
            $news->luotxem++;

            return response()->json($news, 200, [
                'Access-Control-Allow-Origin' => 'https://nextgenlaptop.click',
                'Access-Control-Allow-Credentials' => 'true',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Không tìm thấy bài viết.'], 404, [
                'Access-Control-Allow-Origin' => 'https://nextgenlaptop.click',
                'Access-Control-Allow-Credentials' => 'true',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $news = DB::transaction(function () use ($request, $data) {
            $data = $this->prepareData($request, $data);
            $data['slug'] = $this->uniqueSlug($data['tieude']);
            $data['tacgia'] = $this->author($request);
            $news = News::create($data);
            $this->syncTags($news, $request->input('tags', []));
            $this->createRevision($news, $request, 'Khởi tạo bài viết');

            return $news->fresh();
        });

        $this->clearNewsCaches();

        return response()->json(['success' => true, 'message' => 'Tạo bài viết thành công.', 'data' => $news], 201);
    }

    public function update(Request $request, int $id)
    {
        $news = News::findOrFail($id);
        $data = $this->validated($request);

        $news = DB::transaction(function () use ($news, $request, $data) {
            $data = $this->prepareData($request, $data);
            if ($data['tieude'] !== $news->tieude) {
                $data['slug'] = $this->uniqueSlug($data['tieude'], $news->id);
            }
            $news->update($data);
            $this->syncTags($news, $request->input('tags', []));
            $this->createRevision($news, $request, 'Cập nhật bài viết');

            return $news->fresh();
        });

        $this->clearNewsCaches();

        return response()->json(['success' => true, 'message' => 'Cập nhật bài viết thành công.', 'data' => $news]);
    }

    public function destroy(int $id)
    {
        $news = News::findOrFail($id);

        DB::transaction(function () use ($news) {
            $news->tags()->detach();
            $news->revisions()->delete();
            $news->delete();
        });

        $this->clearNewsCaches();

        return response()->json(['success' => true, 'message' => 'Xóa bài viết thành công.']);
    }

    public function tags()
    {
        $tags = Cache::remember('news_tags_public', 3600, function () {
            return NewsTag::query()->orderBy('name')->get();
        });

        return response()->json($tags);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tieude' => 'required|string|max:255',
            'danhmuc' => 'required|string|max:100',
            'tomtat' => 'nullable|string|max:1000',
            'noidung' => 'required|string',
            'hinhanh' => 'nullable|string',
            'mota_hinhanh' => 'nullable|string|max:255',
            'trangthai' => ['required', Rule::in(['draft', 'pending', 'published', 'archived', 'scheduled'])],
            'dang_luc' => 'nullable|date',
            'noi_bat' => 'boolean',
            'ghim' => 'boolean',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|url|max:255',
            'no_index' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);
    }

    private function prepareData(Request $request, array $data): array
    {
        $rawHinhanh = $data['hinhanh'] ?? null;

        if (is_string($rawHinhanh) && str_starts_with($rawHinhanh, 'data:image')) {
            $data['hinhanh'] = ImageHelper::saveBase64Image($rawHinhanh, 'uploads/news');
        } elseif ($request->hasFile('hinhanh_file')) {
            $data['hinhanh'] = ImageHelper::uploadImage($request->file('hinhanh_file'), 'news');
        }

        $data['noi_bat'] = $request->boolean('noi_bat');
        $data['ghim'] = $request->boolean('ghim');
        $data['no_index'] = $request->boolean('no_index');
        $data['reading_time'] = $this->calculateReadingTime($data['noidung'] ?? '');

        return $data;
    }

    private function author(Request $request): string
    {
        $user = $request->user();

        return $user ? ($user->ten ?? $user->name ?? 'Admin') : 'Admin';
    }

    private function syncTags(News $news, array $tagNames): void
    {
        $tagIds = [];
        foreach ($tagNames as $name) {
            $name = trim($name);
            if (! $name) {
                continue;
            }
            $tag = NewsTag::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name)]
            );
            $tagIds[] = $tag->id;
        }
        $news->tags()->sync($tagIds);
    }

    private function createRevision(News $news, Request $request, string $note = ''): void
    {
        try {
            $lastVersion = (int) NewsRevision::where('news_id', $news->id)->max('version');
            NewsRevision::create([
                'news_id' => $news->id,
                'version' => $lastVersion + 1,
                'title' => $news->tieude,
                'summary' => $news->tomtat,
                'content' => $news->noidung,
                'snapshot' => $news->toArray(),
                'user_id' => $request->user()?->id,
                'note' => $note,
            ]);
        } catch (\Throwable $e) {
            // Bỏ qua lỗi tạo revision để không ảnh hưởng việc Lưu bài viết chính
        }
    }

    private function calculateReadingTime(string $content): int
    {
        $words = str_word_count(strip_tags($content));

        return max(1, (int) ceil($words / 200));
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (News::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function uploadContentImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required',
                'alt' => 'nullable|string|max:255',
            ]);

            $rawImage = $request->input('image');
            $alt = $request->input('alt') ?: 'Hình ảnh bài viết';
            $filePath = null;

            if ($request->hasFile('image')) {
                $filePath = ImageHelper::uploadImage($request->file('image'), 'news');
            } elseif (is_string($rawImage) && str_starts_with($rawImage, 'data:image')) {
                $filePath = ImageHelper::saveBase64Image($rawImage, 'uploads/news');
            } elseif (is_string($rawImage)) {
                $filePath = ImageHelper::normalizePublicPath($rawImage);
            }

            if (!$filePath) {
                return response()->json(['success' => false, 'message' => 'Không thể lưu hình ảnh.'], 422);
            }

            $normalized = ImageHelper::normalizePublicPath($filePath) ?: $filePath;
            $url = str_starts_with($normalized, 'http') ? $normalized : asset('storage/' . ltrim($normalized, '/'));
            $markdown = "![" . str_replace(['[', ']'], '', $alt) . "](" . $url . ")";

            return response()->json([
                'success' => true,
                'message' => 'Tải ảnh bài viết thành công.',
                'data' => [
                    'url' => $url,
                    'path' => $filePath,
                    'markdown' => $markdown,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi tải ảnh bài viết: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function stats()
    {
        try {
            $total = News::count();
            $published = News::where('trangthai', 'published')->count();
            $draft = News::where('trangthai', 'draft')->count();
            $scheduled = News::where('trangthai', 'scheduled')->count();
            $views = (int) News::sum('luotxem');

            return response()->json([
                'total' => $total,
                'published' => $published,
                'draft' => $draft,
                'scheduled' => $scheduled,
                'views' => $views,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'total' => 0,
                'published' => 0,
                'draft' => 0,
                'scheduled' => 0,
                'views' => 0,
            ]);
        }
    }

    public function autosave(Request $request, $id)
    {
        try {
            $news = News::find($id);
            if (!$news) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy bài viết.'], 404);
            }

            $news->update([
                'tieude' => $request->input('tieude', $news->tieude),
                'danhmuc' => $request->input('danhmuc', $news->danhmuc),
                'tomtat' => $request->input('tomtat', $news->tomtat),
                'noidung' => $request->input('noidung', $news->noidung),
            ]);

            return response()->json(['success' => true, 'message' => 'Đã tự động lưu.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function preview($id)
    {
        try {
            $news = News::findOrFail($id);
            return response()->json(['success' => true, 'data' => $news]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bài viết.'], 404);
        }
    }

    public function revisions($id)
    {
        try {
            $revisions = NewsRevision::where('news_id', $id)
                ->orderByDesc('version')
                ->get();

            return response()->json(['success' => true, 'data' => $revisions]);
        } catch (\Throwable $e) {
            return response()->json(['success' => true, 'data' => []]);
        }
    }

    public function restoreRevision($id, $revisionId)
    {
        try {
            $news = News::findOrFail($id);
            $revision = NewsRevision::where('news_id', $id)->where('id', $revisionId)->firstOrFail();

            $news->update([
                'tieude' => $revision->title ?? $news->tieude,
                'tomtat' => $revision->summary ?? $news->tomtat,
                'noidung' => $revision->content ?? $news->noidung,
            ]);

            return response()->json(['success' => true, 'message' => 'Khôi phục phiên bản thành công.', 'data' => $news->fresh()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Không thể khôi phục phiên bản.'], 500);
        }
    }

    public function feed()
    {
        try {
            $posts = News::where('trangthai', 'published')->orderByDesc('id')->take(20)->get();
            return response()->json($posts);
        } catch (\Throwable $e) {
            return response()->json([]);
        }
    }

    public function sitemap()
    {
        try {
            $posts = News::where('trangthai', 'published')->orderByDesc('id')->get(['id', 'slug', 'updated_at']);
            return response()->json($posts);
        } catch (\Throwable $e) {
            return response()->json([]);
        }
    }

    public function track($id)
    {
        try {
            News::where('id', $id)->increment('luotxem');
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false]);
        }
    }

    public function subscribe(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Đăng ký nhận tin thành công.']);
    }

    private function clearNewsCaches(): void
    {
        Cache::forget('news_tags_public');
        Cache::forget('news_feed_xml');
        Cache::forget('news_sitemap_xml');
    }
}
