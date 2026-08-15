<?php

namespace App\Http\Controllers;

use App\Models\DanhGia;
use App\Models\DatHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Quản lý đánh giá sản phẩm, duyệt nội dung và phản hồi đánh giá.
 */
class DanhGiaController extends Controller
{
    /**
     * Lấy danh sách đánh giá công khai cho một sản phẩm (chỉ những bài đã duyệt)
     */
    public function index($productId)
    {
        $reviews = DanhGia::with(['user', 'bienThe'])
            ->whereHas('bienThe', function ($q) use ($productId) {
                $q->where('id_sanpham', $productId);
            })
            ->where('trangthai', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Admin: Lấy toàn bộ danh sách đánh giá
     */
    public function adminIndex(Request $request)
    {
        $status = $request->query('status');

        $reviews = DanhGia::with(['user', 'bienThe.sanPham'])
            ->when($status && $status !== 'all', function ($q) use ($status) {
                $q->where('trangthai', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return response()->json([
            'success' => true,
            'reviews' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
                'per_page' => $reviews->perPage(),
            ],
            'stats' => [
                'total' => DanhGia::count(),
                'pending' => DanhGia::where('trangthai', 'pending')->count(),
                'spam' => DanhGia::where('trangthai', 'spam')->count(),
                'avg' => round(DanhGia::avg('danhgia') ?: 0, 1),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_dathang' => 'required|exists:dathang,id_dathang',
            'id_bienthe' => 'required|exists:bienthe,id_bienthe',
            'danhgia' => 'required|integer|min:1|max:5',
            'binhluan' => 'nullable|string',
        ]);

        $userId = Auth::id();

        $order = DatHang::where('id_dathang', $request->id_dathang)
            ->where('id_khachhang', $userId)
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Đơn hàng không hợp lệ.'], 403);
        }

        if ($order->trangthai !== 'done') {
            return response()->json(['success' => false, 'message' => 'Bạn chỉ có thể đánh giá sau khi đơn hàng đã hoàn thành.'], 400);
        }

        $hasItem = $order->chi_tiets()->where('id_bienthe', $request->id_bienthe)->exists();
        if (! $hasItem) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm này không nằm trong đơn hàng.'], 400);
        }

        $exists = DanhGia::where('id_dathang', $request->id_dathang)
            ->where('id_bienthe', $request->id_bienthe)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này cho đơn hàng này rồi.'], 400);
        }

        // Phân tích bình luận qua bộ lọc AI thông minh
        $aiResult = $this->analyzeCommentWithAI($request->binhluan ?? '', (int) $request->danhgia);
        $comment = (string) ($request->binhluan ?? '');
        if (! empty($aiResult['reply'])) {
            $comment = trim($comment."\n\n".$aiResult['reply']);
        }

        $danhGia = DanhGia::create([
            'id_dathang' => $request->id_dathang,
            'id_bienthe' => $request->id_bienthe,
            'user_id' => $userId,
            'danhgia' => $request->danhgia,
            'binhluan' => $comment,
            'trangthai' => $aiResult['trangthai'],
        ]);

        $this->clearProductCacheByReview($danhGia);

        $msg = 'Cảm ơn bạn đã đánh giá sản phẩm! Đánh giá của bạn sẽ được hiển thị sau khi được duyệt.';
        if ($aiResult['trangthai'] === 'approved') {
            $msg = 'Cảm ơn bạn đã đánh giá sản phẩm! Đánh giá của bạn chứa phản hồi tích cực và đã được duyệt hiển thị tự động.';
        } elseif ($aiResult['trangthai'] === 'spam') {
            $msg = 'Cảm ơn phản hồi của bạn. Đánh giá của bạn đang được kiểm duyệt hệ thống.';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'danh_gia' => $danhGia,
        ]);
    }

    /**
     * Admin: Cập nhật trạng thái đánh giá (Duyệt/Spam)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'trangthai' => 'required|in:pending,approved,spam',
        ]);

        $review = DanhGia::findOrFail($id);
        $review->update(['trangthai' => $request->trangthai]);

        $this->clearProductCacheByReview($review);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái đánh giá thành công!',
            'review' => $review,
        ]);
    }

    /**
     * Admin/User: Xóa đánh giá
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:danhgia,id_danhgia',
            'trangthai' => 'required|in:pending,approved,spam',
        ]);

        $reviews = DanhGia::whereIn('id_danhgia', $validated['ids'])->get();

        foreach ($reviews as $review) {
            $review->update(['trangthai' => $validated['trangthai']]);
            $this->clearProductCacheByReview($review);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái bình luận hàng loạt thành công!',
            'updated' => $reviews->count(),
        ]);
    }

    public function autoModeratePending(Request $request)
    {
        $limit = (int) $request->input('limit', 100);
        $limit = max(1, min($limit, 500));

        $reviews = DanhGia::whereIn('trangthai', ['pending', 'approved'])
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $summary = [
            'scanned' => $reviews->count(),
            'approved' => 0,
            'spam' => 0,
            'pending' => 0,
        ];

        foreach ($reviews as $review) {
            $result = $this->analyzeCommentWithAI((string) $review->binhluan, (int) $review->danhgia);

            if ($result['trangthai'] === 'spam') {
                if ($review->trangthai !== 'spam') {
                    $cleanText = trim(preg_split('/\n\nCảm ơn/u', (string) $review->binhluan)[0]);
                    $review->update([
                        'trangthai' => 'spam',
                        'binhluan' => $cleanText
                    ]);
                    $this->clearProductCacheByReview($review);
                    $summary['spam']++;
                }
            } elseif ($review->trangthai === 'pending' && $result['trangthai'] === 'approved') {
                $updates = ['trangthai' => 'approved'];
                if (! empty($result['reply']) && ! str_contains((string) $review->binhluan, 'Cảm ơn')) {
                    $updates['binhluan'] = trim(((string) $review->binhluan)."\n\n".$result['reply']);
                }
                $review->update($updates);
                $this->clearProductCacheByReview($review);
                $summary['approved']++;
            } elseif ($review->trangthai === 'pending') {
                $summary['pending']++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã chạy tool kiểm duyệt đánh giá tự động.',
            'summary' => $summary,
        ]);
    }

    public function destroy($id)
    {
        $review = DanhGia::findOrFail($id);

        // Nếu không phải admin thì chỉ được xóa review của chính mình
        if (Auth::user()->vaitro === 'user' && $review->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xóa đánh giá này.'], 403);
        }

        $this->clearProductCacheByReview($review);

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa đánh giá thành công.',
        ]);
    }

    /**
     * Bộ lọc AI thông minh: Quét ngôn từ thô tục và tự động cảm ơn bình luận tích cực
     */
    private function analyzeCommentWithAI($text, $rating)
    {
        return $this->analyzeCommentWithModerationTool((string) $text, (int) $rating);
    }

    private function analyzeCommentWithModerationTool(string $text, int $rating): array
    {
        // Tách câu gốc nếu bình luận đã chứa câu trả lời tự động trước đó
        $parts = preg_split('/\n\nCảm ơn/u', (string) $text);
        $userText = trim($parts[0] ?? '');

        $normalized = $this->normalizeModerationText($userText);
        $compact = preg_replace('/[^a-z0-9]+/', '', $normalized);
        $hasText = trim($normalized) !== '';

        $profanityWords = [
            'dm', 'dmm', 'dcm', 'dkm', 'clgt', 'vcl', 'vl', 'cc', 'buoi',
            'dit', 'deo', 'loz', 'cut', 'ngu', 'oc cho', 'occho',
            'mat day', 'ham', 'khon nan', 'cho chet', 'me may', 'cha may',
            'lon', 'nhu lon', 'nhu l', 'nhu c', 'nhu cut', 'nhu cac', 'cl', 'kac',
            'shop nhu', 'shopnhu', 'cai lon', 'mat lon', 'con lon', 'nhu rác', 'nhu rac'
        ];

        $attackPhrases = [
            'shop lua dao', 'lua dao', 'shop rac', 'shop nhu cut', 'shop nhu lon',
            'shop mat day', 'shop vo trach nhiem', 'shop lam an bo lao', 'shop bo lao',
            'mua o shop khac', 'dung mua', 'khong nen mua', 'canh bao moi nguoi',
            'tay chay', 'scam', 'fake', 'hang gia', 'hang dom', 'hang deu',
        ];

        $complaintPhrases = [
            'qua te', 'rat te', 'te hai', 'kem chat luong', 'that vong', 'khong hai long',
            'khong dung mo ta', 'sai mo ta', 'hang loi', 'bi loi', 'loi san pham',
            'hong', 'bi hong', 'vo', 'be', 'mop', 'tray xuoc', 'khong dung duoc',
            'giao cham', 'dong goi te', 'phuc vu te', 'tu van te', 'bao hanh te',
            'khong bao hanh', 'khong ho tro', 'khong tra loi', 'khong chap nhan',
        ];

        $positivePhrases = [
            'tot', 'ok', 'on', 'tam on', 'binh thuong', 'duoc', 'hai long', 'ung y',
            'tuyet voi', 'rat tot', 'chat luong', 'xai tot', 'dung tot', 'muot',
            'dep', 'nhanh', 'giao nhanh', 'dong goi ky', 'shop uy tin', 'se ung ho',
            'dang tien', 'san pham tot', 'nhiet tinh', 'recommend',
        ];

        $spamSignals = [
            'http', 'https', 'www', 'telegram', 'zalo me', 'casino', 'ca cuoc',
            'vay tien', 'kiem tien', 'khuyen mai soc', 'inbox rieng',
        ];

        $profanityHits = $this->countPhraseHits($normalized, $profanityWords)
            + $this->countCompactHits($compact, ['dmm', 'dcm', 'dkm', 'clgt', 'vcl', 'loz', 'cc', 'nhul', 'nhuc', 'nhucut', 'nhulon']);
        $attackHits = $this->countPhraseHits($normalized, $attackPhrases);
        $complaintHits = $this->countPhraseHits($normalized, $complaintPhrases);
        $positiveHits = $this->countPhraseHits($normalized, $positivePhrases);
        $spamHits = $this->countPhraseHits($normalized, $spamSignals);
        $repeatedChars = preg_match('/(.)\1{5,}/u', $userText) ? 1 : 0;

        // ƯU TIÊN HÀNG ĐẦU: Nếu có từ chửi tục, công kích, hoặc spam => GÁN THÀNH SPAM NGAY BẤT KỂ SAO MẤY
        if ($profanityHits > 0 || $attackHits > 0 || $spamHits > 0 || $repeatedChars > 0) {
            return ['trangthai' => 'spam', 'reply' => null];
        }

        if ($rating <= 2 && ($complaintHits > 0 || ! $hasText)) {
            return ['trangthai' => 'spam', 'reply' => null];
        }

        if ($rating === 3 && $complaintHits >= 2) {
            return ['trangthai' => 'spam', 'reply' => null];
        }

        // Chỉ khi SẠCH TỪ XẤU và ĐÁNH GIÁ TỐT mới duyệt và sinh phản hồi cảm ơn
        $isPositive = $rating >= 4;

        if ($isPositive) {
            $reply = null;
            $aiActive = false;
            if (Storage::exists('admin/ai_status.json')) {
                $aiActive = filter_var(Storage::get('admin/ai_status.json'), FILTER_VALIDATE_BOOLEAN);
            }

            if (! $aiActive) {
                return ['trangthai' => 'pending', 'reply' => null];
            }

            if ($hasText) {
                $thankReplies = [
                    'Cảm ơn bạn rất nhiều vì đánh giá tích cực! Chúc bạn có những trải nghiệm tuyệt vời cùng sản phẩm.',
                    'Cảm ơn Quý khách đã tin tưởng và ủng hộ sản phẩm. Sự hài lòng của bạn là động lực để chúng tôi tiếp tục cải thiện dịch vụ.',
                    'Cảm ơn bạn đã dành thời gian đánh giá sản phẩm. Chúc bạn sử dụng sản phẩm thật hiệu quả và hài lòng.',
                    'Cảm ơn phản hồi rất chất lượng từ bạn! Chúng tôi sẽ luôn cố gắng hỗ trợ bạn tốt nhất trong quá trình sử dụng.',
                ];
                $reply = $thankReplies[array_rand($thankReplies)];
            }

            return ['trangthai' => 'approved', 'reply' => $reply];
        }

        return ['trangthai' => 'pending', 'reply' => null];
    }

    private function countPhraseHits(string $text, array $phrases): int
    {
        $hits = 0;
        foreach ($phrases as $phrase) {
            if ($phrase === '') {
                continue;
            }

            $pattern = '/(^| )'.preg_quote($phrase, '/').'( |$)/u';
            if (preg_match($pattern, $text)) {
                $hits++;
            }
        }

        return $hits;
    }

    private function countCompactHits(string $text, array $phrases): int
    {
        $hits = 0;
        foreach ($phrases as $phrase) {
            if ($phrase !== '' && str_contains($text, $phrase)) {
                $hits++;
            }
        }

        return $hits;
    }

    private function normalizeModerationText(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = \Illuminate\Support\Str::ascii($text);
        $text = strtr($text, [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ]);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    public function getAiStatus()
    {
        $status = false;
        if (Storage::exists('admin/ai_status.json')) {
            $status = filter_var(Storage::get('admin/ai_status.json'), FILTER_VALIDATE_BOOLEAN);
        }

        return response()->json([
            'success' => true,
            'active' => $status,
        ]);
    }

    /**
     * Admin: Kích hoạt / Hủy kích hoạt Trợ lý AI Smart Reply
     */
    public function toggleAiStatus(Request $request)
    {
        $validated = $request->validate([
            'active' => 'required|boolean',
        ]);

        $active = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        Storage::put('admin/ai_status.json', $active ? 'true' : 'false');

        return response()->json([
            'success' => true,
            'active' => $active,
            'message' => $active ? 'Đã kích hoạt Trợ lý AI Smart Reply thành công!' : 'Đã hủy kích hoạt Trợ lý AI Smart Reply!',
        ]);
    }

    /**
     * Clear all product-related caches when reviews are updated.
     */
    private function clearProductCacheByReview($review)
    {
        if ($review) {
            $review->load('bienThe');
            if ($review->bienThe) {
                $productId = $review->bienThe->id_sanpham;
                Cache::forget("sanpham_show_{$productId}");
            }
        }
        Cache::put('sanpham_cache_bust', (string) microtime(true));
        Cache::forget('sanpham_index_'.md5(json_encode([])));
        Cache::forget('mobile_home_v2');
    }
}
