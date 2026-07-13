<?php

namespace App\Http\Controllers;

use App\Models\BienThe;
use App\Models\DanhMuc;
use App\Models\Promotion;
use App\Models\ThuongHieu;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = trim(mb_strtolower($request->input('message')));

        // 1. CHÀO HỎI & FAQ CHÍNH SÁCH / THÔNG TIN LIÊN HỆ

        // Chương trình Khuyến mãi / Ưu đãi từ DB
        if ($this->containsAny($userMessage, ['khuyến mãi', 'khuyen mai', 'mã giảm giá', 'giam gia', 'voucher', 'ưu đãi', 'uu dai', 'km', 'promotion', 'coupon'])) {
            $now = now()->toDateString();
            $promotions = Promotion::whereIn('trangthai', ['running', 'open', 'active'])
                ->where('congkhai', 1)
                ->where('giatri', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('danhmuc')->orWhere('danhmuc', '!=', 'birthday');
                })
                ->where('code', 'not like', '%BIRTHDAY%')
                ->where('code', 'not like', '%HAPPYBDAY%')
                ->where(function ($query) {
                    $query->whereNull('ten')
                        ->orWhere(function ($sub) {
                            $sub->where('ten', 'not like', '%sinh nhật%')
                                ->where('ten', 'not like', '%sinh nhat%');
                        });
                })
                ->where(function ($query) {
                    $query->whereNull('mota')
                        ->orWhere(function ($sub) {
                            $sub->where('mota', 'not like', '%sinh nhật%')
                                ->where('mota', 'not like', '%sinh nhat%');
                        });
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('ngaybatdau')->orWhere('ngaybatdau', '<=', $now);
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('ngayketthuc')->orWhere('ngayketthuc', '>=', $now);
                })
                ->orderBy('giatri', 'asc')
                ->limit(3)
                ->get();

            if ($promotions->isNotEmpty()) {
                $promoList = '';
                foreach ($promotions as $promo) {
                    $promoList .= $this->buildPromotionChatLine($promo);
                }

                return response()->json([
                    'reply' => "Dạ hiện tại VinaTech có một vài mã giảm giá nhỏ đang áp dụng công khai. Anh/chị có thể nhập mã khi chốt đơn trực tuyến nha:\n\n".trim($promoList),
                    'products' => [],
                ]);

            } else {
                return response()->json([
                    'reply' => 'Dạ hiện tại hệ thống chưa có mã giảm giá công khai phù hợp. Anh/chị có thể nhắn thêm sản phẩm muốn mua để Mia kiểm tra ưu đãi tốt nhất cho mình nhé.',
                    'products' => [],
                ]);
            }
        }

        // Địa chỉ / Liên hệ / Hotline / Showroom
        if ($this->containsAny($userMessage, ['địa chỉ', 'dia chi', 'cửa hàng', 'cua hang', 'showroom', 'ở đâu', 'o dau', 'vị trí', 'vi tri', 'đường đi', 'bản đồ', 'sđt', 'hotline', 'liên hệ', 'sdt', 'điện thoại'])) {
            return response()->json([
                'reply' => "Dạ, anh/chị có thể ghé thăm showroom hoặc liên hệ với VinaTech theo thông tin dưới đây ạ:\n\n📍 **Địa chỉ showroom**: Tòa nhà VinaTech, 123 Đường 3/2, Phường 11, Quận 10, TP. Hồ Chí Minh.\n📞 **Hotline hỗ trợ trực tuyến**: 1900 8080 (Miễn phí cuộc gọi, hoạt động từ 8:00 - 21:30 hàng ngày)\n📧 **Email hỗ trợ**: support@vinatech.com.vn\n💬 Anh/chị có thể chat trực tiếp tại đây hoặc nhấn nút **'Nhắn Admin'** ở phía trên để gặp nhân viên hỗ trợ trực tiếp nha!",
                'products' => [],
            ]);
        }

        // Chính sách bảo hành / Đổi trả
        if ($this->containsAny($userMessage, ['bảo hành', 'bao hanh', 'đổi trả', 'doi tra', 'lỗi', 'hỏng', 'trả máy', 'đổi máy', 'chính sách bảo hành'])) {
            return response()->json([
                'reply' => "Dạ, VinaTech luôn đặt quyền lợi của khách hàng lên hàng đầu với chính sách bảo hành cực kỳ an tâm:\n\n🛡️ **Chính sách đổi trả**: 1-đổi-1 trong vòng **30 ngày đầu tiên** nếu máy phát sinh lỗi phần cứng từ nhà sản xuất.\n🛠️ **Chính sách bảo hành**: Bảo hành chính hãng từ **12 đến 24 tháng** tùy dòng máy. VinaTech hỗ trợ gửi máy bảo hành về hãng hoàn toàn miễn phí.\n💻 **Hỗ trợ phần mềm**: Hỗ trợ cài đặt hệ điều hành, phần mềm văn phòng, đồ họa và vệ sinh máy định kỳ **trọn đời hoàn toàn miễn phí** tại cửa hàng.",
                'products' => [],
            ]);
        }

        // Chính sách giao hàng / Ship hàng
        if ($this->containsAny($userMessage, ['ship', 'phí ship', 'phi ship', 'vận chuyển', 'giao hàng', 'bao lâu nhận', 'nhận hàng', 'giao hỏa tốc', 'giao hoatoc'])) {
            return response()->json([
                'reply' => "Dạ về vận chuyển, VinaTech hỗ trợ giao hàng tận nơi siêu nhanh chóng:\n\n🚀 **Giao hỏa tốc 2 giờ**: Áp dụng trong nội thành TP. Hồ Chí Minh.\n🚚 **Giao hàng tiêu chuẩn toàn quốc**: Hoàn toàn **Miễn Phí Vận Chuyển** đối với các đơn hàng laptop.\n⏱️ **Thời gian nhận hàng**: Các tỉnh lân cận từ 1 - 2 ngày, khu vực miền Trung/Bắc từ 3 - 4 ngày làm việc. Quý khách được phép mở hộp kiểm tra máy trước khi thanh toán (COD) ạ.",
                'products' => [],
            ]);
        }

        // Trả góp
        if ($this->containsAny($userMessage, ['trả góp', 'tra gop', 'lãi suất', 'lai suat', 'góp', 'thẻ tín dụng'])) {
            return response()->json([
                'reply' => "Dạ, VinaTech hỗ trợ mua laptop trả góp với 2 hình thức cực kỳ đơn giản:\n\n1️⃣ **Trả góp 0% lãi suất qua thẻ tín dụng**: Áp dụng cho thẻ Visa, Mastercard, JCB của hơn 25 ngân hàng liên kết. Kỳ hạn linh hoạt từ 3, 6, 9 đến 12 tháng.\n2️⃣ **Trả góp qua công ty tài chính (HD Saison, Home Credit, MCredit)**: Chỉ cần CCCD gắn chip (từ 18 tuổi trở lên). Không cần chứng minh thu nhập, duyệt hồ sơ nhanh trong 15 phút, trả trước chỉ từ 10% giá trị máy.\n\nAnh/chị quan tâm dòng máy nào, nhắn em để em tính trước số tiền trả góp mỗi tháng cho mình tham khảo nhé!",
                'products' => [],
            ]);
        }

        // 2. KHỞI TẠO TRUY VẤN BIẾN THỂ SẢN PHẨM
        $variantsQuery = BienThe::with('sanPham');
        $specContexts = [];

        // So khớp thương hiệu động từ DB
        $brands = ThuongHieu::pluck('ten_thuonghieu')->toArray();
        $matchedBrand = null;
        foreach ($brands as $b) {
            $bLower = mb_strtolower($b);
            if (str_contains($userMessage, $bLower)) {
                $matchedBrand = $b;
                break;
            }
        }

        if ($matchedBrand) {
            $variantsQuery->whereHas('sanPham', function ($q) use ($matchedBrand) {
                $q->whereHas('thuongHieu', function ($sub) use ($matchedBrand) {
                    $sub->where('ten_thuonghieu', 'like', "%{$matchedBrand}%");
                })->orWhere('tenSP', 'like', "%{$matchedBrand}%");
            });
            $specContexts[] = 'hãng '.strtoupper($matchedBrand);
        } elseif ($this->containsAny($userMessage, ['apple', 'macbook'])) {
            $variantsQuery->whereHas('sanPham', function ($q) {
                $q->whereHas('thuongHieu', function ($sub) {
                    $sub->where('ten_thuonghieu', 'like', '%apple%');
                })->orWhere('tenSP', 'like', '%macbook%')->orWhere('tenSP', 'like', '%apple%');
            });
            $specContexts[] = 'hãng APPLE';
            $matchedBrand = 'Apple';
        }

        // So khớp danh mục động từ DB
        $isAccessoryRequest = $this->isAccessoryIntent($userMessage);
        $categories = DanhMuc::pluck('ten_danhmuc')->toArray();
        $matchedCategory = null;
        foreach ($categories as $cat) {
            $catLower = mb_strtolower($cat);
            if (str_contains($userMessage, $catLower)) {
                if ($isAccessoryRequest && ! $this->isAccessoryCategoryName($cat)) {
                    continue;
                }

                $matchedCategory = $cat;
                break;
            }
        }

        if ($matchedCategory) {
            $variantsQuery->whereHas('sanPham', function ($q) use ($matchedCategory) {
                $q->whereHas('danhMuc', function ($sub) use ($matchedCategory) {
                    $sub->where('ten_danhmuc', 'like', "%{$matchedCategory}%");
                });
            });
            $specContexts[] = 'danh mục '.$matchedCategory;
        }

        // Lọc theo nhu cầu/keyword nhu cầu gốc
        $isLaptopRequest = ! $isAccessoryRequest && (
            $this->isLaptopIntent($userMessage)
            || (! $matchedCategory && $this->containsAnyNormalized($userMessage, [
                'gia re',
                're nhat',
                'may re',
                'cau hinh',
                'tu van',
                'ban chay',
                'hot',
            ]))
        );

        if ($isLaptopRequest) {
            $this->applyLaptopOnlyFilter($variantsQuery);

            if (! in_array('laptop', $specContexts, true) && ! $matchedCategory) {
                $specContexts[] = 'laptop';
            }
        } elseif ($isAccessoryRequest) {
            $this->applyAccessoryOnlyFilter($variantsQuery);

            if (! in_array('phụ kiện', $specContexts, true) && ! $matchedCategory) {
                $specContexts[] = 'phụ kiện';
            }
        }

        $intent = 'general';
        if ($this->containsAny($userMessage, ['gaming', 'chơi game', 'game', 'pubg', 'valorant', 'lol', 'fifa'])) {
            $intent = 'gaming';
            $variantsQuery->where(function ($q) {
                $q->whereHas('sanPham', function ($sub) {
                    $sub->where('tenSP', 'like', '%gaming%')
                        ->orWhere('tenSP', 'like', '%tuf%')
                        ->orWhere('tenSP', 'like', '%rog%')
                        ->orWhere('tenSP', 'like', '%nitro%')
                        ->orWhere('tenSP', 'like', '%legion%')
                        ->orWhere('tenSP', 'like', '%nextgen%');
                })
                    ->orWhere('ten_bienthe', 'like', '%rtx%')
                    ->orWhere('ten_bienthe', 'like', '%gtx%')
                    ->orWhere('thuoc_tinh_json', 'like', '%RTX%')
                    ->orWhere('thuoc_tinh_json', 'like', '%GTX%');
            });
            $specContexts[] = 'Gaming';
        } elseif ($this->containsAny($userMessage, ['sinh viên', 'học tập', 'văn phòng', 'office', 'online', 'kế toán'])) {
            $intent = 'office';
            $variantsQuery->where(function ($q) {
                $q->whereHas('sanPham', function ($sub) {
                    $sub->where('tenSP', 'like', '%vivobook%')
                        ->orWhere('tenSP', 'like', '%zenbook%')
                        ->orWhere('tenSP', 'like', '%book%')
                        ->orWhere('tenSP', 'like', '%aspire%')
                        ->orWhere('tenSP', 'like', '%pavilion%')
                        ->orWhere('tenSP', 'like', '%inspiron%');
                })
                    ->orWhere('ten_bienthe', 'like', '%intel core i3%')
                    ->orWhere('ten_bienthe', 'like', '%intel core i5%')
                    ->orWhere('ten_bienthe', 'like', '%8gb%');
            });
            $specContexts[] = 'Văn phòng/Học tập';
        } elseif ($this->containsAny($userMessage, ['đồ họa', 'design', 'thiết kế', 'photoshop', 'illustrator', 'autocad', 'dựng phim', 'edit video'])) {
            $intent = 'graphics';
            $variantsQuery->where(function ($q) {
                $q->whereHas('sanPham', function ($sub) {
                    $sub->where('tenSP', 'like', '%proart%')
                        ->orWhere('tenSP', 'like', '%macbook%')
                        ->orWhere('tenSP', 'like', '%creator%');
                })
                    ->orWhere('ten_bienthe', 'like', '%rtx%')
                    ->orWhere('ten_bienthe', 'like', '%i7%')
                    ->orWhere('ten_bienthe', 'like', '%r7%')
                    ->orWhere('thuoc_tinh_json', 'like', '%RTX%')
                    ->orWhere('thuoc_tinh_json', 'like', '%Intel Core i7%');
            });
            $specContexts[] = 'Đồ họa/Thiết kế';
        }

        // Lọc theo cấu hình RAM
        preg_match('/\b(8gb|16gb|32gb|64gb)\b/i', $userMessage, $ramMatch);
        if (! empty($ramMatch)) {
            $ram = strtolower($ramMatch[1]);
            $variantsQuery->where(function ($q) use ($ram) {
                $q->where('ten_bienthe', 'like', "%{$ram}%")
                    ->orWhere('thuoc_tinh_json', 'like', "%{$ram}%")
                    ->orWhereHas('sanPham', function ($sub) use ($ram) {
                        $sub->where('tenSP', 'like', "%{$ram}%")
                            ->orWhere('thong_so_ky_thuat', 'like', "%{$ram}%");
                    });
            });
            $specContexts[] = 'RAM '.strtoupper($ram);
        }

        // Lọc theo SSD/Ổ cứng
        preg_match('/\b(256gb|512gb|1tb|2tb)\b/i', $userMessage, $ssdMatch);
        if (! empty($ssdMatch)) {
            $ssd = strtolower($ssdMatch[1]);
            $variantsQuery->where(function ($q) use ($ssd) {
                $q->where('ten_bienthe', 'like', "%{$ssd}%")
                    ->orWhere('thuoc_tinh_json', 'like', "%{$ssd}%")
                    ->orWhereHas('sanPham', function ($sub) use ($ssd) {
                        $sub->where('tenSP', 'like', "%{$ssd}%")
                            ->orWhere('thong_so_ky_thuat', 'like', "%{$ssd}%");
                    });
            });
            $specContexts[] = 'SSD '.strtoupper($ssd);
        }

        // Lọc theo CPU
        $cpu = null;
        if ($this->containsAny($userMessage, ['intel core i3', 'core i3', 'i3'])) {
            $cpu = 'i3';
        } elseif ($this->containsAny($userMessage, ['intel core i5', 'core i5', 'i5'])) {
            $cpu = 'i5';
        } elseif ($this->containsAny($userMessage, ['intel core i7', 'core i7', 'i7'])) {
            $cpu = 'i7';
        } elseif ($this->containsAny($userMessage, ['intel core i9', 'core i9', 'i9'])) {
            $cpu = 'i9';
        } elseif ($this->containsAny($userMessage, ['ryzen 3', 'r3'])) {
            $cpu = 'r3';
        } elseif ($this->containsAny($userMessage, ['ryzen 5', 'r5'])) {
            $cpu = 'r5';
        } elseif ($this->containsAny($userMessage, ['ryzen 7', 'r7'])) {
            $cpu = 'r7';
        } elseif ($this->containsAny($userMessage, ['ryzen 9', 'r9'])) {
            $cpu = 'r9';
        } elseif ($this->containsAny($userMessage, ['m1'])) {
            $cpu = 'm1';
        } elseif ($this->containsAny($userMessage, ['m2'])) {
            $cpu = 'm2';
        } elseif ($this->containsAny($userMessage, ['m3'])) {
            $cpu = 'm3';
        } elseif ($this->containsAny($userMessage, ['ultra 5'])) {
            $cpu = 'ultra 5';
        } elseif ($this->containsAny($userMessage, ['ultra 7'])) {
            $cpu = 'ultra 7';
        }

        if ($cpu) {
            $variantsQuery->where(function ($q) use ($cpu) {
                $tokens = [$cpu];
                if ($cpu === 'i3') {
                    $tokens[] = 'intel core i3';
                }
                if ($cpu === 'i5') {
                    $tokens[] = 'intel core i5';
                }
                if ($cpu === 'i7') {
                    $tokens[] = 'intel core i7';
                }
                if ($cpu === 'i9') {
                    $tokens[] = 'intel core i9';
                }
                if ($cpu === 'r3') {
                    $tokens[] = 'ryzen 3';
                    $tokens[] = 'r3';
                }
                if ($cpu === 'r5') {
                    $tokens[] = 'ryzen 5';
                    $tokens[] = 'r5';
                }
                if ($cpu === 'r7') {
                    $tokens[] = 'ryzen 7';
                    $tokens[] = 'r7';
                }
                if ($cpu === 'r9') {
                    $tokens[] = 'ryzen 9';
                    $tokens[] = 'r9';
                }
                if ($cpu === 'm1') {
                    $tokens[] = 'apple m1';
                    $tokens[] = 'm1';
                }
                if ($cpu === 'm2') {
                    $tokens[] = 'apple m2';
                    $tokens[] = 'm2';
                }
                if ($cpu === 'm3') {
                    $tokens[] = 'apple m3';
                    $tokens[] = 'm3';
                }
                if ($cpu === 'ultra 5') {
                    $tokens[] = 'ultra 5';
                    $tokens[] = 'core ultra 5';
                }
                if ($cpu === 'ultra 7') {
                    $tokens[] = 'ultra 7';
                    $tokens[] = 'core ultra 7';
                }

                $q->where(function ($subQ) use ($tokens) {
                    foreach ($tokens as $tok) {
                        $subQ->orWhere('ten_bienthe', 'like', "%{$tok}%")
                            ->orWhere('thuoc_tinh_json', 'like', "%{$tok}%")
                            ->orWhereHas('sanPham', function ($sub) use ($tok) {
                                $sub->where('tenSP', 'like', "%{$tok}%")
                                    ->orWhere('thong_so_ky_thuat', 'like', "%{$tok}%");
                            });
                    }
                });
            });
            $specContexts[] = 'CPU '.strtoupper($cpu);
        }

        // Lọc theo Dòng máy (Series) phổ biến
        $series = null;
        $seriesList = ['tuf', 'rog', 'nitro', 'thinkpad', 'ideapad', 'legion', 'latitude', 'vostro', 'inspiron', 'zenbook', 'vivobook', 'proart', 'macbook', 'victus', 'pavilion', 'spectre', 'envy', 'nextgen', 'swift', 'aspire'];
        foreach ($seriesList as $ser) {
            if (str_contains($userMessage, $ser)) {
                $series = $ser;
                break;
            }
        }

        if ($series) {
            $variantsQuery->where(function ($q) use ($series) {
                $q->where('ten_bienthe', 'like', "%{$series}%")
                    ->orWhereHas('sanPham', function ($sub) use ($series) {
                        $sub->where('tenSP', 'like', "%{$series}%");
                    });
            });
            $specContexts[] = 'dòng '.strtoupper($series);
        }

        // 3. XỬ LÝ LỌC GIÁ CỦA CHATBOT
        $prices = $this->extractPrices($userMessage);
        $priceContext = '';

        if (! empty($prices)) {
            if (count($prices) >= 2) {
                // Có khoảng giá xác định (từ X triệu đến Y triệu)
                $min = $prices[0];
                $max = $prices[1];
                $variantsQuery->whereBetween('gia', [$min, $max]);
                $priceContext = 'tầm giá từ '.$this->formatMillions($min).'tr đến '.$this->formatMillions($max).'tr';
            } else {
                // Chỉ có 1 mức giá duy nhất -> Áp dụng khoảng giá fuzzy (+-15%) trừ khi có hướng so sánh rõ ràng
                $price = $prices[0];
                if ($this->containsAny($userMessage, ['dưới', 'thấp hơn', 'tối đa', 'max', 'nhỏ hơn', 'ít hơn', 'rẻ hơn'])) {
                    $variantsQuery->where('gia', '<=', $price);
                    $priceContext = 'tầm giá dưới '.$this->formatMillions($price).'tr';
                } elseif ($this->containsAny($userMessage, ['trên', 'hơn', 'cao hơn', 'tối thiểu', 'min', 'lớn hơn', 'trở lên'])) {
                    $variantsQuery->where('gia', '>=', $price);
                    $priceContext = 'tầm giá trên '.$this->formatMillions($price).'tr';
                } else {
                    // Khoảng quanh mức giá đó (+- 15%)
                    $min = $price * 0.85;
                    $max = $price * 1.15;
                    $variantsQuery->whereBetween('gia', [$min, $max]);
                    $priceContext = 'tầm giá quanh '.$this->formatMillions($price).'tr (từ '.$this->formatMillions($min).'tr đến '.$this->formatMillions($max).'tr)';
                }
            }
        }

        // 4. XỬ LÝ SẮP XẾP VÀ THỨ TỰ (TRƯỚC KHI TAKE KẾT QUẢ)
        $sortingApplied = false;

        // Rẻ nhất
        if ($this->containsAny($userMessage, ['rẻ nhất', 'giá rẻ', 'thấp nhất', 'gia thap', 're nhat'])) {
            $variantsQuery->orderBy('gia', 'asc');
            $sortingApplied = true;
            $specContexts[] = 'giá rẻ nhất';
        }
        // Đắt nhất / cấu hình khủng nhất
        elseif ($this->containsAny($userMessage, ['đắt nhất', 'dat nhat', 'cao nhất', 'khủng nhất', 'vip nhất', 'mạnh nhất', 'manh nhat'])) {
            $variantsQuery->orderBy('gia', 'desc');
            $sortingApplied = true;
            $specContexts[] = 'hiệu năng cao nhất';
        }
        // Mới nhất
        elseif ($this->containsAny($userMessage, ['mới nhất', 'moi nhat', 'mới về', 'moi ve'])) {
            $variantsQuery->orderBy('id_bienthe', 'desc');
            $sortingApplied = true;
            $specContexts[] = 'mới nhất';
        }
        // Đánh giá tốt nhất / Bán chạy nhất
        elseif ($this->containsAny($userMessage, ['tốt nhất', 'đánh giá cao', 'ban chay', 'bán chạy', 'được chuộng', 'hot', 'hot nhất', 'review tốt', 'đánh giá tốt'])) {
            $variantsQuery->withCount('reviews')->orderByDesc('reviews_count');
            $sortingApplied = true;
            $specContexts[] = 'đánh giá tốt/bán chạy';
        }

        if (! $sortingApplied) {
            // Thứ tự ngẫu nhiên nếu không yêu cầu sắp xếp cụ thể
            $variantsQuery->inRandomOrder();
        }

        // Chỉ lấy các sản phẩm đang có số lượng hàng tồn kho > 0
        $variantsQuery->where('soluong', '>', 0);

        // Lấy 5 kết quả
        $variants = $variantsQuery->take(5)->get();

        // 5. XÂY DỰNG CÂU TRẢ LỜI CỦA MIA
        $specDesc = ! empty($specContexts) ? implode(', ', $specContexts) : '';

        if ($variants->isNotEmpty()) {
            if ($isAccessoryRequest) {
                $accessoryDesc = $specDesc && $specDesc !== 'phụ kiện' ? " **{$specDesc}**" : '';
                $reply = $priceContext
                    ? "Dạ em tìm được một vài phụ kiện{$accessoryDesc} thuộc **{$priceContext}** phù hợp cho mình đây ạ:"
                    : "Dạ em gửi anh/chị một vài phụ kiện{$accessoryDesc} bên em đang có sẵn ạ. Anh/chị nhắn thêm loại phụ kiện hoặc tầm giá mong muốn để Mia lọc sát hơn nha!";
            } elseif ($priceContext && $specDesc) {
                $reply = "Dạ em tìm được vài cấu hình laptop **{$specDesc}** thuộc **{$priceContext}** cực tốt cho mình đây ạ:";
            } elseif ($priceContext) {
                $reply = "Dạ với **{$priceContext}**, đây là những lựa chọn phù hợp và sịn sò nhất gửi khách yêu tham khảo ạ:";
            } elseif ($specDesc) {
                $reply = "Dạ gửi khách yêu các dòng laptop **{$specDesc}** bên em đang có sẵn và rất hot ạ. Anh/chị nhắn thêm tầm giá mong muốn để em lọc sát ngân sách hơn nha!";
            } else {
                if ($this->containsAny($userMessage, ['xin chào', 'chào', 'hello', 'hi'])) {
                    $reply = 'Dạ em chào anh/chị ạ! Em là Mia, trợ lý tư vấn VinaTech. Anh/chị cần tìm máy tầm giá bao nhiêu hoặc phục vụ nhu cầu học tập, làm việc gì để em tìm cấu hình phù hợp nhất ạ?';
                } else {
                    $reply = 'Dạ em gửi anh/chị một số cấu hình laptop bán chạy và được ưa chuộng nhất hiện nay tại VinaTech ạ. Anh/chị có thể cho em biết thêm khoảng giá hoặc thương hiệu yêu thích để em hỗ trợ tốt nhất nhé! 😊';
                }
            }
        } else {
            // Không tìm thấy sản phẩm khớp
            if ($isAccessoryRequest) {
                $reply = 'Dạ hiện tại Mia chưa tìm thấy phụ kiện phù hợp với yêu cầu này. Anh/chị có thể nhắn rõ hơn như chuột, bàn phím, tai nghe, sạc hoặc tầm giá để em lọc lại ngay nhé.';
            } elseif ($priceContext && $specDesc) {
                $reply = "Dạ hiện tại bên em chưa có cấu hình laptop **{$specDesc}** nào trong **{$priceContext}** phù hợp hoàn toàn ạ. Anh/chị có thể điều chỉnh nhẹ ngân sách hoặc đổi sang nhu cầu/hãng khác để em tìm bản gần nhất nha.";
            } elseif ($priceContext) {
                $reply = "Dạ hiện tại bên em chưa có cấu hình phù hợp trong **{$priceContext}** rồi ạ. Anh/chị có thể tham khảo tầm giá khác một chút hoặc nhắn dòng máy yêu thích để em tìm cấu hình gần nhất nhé.";
            } else {
                $reply = 'Dạ Mia chưa tìm thấy cấu hình nào khớp hoàn toàn với thông tin yêu cầu của anh/chị. Anh/chị có thể nhắn thêm cụ thể tên máy, tầm giá hoặc hãng máy để em lọc lại ngay cho mình nhé! 💖';
            }
        }

        return response()->json([
            'reply' => $reply,
            'products' => $variants->values(),
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

    private function buildPromotionChatLine(Promotion $promo): string
    {
        $promoName = $promo->ten ?: 'Mã giảm giá';
        $line = "🎁 **{$promoName}**\n";
        $line .= "   - Mã: `{$promo->code}` ({$this->formatPromotionDiscount($promo)})\n";

        if ($promo->mota) {
            $line .= "   - Chi tiết: {$promo->mota}\n";
        }

        if ($promo->dieu_kien_tang) {
            $line .= '   - Điều kiện: Đơn hàng từ '.number_format($promo->dieu_kien_tang, 0, ',', '.')."đ\n";
        }

        return $line."\n";
    }

    private function formatPromotionDiscount(Promotion $promo): string
    {
        $value = (float) $promo->giatri;

        if ($promo->loai === 'percent') {
            return 'Giảm '.rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',').'%';
        }

        if ($promo->loai === 'maxprice') {
            return 'Giảm tối đa '.number_format($value, 0, ',', '.').'đ';
        }

        return 'Giảm '.number_format($value, 0, ',', '.').'đ';
    }

    private function containsAnyNormalized(string $text, array $keywords): bool
    {
        $normalizedText = $this->normalizeSearchText($text);

        foreach ($keywords as $keyword) {
            if (str_contains($normalizedText, $this->normalizeSearchText($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSearchText(string $text): string
    {
        $text = mb_strtolower($text);

        return strtr($text, [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ]);
    }

    private function isAccessoryIntent(string $text): bool
    {
        return $this->containsAnyNormalized($text, [
            'phu kien',
            'chuot',
            'mouse',
            'ban phim',
            'keyboard',
            'tai nghe',
            'headphone',
            'headset',
            'balo',
            'sac',
            'adapter',
            'usb',
            'hub',
            'lot chuot',
        ]);
    }

    private function isAccessoryCategoryName(string $text): bool
    {
        return $this->containsAnyNormalized($text, [
            'phu kien',
            'chuot',
            'mouse',
            'ban phim',
            'keyboard',
            'tai nghe',
            'headphone',
            'headset',
            'balo',
            'sac',
            'adapter',
            'usb',
            'hub',
            'lot chuot',
        ]);
    }

    private function isLaptopIntent(string $text): bool
    {
        return $this->containsAnyNormalized($text, [
            'laptop',
            'may tinh',
            'may xach tay',
            'macbook',
            'gaming',
            'van phong',
            'hoc tap',
            'sinh vien',
            'do hoa',
            'lap',
        ]);
    }

    private function applyLaptopOnlyFilter($query): void
    {
        $laptopTerms = ['laptop', 'macbook', 'may tinh', 'notebook'];
        $accessoryTerms = [
            'phu kien',
            'chuot',
            'mouse',
            'ban phim',
            'keyboard',
            'tai nghe',
            'headphone',
            'headset',
            'balo',
            'sac',
            'adapter',
            'usb',
            'hub',
            'lot chuot',
        ];

        $query->whereHas('sanPham', function ($product) use ($laptopTerms, $accessoryTerms) {
            $product->where(function ($productScope) use ($laptopTerms) {
                foreach ($laptopTerms as $term) {
                    $productScope->orWhere('tenSP', 'like', "%{$term}%")
                        ->orWhereHas('danhMuc', function ($category) use ($term) {
                            $category->where('ten_danhmuc', 'like', "%{$term}%")
                                ->orWhereHas('danhMucCha', function ($parent) use ($term) {
                                    $parent->where('ten_danhmuc', 'like', "%{$term}%");
                                });
                        });
                }
            });

            $product->where(function ($productScope) use ($accessoryTerms) {
                foreach ($accessoryTerms as $term) {
                    $productScope->where('tenSP', 'not like', "%{$term}%");
                }
            });

            $product->whereDoesntHave('danhMuc', function ($category) use ($accessoryTerms) {
                $category->where(function ($categoryScope) use ($accessoryTerms) {
                    foreach ($accessoryTerms as $term) {
                        $categoryScope->orWhere('ten_danhmuc', 'like', "%{$term}%")
                            ->orWhereHas('danhMucCha', function ($parent) use ($term) {
                                $parent->where('ten_danhmuc', 'like', "%{$term}%");
                            });
                    }
                });
            });
        });
    }

    private function applyAccessoryOnlyFilter($query): void
    {
        $accessoryTerms = [
            'phụ kiện',
            'phu kien',
            'chuột',
            'chuot',
            'mouse',
            'bàn phím',
            'ban phim',
            'keyboard',
            'tai nghe',
            'headphone',
            'headset',
            'balo',
            'sạc',
            'sac',
            'adapter',
            'usb',
            'hub',
            'lót chuột',
            'lot chuot',
        ];

        $query->whereHas('sanPham', function ($product) use ($accessoryTerms) {
            $product->where(function ($productScope) use ($accessoryTerms) {
                foreach ($accessoryTerms as $term) {
                    $productScope->orWhere('tenSP', 'like', "%{$term}%")
                        ->orWhereHas('danhMuc', function ($category) use ($term) {
                            $category->where('ten_danhmuc', 'like', "%{$term}%")
                                ->orWhereHas('danhMucCha', function ($parent) use ($term) {
                                    $parent->where('ten_danhmuc', 'like', "%{$term}%");
                                });
                        });
                }
            });

            $product->where('tenSP', 'not like', '%laptop%')
                ->where('tenSP', 'not like', '%macbook%')
                ->whereDoesntHave('danhMuc', function ($category) {
                    $category->where('ten_danhmuc', 'like', '%laptop%')
                        ->orWhere('ten_danhmuc', 'like', '%macbook%')
                        ->orWhereHas('danhMucCha', function ($parent) {
                            $parent->where('ten_danhmuc', 'like', '%laptop%')
                                ->orWhere('ten_danhmuc', 'like', '%macbook%');
                        });
                });
        });
    }

    private function extractPrices(string $text): array
    {
        $prices = [];
        // Pattern 1: Số + đơn vị (15tr, 15 triệu, 15.5tr)
        preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?:tr|triệu|m|trđ|triệu đồng)/u', $text, $matches);
        foreach ($matches[1] as $val) {
            $prices[] = $this->normalizePrice($val);
        }

        // Pattern 2: Số thuần túy lớn (ví dụ 15000000)
        if (empty($prices)) {
            preg_match_all('/\b\d{6,}\b/', $text, $matches);
            foreach ($matches[0] as $val) {
                $prices[] = (int) $val;
            }
        }

        sort($prices);

        return array_unique($prices);
    }

    private function normalizePrice($val): int
    {
        $val = str_replace(',', '.', $val);
        $num = (float) $val;

        if ($num < 1000) {
            return (int) ($num * 1000000);
        }

        return (int) $num;
    }

    private function formatMillions($amount): string
    {
        $millions = $amount / 1000000;

        return (round($millions, 1) == round($millions))
            ? number_format($millions, 0, ',', '.')
            : number_format($millions, 1, ',', '.');
    }
}
