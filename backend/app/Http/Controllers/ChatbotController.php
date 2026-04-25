<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SanPham;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = trim(mb_strtolower($request->input('message')));

        // 1. Khởi tạo Query cho Biến thể (với quan hệ SanPham)
        $variantsQuery = \App\Models\BienThe::with('sanPham');
        $reply = 'Dạ em chào anh/chị ạ. Anh/chị đang cần laptop cho nhu cầu gì ạ: học tập, văn phòng, gaming hay đồ họa? Kèm ngân sách để em lọc nhanh cho mình nha.';

        // 2. Trích xuất giá và hướng lọc
        $prices = $this->extractPrices($userMessage);
        $priceContext = '';

        if (!empty($prices)) {
            if (count($prices) >= 2) {
                // Khoảng giá
                $min = $prices[0];
                $max = $prices[1];
                $variantsQuery->whereBetween('gia', [$min, $max]);
                $priceContext = "tầm giá từ " . number_format($min / 1000000, 0, ',', '.') . "tr đến " . number_format($max / 1000000, 0, ',', '.') . "tr";
            } else {
                // Một mức giá
                $price = $prices[0];
                if ($this->containsAny($userMessage, ['trên', 'hơn', 'cao hơn', 'trở lên'])) {
                    $variantsQuery->where('gia', '>=', $price);
                    $priceContext = "tầm giá trên " . number_format($price / 1000000, 0, ',', '.') . "tr";
                } else {
                    $variantsQuery->where('gia', '<=', $price);
                    $priceContext = "tầm giá dưới " . number_format($price / 1000000, 0, ',', '.') . "tr";
                }
            }
        }

        // 3. Lọc theo nhu cầu/keyword
        $intent = 'general';
        if ($this->containsAny($userMessage, ['gaming', 'chơi game', 'game', 'pubg', 'valorant', 'lol'])) {
            $intent = 'gaming';
            $variantsQuery->where(function ($q) {
                $q->whereHas('sanPham', function ($sub) {
                    $sub->where('tenSP', 'like', '%gaming%');
                })
                ->orWhere('ten_bienthe', 'like', '%rtx%')
                ->orWhere('ten_bienthe', 'like', '%gtx%')
                ->orWhere('thuoc_tinh_json', 'like', '%RTX%')
                ->orWhere('thuoc_tinh_json', 'like', '%GTX%');
            });
        } elseif ($this->containsAny($userMessage, ['sinh viên', 'học tập', 'văn phòng', 'office', 'online'])) {
            $intent = 'office';
            $variantsQuery->where(function ($q) {
                $q->whereHas('sanPham', function ($sub) {
                    $sub->where('tenSP', 'like', '%vivobook%')
                        ->orWhere('tenSP', 'like', '%zenbook%')
                        ->orWhere('tenSP', 'like', '%book%');
                })
                ->orWhere('ten_bienthe', 'like', '%intel core i3%')
                ->orWhere('ten_bienthe', 'like', '%intel core i5%')
                ->orWhere('ten_bienthe', 'like', '%8gb%');
            });
        } elseif ($this->containsAny($userMessage, ['đồ họa', 'design', 'thiết kế', 'photoshop', 'illustrator'])) {
            $intent = 'graphics';
            $variantsQuery->where(function ($q) {
                $q->whereHas('sanPham', function ($sub) {
                    $sub->where('tenSP', 'like', '%proart%')
                        ->orWhere('tenSP', 'like', '%macbook%');
                })
                ->orWhere('ten_bienthe', 'like', '%rtx%')
                ->orWhere('ten_bienthe', 'like', '%i7%')
                ->orWhere('ten_bienthe', 'like', '%r7%')
                ->orWhere('thuoc_tinh_json', 'like', '%RTX%')
                ->orWhere('thuoc_tinh_json', 'like', '%Intel Core i7%');
            });
        }

        // lọc theo hãng
        $brand = $this->extractBrand($userMessage);
        if ($brand) {
            $variantsQuery->whereHas('sanPham', function ($q) use ($brand) {
                $q->where('tenSP', 'like', '%' . $brand . '%');
            });
        }

        // 4. Lấy ngẫu nhiên 5 kết quả (theo yêu cầu của người dùng)
        $variants = $variantsQuery->inRandomOrder()->take(5)->get();

        // 5. Xây dựng câu trả lời
        if ($variants->isNotEmpty()) {
            if ($priceContext && $intent !== 'general') {
                $intentText = $intent === 'gaming' ? 'Gaming' : ($intent === 'office' ? 'Văn phòng' : 'Đồ họa');
                $reply = "Dạ em tìm được vài cấu hình laptop **{$intentText}** trong **{$priceContext}** cực tốt cho mình đây ạ:";
            } elseif ($priceContext) {
                $reply = "Dạ với **{$priceContext}**, đây là 5 lựa chọn cấu hình sịn sò nhất cho mình ạ:";
            } elseif ($intent !== 'general') {
                $intentText = $intent === 'gaming' ? 'Gaming' : ($intent === 'office' ? 'Văn phòng' : 'Đồ họa');
                $reply = "Dạ gửi khách yêu các dòng laptop chuyên **{$intentText}** bên em đang có sẵn ạ. Anh/chị nhắn thêm tầm giá để em lọc sát hơn nha!";
            } elseif ($brand) {
                $reply = "Dạ gửi anh/chị các phiên bản laptop **" . strtoupper($brand) . "** hot nhất đây ạ. Anh/chị xem có cấu hình nào ưng ý không nha!";
            } else {
                // Chào hỏi/Fallback
                if ($this->containsAny($userMessage, ['xin chào', 'chào', 'hello', 'hi'])) {
                    $reply = 'Dạ em chào anh/chị ạ! Anh/chị muốn tìm laptop tầm giá bao nhiêu hoặc dùng cho công việc gì để em tư vấn các cấu hình sát nhất ạ?';
                } else {
                    $reply = 'Dạ em gửi anh/chị vài cấu hình laptop đang được ưa chuộng nhất đây ạ. Anh/chị cần lọc theo giá hay hãng nào thì nhắn em ngay nha!';
                }
            }
        } else {
            if ($priceContext) {
                $reply = "Dạ hiện tại bên em chưa có cấu hình nào trong **{$priceContext}** phù hợp hoàn toàn ạ. Anh/chị có thể điều chỉnh ngân sách một chút hoặc nhắn nhu cầu để em tìm bản gần nhất nha.";
            } else {
                $reply = 'Dạ em chưa tìm thấy phiên bản laptop nào khớp hoàn toàn ạ. Anh/chị nhắn giúp em tầm giá hoặc dòng máy mình thích, em lọc lại ngay cho mình nha!';
            }
        }

        return response()->json([
            'reply' => $reply,
            'products' => $variants->values(), // Trả về biến thể (frontend vẫn dùng key 'products' để tránh sửa nhiều)
        ]);
    }

    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function extractBrand(string $text): ?string
    {
        $brands = ['asus', 'acer', 'dell', 'hp', 'lenovo', 'msi', 'apple', 'macbook'];

        foreach ($brands as $brand) {
            if (str_contains($text, $brand)) {
                return $brand === 'macbook' ? 'apple' : $brand;
            }
        }

        return null;
    }

    private function extractPrices(string $text): array
    {
        $prices = [];
        // Pattern 1: Số + đơn vị (15tr, 15 triệu, 15.5tr)
        // Chấp nhận số thập phân dấu phẩy hoặc dấu chấm
        preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?:tr|triệu|m|trđ|triệu đồng)/u', $text, $matches);
        foreach ($matches[1] as $val) {
            $prices[] = $this->normalizePrice($val);
        }

        // Pattern 2: Số thuần túy lớn (ví dụ 15000000)
        if (empty($prices)) {
            preg_match_all('/\b\d{6,}\b/', $text, $matches);
            foreach ($matches[0] as $val) {
                $prices[] = (int)$val;
            }
        }

        sort($prices);
        return array_unique($prices);
    }

    private function normalizePrice($val): int
    {
        // Thay dấu phẩy thành dấu chấm để xử lý số thực
        $val = str_replace(',', '.', $val);
        $num = (float)$val;

        // Nếu số nhỏ (dưới 1000) thì coi là đơn vị Triệu
        if ($num < 1000) {
            return (int)($num * 1000000);
        }
        return (int)$num;
    }
}