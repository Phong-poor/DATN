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
        $public = $request->string('scope')->toString() === 'public';
        $query = News::query();

        if ($request->filled('danhmuc') && $request->danhmuc !== 'all') {
            $query->where('danhmuc', $request->danhmuc);
        }
        if (! $public && $request->filled('trangthai') && $request->trangthai !== 'all') {
            $query->where('trangthai', $request->trangthai);
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag));
        }
        if ($request->boolean('featured')) {
            $query->where('noi_bat', true);
        }
        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('tieude', 'like', "%{$keyword}%")
                    ->orWhere('tomtat', 'like', "%{$keyword}%")
                    ->orWhere('noidung', 'like', "%{$keyword}%")
                    ->orWhere('tacgia', 'like', "%{$keyword}%")
                    ->orWhereHas('tags', fn ($tag) => $tag->where('name', 'like', "%{$keyword}%"));
            });
        }

        if ($public) {
            $query->where('trangthai', 'published')
                ->where(fn ($q) => $q->whereNull('dang_luc')->orWhere('dang_luc', '<=', now()));
        }

        $sort = $request->get('sort', 'latest');
        $query->orderByDesc('ghim');
        $sort === 'popular'
            ? $query->orderByDesc('luotxem')
            : $query->orderByDesc('dang_luc')->orderByDesc('id');

        $perPage = max(1, min((int) $request->get('per_page', 9), 50));
        if (! $public) {
            return response()->json($query->paginate($perPage));
        }

        $key = 'news:public:'.$this->cacheVersion().':'.md5(json_encode($request->query()));

        return response()->json(Cache::remember($key, now()->addMinutes(10), fn () => $query->paginate($perPage)));
    }

    public function show(string $identifier)
    {
        $news = News::query()
            ->where('trangthai', 'published')
            ->where(fn ($q) => $q->whereNull('dang_luc')->orWhere('dang_luc', '<=', now()))
            ->where(fn ($q) => $q->where('id', $identifier)->orWhere('slug', $identifier))
            ->firstOrFail();

        News::whereKey($news->id)->increment('luotxem');
        $news->luotxem++;
        $news->setRelation('related', $this->relatedQuery($news)->limit(4)->get());

        return response()->json($news);
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

        $news = DB::transaction(function () use ($request, $news, $data) {
            $data = $this->prepareData($request, $data, $news);
            $data['slug'] = $this->uniqueSlug($data['tieude'], $news->id);
            $data['tacgia'] = $this->author($request, $news->tacgia);
            $news->update($data);
            $this->syncTags($news, $request->input('tags', []));
            $this->createRevision($news, $request, $request->input('revision_note', 'Cập nhật bài viết'));

            return $news->fresh();
        });

        $this->clearNewsCaches();

        return response()->json(['success' => true, 'message' => 'Cập nhật bài viết thành công.', 'data' => $news]);
    }

    public function autosave(Request $request, int $id)
    {
        $news = News::findOrFail($id);
        $data = $request->validate([
            'tieude' => 'sometimes|string|max:255', 'tomtat' => 'nullable|string', 'noidung' => 'nullable|string',
            'danhmuc' => 'sometimes|string|max:100', 'tags' => 'sometimes|array', 'tags.*' => 'string|max:80',
        ]);
        $data['reading_time'] = $this->readingTime($data['noidung'] ?? $news->noidung);
        $news->update(collect($data)->except('tags')->all());
        if (array_key_exists('tags', $data)) {
            $this->syncTags($news, $data['tags']);
        }

        return response()->json(['success' => true, 'saved_at' => now(), 'data' => $news->fresh()]);
    }

    public function preview(int $id)
    {
        return response()->json(News::findOrFail($id));
    }

    public function revisions(int $id)
    {
        News::findOrFail($id);

        return response()->json(NewsRevision::where('news_id', $id)->latest('version')->paginate(20));
    }

    public function restoreRevision(Request $request, int $id, int $revisionId)
    {
        $news = News::findOrFail($id);
        $revision = NewsRevision::where('news_id', $id)->findOrFail($revisionId);
        $allowed = collect($revision->snapshot)->only($news->getFillable())->except(['luotxem', 'share_count'])->all();
        $news->update($allowed);
        $this->createRevision($news, $request, "Khôi phục phiên bản {$revision->version}");
        $this->clearNewsCaches();

        return response()->json(['success' => true, 'message' => 'Đã khôi phục phiên bản.', 'data' => $news->fresh()]);
    }

    public function destroy(int $id)
    {
        $news = News::find($id);
        if ($news) {
            $news->delete();
        }
        $this->clearNewsCaches();

        return response()->json(['success' => true, 'message' => $news ? 'Xóa bài viết thành công.' : 'Bài viết đã được xóa trước đó.']);
    }

    public function track(Request $request, int $id)
    {
        News::findOrFail($id);
        $data = $request->validate([
            'event' => ['required', Rule::in(['read', 'share', 'product_click', 'favorite'])],
            'metadata' => 'nullable|array',
        ]);
        DB::table('news_events')->insert([
            'news_id' => $id, 'event' => $data['event'],
            'session_hash' => hash('sha256', ($request->ip() ?? '').'|'.($request->userAgent() ?? '')),
            'referrer' => Str::limit((string) $request->header('referer'), 255, ''),
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($data['event'] === 'share') {
            News::whereKey($id)->increment('share_count');
        }

        return response()->json(['success' => true], 201);
    }

    public function subscribe(Request $request)
    {
        $email = $request->validate(['email' => 'required|email:rfc|max:255'])['email'];
        DB::table('news_subscribers')->updateOrInsert(['email' => Str::lower($email)], [
            'active' => true, 'token' => Str::random(64), 'subscribed_at' => now(),
            'unsubscribed_at' => null, 'updated_at' => now(), 'created_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Đăng ký nhận tin thành công.']);
    }

    public function tags()
    {
        return response()->json(NewsTag::withCount('news')->orderByDesc('news_count')->get());
    }

    public function feed()
    {
        $items = News::where('trangthai', 'published')->where('dang_luc', '<=', now())->latest('dang_luc')->limit(30)->get();
        $xml = view('feeds.news', compact('items'))->render();

        return response($xml, 200)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function sitemap()
    {
        $items = News::where('trangthai', 'published')->where('no_index', false)->where('dang_luc', '<=', now())->latest('updated_at')->get();
        $xml = view('feeds.news-sitemap', compact('items'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function uploadContentImage(Request $request)
    {
        $data = $request->validate(['image' => 'required|string', 'alt' => 'nullable|string|max:255', 'title' => 'nullable|string|max:255', 'category' => 'nullable|string|max:100']);
        abort_unless(str_starts_with($data['image'], 'data:image'), 422, 'Định dạng ảnh không hợp lệ.');
        $path = ImageHelper::saveBase64Image($data['image'], 'uploads/news/content');
        abort_unless($path, 422, 'Không thể lưu ảnh.');
        $alt = $this->imageAlt($data['alt'] ?? null, $data['title'] ?? 'bài viết VinaTech', $data['category'] ?? null);

        return response()->json(['success' => true, 'data' => ['path' => $path, 'url' => asset("storage/{$path}"), 'alt' => $alt, 'markdown' => "![{$alt}]({$path})"]]);
    }

    public function stats()
    {
        return response()->json([
            'total' => News::count(), 'published' => News::where('trangthai', 'published')->count(),
            'scheduled' => News::where('trangthai', 'scheduled')->count(), 'draft' => News::where('trangthai', 'draft')->count(),
            'review' => News::where('workflow_status', 'review')->count(), 'views' => (int) News::sum('luotxem'),
            'shares' => (int) News::sum('share_count'),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tieude' => 'required|string|max:255', 'danhmuc' => 'required|string|max:100', 'tacgia' => 'nullable|string|max:100',
            'trangthai' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'workflow_status' => ['nullable', Rule::in(['draft', 'review', 'approved', 'published'])],
            'tomtat' => 'nullable|string|max:1000', 'noidung' => 'nullable|string', 'hinhanh' => 'nullable|string',
            'mota_hinhanh' => 'nullable|string|max:255', 'dang_luc' => 'nullable|date', 'tags' => 'nullable|array|max:15',
            'tags.*' => 'string|max:80', 'seo_title' => 'nullable|string|max:70', 'seo_description' => 'nullable|string|max:320',
            'seo_keywords' => 'nullable|string|max:255', 'canonical_url' => 'nullable|string|max:255', 'no_index' => 'boolean',
            'noi_bat' => 'boolean', 'ghim' => 'boolean', 'revision_note' => 'nullable|string|max:255',
        ]);
    }

    private function prepareData(Request $request, array $data, ?News $news = null): array
    {
        unset($data['tags'], $data['revision_note']);
        if ($request->has('hinhanh')) {
            $data['hinhanh'] = blank($request->hinhanh) ? null : (str_starts_with($request->hinhanh, 'data:image')
                ? ImageHelper::saveBase64Image($request->hinhanh, 'uploads/news')
                : ImageHelper::normalizePublicPath($request->hinhanh));
        } elseif ($news) {
            unset($data['hinhanh']);
        }
        $data['mota_hinhanh'] = $this->imageAlt($data['mota_hinhanh'] ?? null, $data['tieude'], $data['danhmuc']);
        $data['reading_time'] = $this->readingTime($data['noidung'] ?? '');
        $data['workflow_status'] = $data['workflow_status'] ?? ($data['trangthai'] === 'published' ? 'published' : 'draft');
        $data['dang_luc'] = $this->publishedAt($request, $news);
        if (in_array($data['workflow_status'], ['approved', 'published'], true)) {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] = $this->author($request);
        }

        $this->ensurePublishable($data);

        return $data;
    }

    private function syncTags(News $news, array|string|null $tags): void
    {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }
        $ids = collect($tags ?? [])->map(fn ($name) => trim(is_array($name) ? ($name['name'] ?? '') : $name))->filter()->unique()
            ->map(fn ($name) => NewsTag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id);
        $news->tags()->sync($ids);
    }

    private function createRevision(News $news, Request $request, string $note): void
    {
        $version = (int) NewsRevision::where('news_id', $news->id)->max('version') + 1;
        NewsRevision::create(['news_id' => $news->id, 'version' => $version, 'editor' => $this->author($request), 'note' => $note, 'snapshot' => $news->fresh()->toArray()]);
    }

    private function relatedQuery(News $news)
    {
        $tagIds = $news->tags->pluck('id');

        return News::query()->whereKeyNot($news->id)->where('trangthai', 'published')->where('dang_luc', '<=', now())
            ->where(fn ($q) => $q->where('danhmuc', $news->danhmuc)->orWhereHas('tags', fn ($tags) => $tags->whereIn('news_tags.id', $tagIds)))
            ->orderByDesc('noi_bat')->orderByDesc('dang_luc');
    }

    private function readingTime(?string $content): int
    {
        return max(1, (int) ceil($this->wordCount($content) / 200));
    }

    private function wordCount(?string $content): int
    {
        $plainText = preg_replace('/!\[[^\]]*\]\([^)]+\)/u', ' ', strip_tags((string) $content));
        preg_match_all('/[\p{L}\p{N}]+/u', $plainText ?: '', $matches);

        return count($matches[0]);
    }

    private function contentImageCount(?string $content): int
    {
        preg_match_all('/!\[[^\]]*\]\([^)]+\)/u', (string) $content, $matches);

        return count($matches[0]);
    }

    private function ensurePublishable(array $data): void
    {
        if (! in_array($data['trangthai'] ?? 'draft', ['published', 'scheduled'], true)) {
            return;
        }

        $errors = [];
        $wordCount = $this->wordCount($data['noidung'] ?? '');
        $imageCount = $this->contentImageCount($data['noidung'] ?? '');

        if ($wordCount < 600) {
            $errors['noidung'][] = "Bài xuất bản cần tối thiểu 600 từ; hiện có {$wordCount} từ.";
        }
        if ($imageCount < 2) {
            $errors['noidung'][] = "Bài xuất bản cần ít nhất 2 ảnh trong nội dung; hiện có {$imageCount} ảnh.";
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function imageAlt(?string $alt, string $title, ?string $category = null): string
    {
        return filled($alt) ? trim(Str::limit(Str::squish($alt), 255, '')) : trim(Str::limit('Ảnh minh họa '.($category ?: 'tin tức công nghệ').' về '.$title, 255, ''));
    }

    private function author(Request $request, ?string $fallback = null): string
    {
        return trim(Str::limit(Str::squish($request->user()?->ten ?: $request->user()?->name ?: $fallback ?: $request->tacgia ?: 'Admin'), 100, ''));
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'bai-viet';
        $slug = $base;
        $i = 1;
        while (News::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function publishedAt(Request $request, ?News $news = null)
    {
        if ($request->trangthai === 'published') {
            return $request->filled('dang_luc') ? $request->dang_luc : ($news?->dang_luc ?: now());
        }
        if ($request->trangthai === 'scheduled') {
            return $request->dang_luc;
        }

        return null;
    }

    private function cacheVersion(): string
    {
        return (string) Cache::get('news:version', '1');
    }

    private function clearNewsCaches(): void
    {
        Cache::forever('news:version', (string) microtime(true));
    }
}
