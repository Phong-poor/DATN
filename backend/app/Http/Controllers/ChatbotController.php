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

        $reply = 'Dạ anh/chị đang cần laptop cho nhu cầu gì ạ: học tập, văn phòng, gaming hay đồ họa? Kèm ngân sách để em lọc nhanh cho mình nha.';
        $products = collect();

        // Chào hỏi
        if ($this->containsAny($userMessage, ['xin chào', 'chào', 'hello', 'hi', 'shop ơi', 'ad ơi'])) {
            $reply = 'Dạ em chào anh/chị ạ. Anh/chị đang cần laptop cho học tập, văn phòng, gaming hay đồ họa ạ? Anh/chị nhắn thêm tầm giá, em lọc mẫu hợp nhất cho mình luôn nha.';
        }

        // Nhu cầu chung: mua laptop
        elseif ($this->containsAny($userMessage, ['mua laptop', 'cần laptop', 'tư vấn laptop', 'tìm laptop'])) {
            $reply = 'Dạ em tư vấn nhanh cho anh/chị nha. Anh/chị dùng máy chủ yếu để làm gì ạ: học tập, văn phòng, chơi game hay thiết kế? Ngân sách khoảng bao nhiêu để em gợi ý đúng mẫu dễ chốt hơn ạ.';
        }

        // Dưới 10 triệu
        elseif ($this->containsAny($userMessage, ['dưới 10', 'dưới 10 triệu', '10tr dưới', 'tầm 10 triệu đổ xuống'])) {
            $products = SanPham::with('bienThes')
                ->whereHas('bienThes', function ($q) {
                    $q->where('gia', '<=', 10000000);
                })
                ->latest('id_sanpham')
                ->take(4)
                ->get();

            $reply = $products->isNotEmpty()
                ? 'Dạ với ngân sách dưới 10 triệu thì anh/chị tham khảo mấy mẫu này nha. Tầm này hợp học tập, làm việc cơ bản và dùng ổn định. Anh/chị thích máy mỏng nhẹ hay ưu tiên cấu hình hơn để em lọc tiếp ạ?'
                : 'Dạ hiện em chưa thấy mẫu nào dưới 10 triệu trong kho ạ. Anh/chị có thể nâng nhẹ lên tầm 11 đến 13 triệu, lúc đó sẽ có nhiều mẫu ngon hơn nha.';
        }

        // Dưới 15 triệu
        elseif ($this->containsAny($userMessage, ['dưới 15', 'dưới 15 triệu', '15tr dưới', 'tầm 15 triệu đổ xuống'])) {
            $products = SanPham::with('bienThes')
                ->whereHas('bienThes', function ($q) {
                    $q->where('gia', '<=', 15000000);
                })
                ->latest('id_sanpham')
                ->take(4)
                ->get();

            $reply = $products->isNotEmpty()
                ? 'Dạ tầm dưới 15 triệu là mức rất dễ chọn máy ngon cho học tập, văn phòng và làm việc lâu dài ạ. Em gửi anh/chị vài mẫu đáng tiền nhất bên dưới nha. Anh/chị thích hãng nào để em lọc sát hơn ạ?'
                : 'Dạ hiện em chưa thấy mẫu nào đúng tầm dưới 15 triệu ạ. Anh/chị nhắn thêm nhu cầu, em sẽ tìm phương án gần nhất cho mình nha.';
        }

        // Dưới 20 triệu
        elseif ($this->containsAny($userMessage, ['dưới 20', 'dưới 20 triệu', '20tr dưới', 'tầm 20 triệu đổ xuống'])) {
            $products = SanPham::with('bienThes')
                ->whereHas('bienThes', function ($q) {
                    $q->where('gia', '<=', 20000000);
                })
                ->latest('id_sanpham')
                ->take(4)
                ->get();

            $reply = $products->isNotEmpty()
                ? 'Dạ tầm dưới 20 triệu đang là phân khúc rất dễ chọn máy đẹp, cấu hình khỏe và dùng bền ạ. Em gửi anh/chị vài mẫu nổi bật bên dưới. Anh/chị ưu tiên pin, màn hình hay hiệu năng để em chốt mẫu hợp nhất cho mình nha?'
                : 'Dạ hiện em chưa thấy mẫu nào đúng tầm dưới 20 triệu ạ. Anh/chị nhắn thêm hãng hoặc nhu cầu để em lọc kỹ hơn cho mình nha.';
        }

        // Trên 20 triệu
        elseif ($this->containsAny($userMessage, ['trên 20', 'trên 20 triệu', 'hơn 20', '20tr trở lên'])) {
            $products = SanPham::with('bienThes')
                ->whereHas('bienThes', function ($q) {
                    $q->where('gia', '>=', 20000000);
                })
                ->latest('id_sanpham')
                ->take(4)
                ->get();

            $reply = $products->isNotEmpty()
                ? 'Dạ với ngân sách trên 20 triệu thì mình có khá nhiều lựa chọn ngon về hiệu năng, màn đẹp và build tốt ạ. Em gửi anh/chị vài mẫu đáng xuống tiền nhất bên dưới nha. Anh/chị đang thiên về sang mỏng nhẹ hay cấu hình mạnh ạ?'
                : 'Dạ hiện em chưa thấy mẫu nào trên 20 triệu phù hợp ạ. Anh/chị thử nói thêm nhu cầu cụ thể giúp em nha.';
        }

        // Gaming
        elseif ($this->containsAny($userMessage, ['gaming', 'chơi game', 'game', 'pubg', 'valorant', 'lol', 'cs2'])) {
            $products = SanPham::with('bienThes')
                ->where(function ($q) {
                    $q->where('tenSP', 'like', '%gaming%')
                      ->orWhereHas('bienThes', function ($sub) {
                          $sub->where('ten_bienthe', 'like', '%rtx%')
                              ->orWhere('ten_bienthe', 'like', '%gtx%')
                              ->orWhere('thuoc_tinh_json', 'like', '%RTX%')
                              ->orWhere('thuoc_tinh_json', 'like', '%GTX%');
                      });
                })
                ->latest('id_sanpham')
                ->take(4)
                ->get();

            $reply = $products->isNotEmpty()
                ? 'Dạ nếu anh/chị cần laptop gaming thì em gợi ý mấy mẫu này ạ. Mấy dòng này phù hợp chơi game, làm việc nặng và dùng lâu dài. Anh/chị đang chơi game nào nhiều để em lọc mức cấu hình vừa tiền nhất cho mình nha?'
                : 'Dạ hiện em chưa lọc ra mẫu gaming thật sát ạ. Anh/chị nhắn thêm ngân sách và tựa game hay chơi, em chọn chuẩn hơn cho mình nha.';
        }

        // Học tập - văn phòng
        elseif ($this->containsAny($userMessage, ['sinh viên', 'học tập', 'văn phòng', 'office', 'word', 'excel', 'online'])) {
            $products = SanPham::with('bienThes')
                ->where(function ($q) {
                    $q->where('tenSP', 'like', '%vivobook%')
                      ->orWhere('tenSP', 'like', '%zenbook%')
                      ->orWhere('tenSP', 'like', '%book%')
                      ->orWhereHas('bienThes', function ($sub) {
                          $sub->where('ten_bienthe', 'like', '%intel core i3%')
                              ->orWhere('ten_bienthe', 'like', '%intel core i5%')
                              ->orWhere('ten_bienthe', 'like', '%8gb%')
                              ->orWhere('ten_bienthe', 'like', '%16gb%');
                      });
                })
                ->latest('id_sanpham')
                ->take(4)
                ->get();

            $reply = $products->isNotEmpty()
                ? 'Dạ đây là vài mẫu hợp cho sinh viên và văn phòng ạ. Tập trung vào độ ổn định, pin ổn và dễ dùng lâu dài. Anh/chị thích máy mỏng nhẹ mang đi học đi làm hay muốn cấu hình khỏe hơn để dùng nhiều năm ạ?'
                : 'Dạ anh/chị cần máy học tập hay văn phòng thì cho em thêm tầm giá, em lọc chuẩn hơn cho mình nha.';
        }

        // Đồ họa
        elseif ($this->containsAny($userMessage, ['đồ họa', 'design', 'thiết kế', 'photoshop', 'illustrator', 'premiere', 'render'])) {
            $products = SanPham::with('bienThes')
                ->whereHas('bienThes', function ($sub) {
                    $sub->where('ten_bienthe', 'like', '%rtx%')
                        ->orWhere('ten_bienthe', 'like', '%i7%')
                        ->orWhere('ten_bienthe', 'like', '%r7%')
                        ->orWhere('thuoc_tinh_json', 'like', '%RTX%')
                        ->orWhere('thuoc_tinh_json', 'like', '%Intel Core i7%')
                        ->orWhere('thuoc_tinh_json', 'like', '%Ryzen 7%');
                })
                ->latest('id_sanpham')
                ->take(4)
                ->get();

            $reply = $products->isNotEmpty()
                ? 'Dạ nếu anh/chị cần máy làm đồ họa thì em gợi ý mấy mẫu này ạ. Mấy dòng này sẽ hợp hơn cho Photoshop, Premiere, AI và làm việc nặng. Anh/chị làm 2D hay 3D để em lọc đúng cấu hình cần dùng nha?'
                : 'Dạ máy đồ họa thì anh/chị cho em thêm ngân sách để em lọc đúng hơn nha.';
        }

        // Mỏng nhẹ / pin trâu
        elseif ($this->containsAny($userMessage, ['mỏng nhẹ', 'nhẹ', 'pin trâu', 'pin lâu', 'mang đi học', 'mang đi làm'])) {
            $products = SanPham::with('bienThes')
                ->where(function ($q) {
                    $q->where('tenSP', 'like', '%air%')
                      ->orWhere('tenSP', 'like', '%zenbook%')
                      ->orWhere('tenSP', 'like', '%vivobook%')
                      ->orWhere('tenSP', 'like', '%inspiron%')
                      ->orWhere('tenSP', 'like', '%swift%');
                })
                ->latest('id_sanpham')
                ->take(4)
                ->get();

            $reply = $products->isNotEmpty()
                ? 'Dạ nếu anh/chị ưu tiên máy mỏng nhẹ, dễ mang theo và pin ổn thì em gợi ý các mẫu này ạ. Dòng này hợp sinh viên, dân văn phòng và người hay di chuyển. Anh/chị thích màn 14 inch hay 15.6 inch để em chốt mẫu dễ dùng nhất nha?'
                : 'Dạ anh/chị cho em thêm tầm giá để em lọc đúng các mẫu mỏng nhẹ cho mình nha.';
        }

        // Theo hãng
        elseif ($this->containsAny($userMessage, ['asus', 'acer', 'dell', 'hp', 'lenovo', 'msi', 'apple', 'macbook'])) {
            $brand = $this->extractBrand($userMessage);

            if ($brand) {
                $products = SanPham::with('bienThes')
                    ->where('tenSP', 'like', '%' . $brand . '%')
                    ->latest('id_sanpham')
                    ->take(4)
                    ->get();

                $reply = $products->isNotEmpty()
                    ? 'Dạ em tìm được vài mẫu ' . strtoupper($brand) . ' cho anh/chị đây ạ. Nếu anh/chị muốn, em có thể lọc tiếp theo tầm giá hoặc nhu cầu để ra mẫu dễ chốt nhất cho mình nha.'
                    : 'Dạ hiện em chưa thấy mẫu ' . strtoupper($brand) . ' phù hợp ạ. Anh/chị thử nhắn thêm ngân sách giúp em để em tìm mẫu gần nhất cho mình nha.';
            }
        }

        // Hỏi giá / ưu đãi
        elseif ($this->containsAny($userMessage, ['giá bao nhiêu', 'bao nhiêu tiền', 'giá', 'sale', 'khuyến mãi', 'ưu đãi'])) {
            $reply = 'Dạ anh/chị đang quan tâm mẫu nào hoặc tầm giá nào ạ? Em sẽ lọc nhanh mẫu phù hợp và báo mình mức giá dễ xuống tiền nhất nha.';
        }

        // Muốn được tư vấn thêm
        elseif ($this->containsAny($userMessage, ['tư vấn thêm', 'gợi ý thêm', 'mẫu khác', 'còn mẫu nào'])) {
            $products = SanPham::with('bienThes')
                ->latest('id_sanpham')
                ->take(4)
                ->get();

            $reply = $products->isNotEmpty()
                ? 'Dạ em gửi anh/chị thêm vài mẫu đang được quan tâm nhiều ạ. Anh/chị thích em lọc theo giá, hãng hay nhu cầu để ra đúng mẫu hợp nhất không ạ?'
                : 'Dạ hiện em chưa lấy được thêm mẫu ạ. Anh/chị nhắn em nhu cầu cụ thể hơn chút để em tư vấn sát hơn nha.';
        }

        // Chốt mềm
        elseif ($this->containsAny($userMessage, ['ok', 'ổn', 'được đó', 'ưng', 'thích mẫu này', 'mẫu này được'])) {
            $reply = 'Dạ mẫu này khá ổn trong tầm giá đó ạ. Nếu anh/chị muốn, em có thể tư vấn thêm cấu hình chi tiết, khả năng dùng thực tế và mẫu nào đáng tiền hơn để mình chốt dễ hơn nha.';
        }

        // Cảm ơn
        elseif ($this->containsAny($userMessage, ['cảm ơn', 'thank', 'thanks'])) {
            $reply = 'Dạ em cảm ơn anh/chị nhiều ạ. Khi nào cần tư vấn laptop cứ nhắn em, em lọc nhanh mẫu hợp nhu cầu và ngân sách cho mình nha.';
        }

        // Fallback: không hiểu nhưng vẫn giữ khách
        else {
            $products = SanPham::with('bienThes')
                ->latest('id_sanpham')
                ->take(4)
                ->get();

            $reply = 'Dạ em hiểu sơ nhu cầu của anh/chị rồi ạ. Anh/chị nhắn giúp em 2 ý là nhu cầu sử dụng và ngân sách, ví dụ như "văn phòng dưới 15 triệu" hoặc "gaming tầm 20 triệu", em sẽ gợi ý đúng mẫu dễ chọn nhất cho mình nha.';
        }

        return response()->json([
            'reply' => $reply,
            'products' => $products->values(),
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
}