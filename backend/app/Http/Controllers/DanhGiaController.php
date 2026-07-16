<?php

namespace App\Http\Controllers;

use App\Models\DanhGia;
use App\Models\DatHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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
        $aiResult = $this->analyzeCommentWithModerationTool($request->binhluan ?? '', (int) $request->danhgia);

        $danhGia = DanhGia::create([
            'id_dathang' => $request->id_dathang,
            'id_bienthe' => $request->id_bienthe,
            'user_id' => $userId,
            'danhgia' => $request->danhgia,
            'binhluan' => $request->binhluan,
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
        $textLower = mb_strtolower($text, 'UTF-8');

        // 1. Danh sách từ khóa chửi tục, thô tục phổ biến tiếng Việt (bao gồm cả viết tắt/teencode)
        $profanityWords = [
            'đm', 'dm', 'dkm', 'vcl', 'clgt', 'lồn', 'lon', 'chó', 'cho', 'đần', 'dan', 'ngu',
            'mất dạy', 'mat day', 'cứt', 'cut', 'hãm', 'ham', 'đéo', 'deo', 'buồi', 'buoi',
            'cặc', 'cac', 'óc chó', 'oc cho', 'đĩ', 'di', 'điếm', 'diem', 'khốn nạn', 'khon nan',
            'bậy', 'bay', 'đớp', 'dop', 'hít', 'hit', 'địt', 'dit', 'mẹ mày', 'me may', 'cha mày', 'cha may',
            'cl', 'dcm', 'vl', 'đmm', 'dmm',
        ];

        // Kiểm tra xem bình luận có chứa từ thô tục nào không
        $isSpam = false;
        if (! empty($text)) {
            foreach ($profanityWords as $word) {
                if (mb_strpos($textLower, $word) !== false) {
                    $isSpam = true;
                    break;
                }
            }
        }

        if ($isSpam) {
            return [
                'trangthai' => 'spam',
                'reply' => null,
            ];
        }

        // 2. Kiểm tra xem Trợ lý AI Smart Reply có được kích hoạt không
        $aiActive = false;
        if (Storage::exists('admin/ai_status.json')) {
            $aiActive = filter_var(Storage::get('admin/ai_status.json'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($aiActive && ! empty($text)) {
            // Phân tích cảm xúc tích cực để tự động duyệt & phản hồi
            $positiveWords = [
                'tốt', 'tot', 'tuyệt vời', 'tuyet voi', 'ưng ý', 'ung y', 'chất lượng', 'chat luong',
                'đẹp', 'dep', 'mượt', 'muot', 'nhanh', 'nhiệt tình', 'nhiet tinh', 'thích', 'thich',
                'hài lòng', 'hai long', 'yêu', 'yeu', 'xịn', 'xin', 'quá ngon', 'qua ngon', 'ok',
                'recommend', 'tuyệt', 'tuyet', 'quá đỉnh', 'qua dinh',
            ];

            $isPositive = ($rating >= 4);
            if (! $isPositive) {
                foreach ($positiveWords as $word) {
                    if (mb_strpos($textLower, $word) !== false) {
                        $isPositive = true;
                        break;
                    }
                }
            }

            if ($isPositive) {
                // Danh sách các câu trả lời tự động ngẫu nhiên của AI để tăng tính tự nhiên
                $thankReplies = [
                    '🤖 *Trợ lý AI VinaTech:* Cảm ơn bạn rất nhiều vì đánh giá tích cực! VinaTech rất tự hào khi mang đến trải nghiệm hài lòng cho bạn. Chúc bạn có những trải nghiệm tuyệt vời cùng sản phẩm! ✨',
                    '🤖 *Trợ lý AI VinaTech:* Cảm ơn Quý khách đã tin tưởng và ủng hộ sản phẩm của VinaTech! Sự hài lòng của bạn là động lực lớn nhất để chúng tôi không ngừng cải thiện chất lượng dịch vụ. Chúc bạn một ngày tốt lành! 🌸',
                    '🤖 *Trợ lý AI VinaTech:* Tuyệt vời quá! Cảm ơn bạn đã dành thời gian đánh giá sản phẩm. Chúc bạn có thời gian làm việc và giải trí thật mượt mà và hiệu quả nhé! 💻🚀',
                    '🤖 *Trợ lý AI VinaTech:* Cảm ơn phản hồi siêu chất lượng từ bạn! VinaTech cam kết luôn đồng hành và hỗ trợ bạn tốt nhất trong suốt quá trình sử dụng. Chúc bạn vạn sự như ý! 💎',
                ];

                $reply = $thankReplies[array_rand($thankReplies)];

                return [
                    'trangthai' => 'approved', // Tự động duyệt hiển thị lên giao diện
                    'reply' => $reply,
                ];
            }
        }

        // Mặc định: Chờ duyệt thủ công
        return [
            'trangthai' => 'pending',
            'reply' => null,
        ];
    }

    /**
     * Admin: Lấy trạng thái kích hoạt của Trợ lý AI Smart Reply
     */
    private function analyzeCommentWithModerationTool(string $text, int $rating): array
    {
        $normalized = $this->normalizeModerationText($text);
        $compact = preg_replace('/[^a-z0-9]+/', '', $normalized);
        $hasText = trim($normalized) !== '';

        $profanityWords = [
            'dm', 'dmm', 'dcm', 'dkm', 'clgt', 'vcl', 'vl', 'cc', 'buoi',
            'dit', 'deo', 'loz', 'cuc', 'cut', 'ngu', 'oc cho', 'occho',
            'mat day', 'ham', 'khon nan', 'cho chet', 'me may', 'cha may',
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

        $profanityScore = $this->countPhraseHits($normalized, $profanityWords)
            + $this->countCompactHits($compact, ['dmm', 'dcm', 'dkm', 'clgt', 'vcl', 'loz']);
        $attackScore = $this->countPhraseHits($normalized, $attackPhrases);
        $complaintScore = $this->countPhraseHits($normalized, $complaintPhrases);
        $positiveScore = $this->countPhraseHits($normalized, $positivePhrases);
        $spamScore = $this->countPhraseHits($normalized, $spamSignals);
        $repeatedChars = preg_match('/(.)\1{5,}/u', $text) ? 1 : 0;
        $tooShortNegative = (! $hasText && $rating <= 2) ? 1 : 0;

        if ($profanityScore > 0 || $attackScore > 0 || $spamScore > 0 || $repeatedChars > 0) {
            return ['trangthai' => 'spam', 'reply' => null];
        }

        if ($rating <= 2 && ($complaintScore > 0 || $tooShortNegative)) {
            return ['trangthai' => 'spam', 'reply' => null];
        }

        if ($rating === 3 && $complaintScore >= 2) {
            return ['trangthai' => 'spam', 'reply' => null];
        }

        if ($rating >= 4 || $positiveScore > 0) {
            return ['trangthai' => 'approved', 'reply' => null];
        }

        if ($rating === 3 && $complaintScore === 0) {
            return ['trangthai' => 'approved', 'reply' => null];
        }

        if ($hasText && $complaintScore === 0) {
            return ['trangthai' => 'approved', 'reply' => null];
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
        $request->validate([
            'active' => 'required|boolean',
        ]);

        Storage::put('admin/ai_status.json', $request->active ? 'true' : 'false');

        return response()->json([
            'success' => true,
            'active' => $request->active,
            'message' => $request->active ? 'Đã kích hoạt Trợ lý AI Smart Reply thành công!' : 'Đã hủy kích hoạt Trợ lý AI Smart Reply!',
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
