<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

use App\Models\BienThe;
use App\Models\DanhMuc;
use App\Models\SanPham;
use App\Models\ThuongHieu;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

try {
    $products = [
        [
            'brand' => 'Acer',
            'category' => 'Laptop Gaming',
            'name' => 'Laptop Gaming Acer NextGen Helios Neo 16 PHN16 71 53M7 (NH.QLUSV.004)',
            'price' => 31990000,
            'discount' => 2000000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/309192/acer-nextgen-helios-neo-16-phn16-71-53m7-i5-nhqlusv004-thumb-600x600.jpg',
            'desc' => "★ Shopee Mall Chính Hãng ★\nLaptop Gaming Quốc Dân Acer NextGen Helios Neo 16 trang bị Intel Core i5-13500HX, RTX 4050 6GB cực mạnh mẽ.\n- Màn hình 16 inch WUXGA 165Hz sRGB 100% cực nét.\n- Tản nhiệt kim loại lỏng mát mẻ, đèn nền RGB 4 vùng cực ngầu.\n- RAM 16GB DDR5, SSD 512GB siêu tốc.\n- Tặng kèm Balo NextGen chính hãng.\nBảo hành VIP 3S1 - 1 đổi 1 trong 3 ngày.",
            'variants' => ['16GB - 512GB'],
        ],
        [
            'brand' => 'ASUS',
            'category' => 'Laptop Gaming',
            'name' => 'Laptop ASUS ROG Strix G15 G513RC-HN038W Ryzen 7 6800H / RTX 3050',
            'price' => 24990000,
            'discount' => 1500000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/287769/asus-rog-strix-gaming-g15-g513ih-r7-hn015w-051022-045330-600x600.jpg',
            'desc' => "Asus ROG Strix G15 - Đẳng cấp chuẩn eSports!\nCấu hình mạnh mẽ với AMD Ryzen 7 6800H và card đồ họa NVIDIA RTX 3050 4GB.\n- Màn hình 15.6 inch FHD 144Hz.\n- Bàn phím Overstroke với Aura Sync RGB.\n- Tản nhiệt chất lỏng kim loại cho CPU mát mẻ.\nSản phẩm chính hãng phân phối bởi Shopee Mall.",
            'variants' => ['8GB - 512GB', '16GB - 512GB'],
        ],
        [
            'brand' => 'MSI',
            'category' => 'Laptop Gaming',
            'name' => 'Laptop Gaming MSI Cyborg 15 A12UCX-281VN Intel Core i5 12450H',
            'price' => 18490000,
            'discount' => 1000000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/304724/msi-cyborg-15-a12ucx-i5-281vn-thumb-600x600.jpg',
            'desc' => "MSI Cyborg 15 với thiết kế xuyên thấu Cyberpunk cực chất!\n- Intel Core i5-12450H & RTX 2050 4GB.\n- Thiết kế cực nhẹ chỉ 1.98kg.\n- Bàn phím đèn nền xanh độc đáo.\n- Màn hình 144Hz mượt mà.\nHàng chính hãng MSI Việt Nam - Bảo hành 24 tháng.",
            'variants' => ['8GB - 512GB'],
        ],
        [
            'brand' => 'Lenovo',
            'category' => 'Laptop Gaming',
            'name' => 'Laptop Lenovo Legion 5 15IAH7 82RC004PVN i5-12500H',
            'price' => 26990000,
            'discount' => 3000000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/293026/lenovo-legion-5-15iah7-i5-82rc004pvn-thumb-600x600.jpg',
            'desc' => "Huyền thoại Lenovo Legion 5 đã quay trở lại!\n- Intel Core i5 12500H 12 nhân 16 luồng.\n- Card rời RTX 3050Ti 4GB cân mọi tựa game.\n- Tản nhiệt Coldfront 4.0 đỉnh cao.\n- Màn hình 15.6 inch FHD 165Hz chuẩn màu 100% sRGB.",
            'variants' => ['16GB - 512GB'],
        ],
        [
            'brand' => 'Dell',
            'category' => 'Laptop Gaming',
            'name' => 'Laptop Gaming Dell G15 5530 i7-13650HX / RTX 4050 6GB',
            'price' => 34990000,
            'discount' => 2500000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/316527/dell-gaming-g15-5530-i7-i7h165w11gr4060-thumb-600x600.jpg',
            'desc' => "Dell G15 5530 thiết kế cảm hứng từ Alienware.\n- Cấu hình siêu khủng: Core i7 13650HX, RTX 4050.\n- Tản nhiệt cực lớn với buồng hơi hiện đại.\n- Bền bỉ vô đối chuẩn quân đội.\nHỗ trợ trả góp 0% qua thẻ tín dụng.",
            'variants' => ['16GB - 512GB'],
        ],
        [
            'brand' => 'HP',
            'category' => 'Laptop Gaming',
            'name' => 'Laptop HP Victus 15-fa0115TX i5-12500H / RTX 3050',
            'price' => 19990000,
            'discount' => 1200000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/294101/hp-victus-15-fa0115tx-i5-7c0x1pa-thumb-600x600.jpg',
            'desc' => "HP Victus 15 - Gaming thanh lịch nhưng mạnh mẽ.\n- Sở hữu Intel Core i5 12500H mạnh mẽ.\n- RTX 3050 cân tốt game AAA.\n- Màn hình 15.6 FHD 144Hz.\n- Thiết kế tối giản phù hợp cả học tập lẫn chơi game.\nSản phẩm Fullbox 100%.",
            'variants' => ['8GB - 512GB', '16GB - 512GB'],
        ],
        [
            'brand' => 'Apple',
            'category' => 'MacBook',
            'name' => 'Apple MacBook Air M1 256GB 2020',
            'price' => 18990000,
            'discount' => 500000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/231244/macbook-air-m1-2020-gray-600x600.jpg',
            'desc' => "Siêu phẩm MacBook Air M1 chính hãng Apple Việt Nam (VN/A).\n- Chip Apple M1 siêu mạnh, pin lên tới 18 giờ.\n- Thiết kế nhôm nguyên khối sang trọng, mỏng nhẹ chỉ 1.29kg.\n- Hoàn hảo cho dân văn phòng, sinh viên và lập trình viên.",
            'variants' => ['8GB - 256GB'],
        ],
        [
            'brand' => 'Apple',
            'category' => 'MacBook',
            'name' => 'Apple MacBook Pro M3 14 inch 2023',
            'price' => 39990000,
            'discount' => 2000000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/316930/macbook-pro-14-inch-m3-2023-space-grey-thumb-600x600.jpg',
            'desc' => "MacBook Pro M3 cực khủng dành cho dân chuyên.\n- Chip M3 thế hệ mới với công nghệ dò tia phần cứng.\n- Màn hình Liquid Retina XDR độ sáng 1600 nits.\n- Hệ thống 6 loa cực đỉnh.\nShopee Mall cam kết bảo hành chính hãng toàn quốc.",
            'variants' => ['8GB - 512GB', '16GB - 1TB'],
        ],
        [
            'brand' => 'Gigabyte',
            'category' => 'Laptop Gaming',
            'name' => 'Laptop Gigabyte G5 MF-F2VN333SH i5-12450H / RTX 4050',
            'price' => 21990000,
            'discount' => 1500000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/302821/gigabyte-g5-mf-i5-f2vn333sh-thumb-600x600.jpg',
            'desc' => "Gigabyte G5 - Hiệu năng trên giá thành cực tốt.\n- RTX 4050 6GB thế hệ mới với DLSS 3 siêu xịn.\n- Tản nhiệt Windforce thế hệ mới với 5 ống đồng.\n- Màn hình 144Hz chơi game FPS mượt mà.\nBảo hành chính hãng 24 tháng.",
            'variants' => ['8GB - 512GB'],
        ],
        [
            'brand' => 'Acer',
            'category' => 'Laptop Văn Phòng',
            'name' => 'Laptop Acer Swift 3 SF314-43-R4X3 Ryzen 5 5500U',
            'price' => 14990000,
            'discount' => 1000000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/263560/acer-swift-3-sf314-43-r4x3-r5-nxab1sv004-600x600.jpg',
            'desc' => "Acer Swift 3 - Sang trọng, mỏng nhẹ, pin trâu.\n- Vỏ nhôm nguyên khối, nặng chỉ 1.19kg.\n- CPU AMD Ryzen 5 mạnh mẽ và tiết kiệm pin.\n- Màn hình 14 inch FHD 100% sRGB.\nLựa chọn số 1 cho dân văn phòng và sinh viên.",
            'variants' => ['16GB - 512GB'],
        ],
        [
            'brand' => 'ASUS',
            'category' => 'Laptop Văn Phòng',
            'name' => 'Laptop ASUS Vivobook 15 OLED A1505ZA i5-12500H',
            'price' => 16490000,
            'discount' => 800000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/304192/asus-vivobook-15-oled-a1505za-i5-l1201w-thumb-600x600.jpg',
            'desc' => "Vivobook 15 OLED - Trải nghiệm màn hình tuyệt đỉnh.\n- Màn hình OLED rực rỡ, chuẩn màu DCI-P3 100%.\n- Cấu hình i5 dòng H hiệu năng cao.\n- Thiết kế bản lề mở 180 độ linh hoạt.\nPhù hợp làm đồ họa nhẹ và học tập.",
            'variants' => ['8GB - 512GB'],
        ],
        [
            'brand' => 'Lenovo',
            'category' => 'Laptop Văn Phòng',
            'name' => 'Laptop Lenovo IdeaPad Slim 5 Light 14ABR8 Ryzen 5 7530U',
            'price' => 15990000,
            'discount' => 1200000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/307185/lenovo-ideapad-slim-5-light-14abr8-r5-82xs002jvn-thumb-600x600.jpg',
            'desc' => "IdeaPad Slim 5 Light cực kỳ mỏng nhẹ (chỉ 1.17kg).\n- Chip AMD Ryzen 7000 series mới nhất.\n- Vỏ hợp kim Magnesium cao cấp.\n- Bàn phím Lenovo gõ cực thích.\nBảo hành Premium Care tận nhà 2 năm.",
            'variants' => ['16GB - 512GB'],
        ],
        [
            'brand' => 'MSI',
            'category' => 'Laptop Văn Phòng',
            'name' => 'Laptop MSI Modern 14 C11M-011VN i3-1115G4',
            'price' => 10490000,
            'discount' => 1500000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/299623/msi-modern-14-c11m-i3-011vn-thumb-600x600.jpg',
            'desc' => "Laptop quốc dân giá rẻ MSI Modern 14.\n- Dành cho sinh viên và dân văn phòng cơ bản.\n- Thiết kế mỏng nhẹ 1.4kg, chuẩn quân đội bền bỉ.\n- Hành trình phím tốt, có đèn nền LED trắng.\nHàng mới 100%, bảo hành 12 tháng.",
            'variants' => ['8GB - 512GB'],
        ],
        [
            'brand' => 'Dell',
            'category' => 'Laptop Văn Phòng',
            'name' => 'Laptop Dell Inspiron 15 3520 i5-1235U',
            'price' => 16990000,
            'discount' => 500000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/294248/dell-inspiron-15-3520-i5-n5i5122w1-thumb-600x600.jpg',
            'desc' => "Laptop Dell Inspiron bền bỉ theo thời gian.\n- Chip i5 thế hệ 12 mượt mà mọi tác vụ.\n- Màn hình 15.6 inch 120Hz mượt mà (hiếm có trong tầm giá).\n- Bàn phím số tiện dụng cho kế toán.\nSản phẩm phân phối chính hãng.",
            'variants' => ['8GB - 512GB', '16GB - 512GB'],
        ],
        [
            'brand' => 'HP',
            'category' => 'Laptop Văn Phòng',
            'name' => 'Laptop HP Pavilion 14-dv2073TU i5-1235U',
            'price' => 17490000,
            'discount' => 1000000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/294103/hp-pavilion-14-dv2073tu-i5-7c0p2pa-thumb-600x600.jpg',
            'desc' => "HP Pavilion 14 thiết kế vỏ nhôm vàng sang trọng.\n- Âm thanh B&O nghe nhạc cực hay.\n- Chip i5 thế hệ 12, Ram 16GB dư sức đa nhiệm.\n- Nhỏ gọn tiện mang đi học, đi cà phê.\nCam kết mới 100%, đóng gói cẩn thận.",
            'variants' => ['16GB - 512GB'],
        ],
        [
            'brand' => 'LG',
            'category' => 'Laptop Văn Phòng',
            'name' => 'Laptop LG Gram 14 2023 i5-1340P',
            'price' => 28990000,
            'discount' => 3000000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/304245/lg-gram-14-i5-14z90r-gah53a5-thumb-600x600.jpg',
            'desc' => "Vua siêu nhẹ LG Gram 14 - Cân nặng vỏn vẹn 999 gram.\n- Khung hợp kim Nano Carbon siêu bền chuẩn quân sự Mỹ.\n- Chip i5 thế hệ 13 dòng P mạnh mẽ.\n- Pin khủng có thể dùng hơn 15 tiếng.\nBảo hành chính hãng 12 tháng tại nhà.",
            'variants' => ['16GB - 512GB'],
        ],
        [
            'brand' => 'ASUS',
            'category' => 'Laptop Đồ Họa',
            'name' => 'Laptop ASUS Zenbook Pro 14 Duo OLED UX8402ZA',
            'price' => 45990000,
            'discount' => 2500000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/287768/asus-zenbook-pro-14-duo-oled-ux8402za-i7-m9051w-thumb-600x600.jpg',
            'desc' => "Kiệt tác đồ họa Zenbook Pro 14 Duo với 2 màn hình cảm ứng OLED!\n- Màn hình chính 2.8K OLED 120Hz chuẩn màu Pantone.\n- CPU i7-12700H đỉnh cao render.\n- Phù hợp với nhà sáng tạo nội dung, editor, designer chuyên nghiệp.\nSố lượng có hạn!",
            'variants' => ['16GB - 1TB'],
        ],
        [
            'brand' => 'Acer',
            'category' => 'Laptop Gaming',
            'name' => 'Laptop Acer Nitro 5 Tiger AN515 58 52SP i5 12500H / RTX 3050',
            'price' => 20990000,
            'discount' => 1500000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/282956/acer-nitro-5-tiger-an515-58-52sp-i5-nhqfsvn001-thumb-600x600.jpg',
            'desc' => "Quốc dân Gaming Acer Nitro 5 Tiger.\n- CPU dòng H gen 12 & RTX 3050 cực khỏe.\n- Tản nhiệt kép tốt nhất phân khúc.\n- Màn hình 144Hz chuẩn gaming.\nBảo hành VIP 3S1 - Lỗi đổi mới hoặc bảo hành cực nhanh.",
            'variants' => ['8GB - 512GB', '16GB - 512GB'],
        ],
        [
            'brand' => 'MSI',
            'category' => 'Laptop Gaming',
            'name' => 'Laptop MSI Katana 15 B13VEK-252VN i7-13620H / RTX 4050',
            'price' => 28990000,
            'discount' => 2000000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/302824/msi-katana-15-b13vek-i7-252vn-thumb-600x600.jpg',
            'desc' => "Bảo kiếm Katana 15 của MSI với cấu hình khủng 2023.\n- Intel i7 Gen 13 cực mạnh & RTX 4050 6GB.\n- Bàn phím RGB 4 vùng bắt mắt.\n- Cooler Boost 5 mát lạnh chiến game thâu đêm.\nHàng new seal 100%, có VAT.",
            'variants' => ['16GB - 512GB'],
        ],
        [
            'brand' => 'Lenovo',
            'category' => 'Laptop Văn Phòng',
            'name' => 'Laptop Lenovo ThinkPad E14 Gen 4 Ryzen 5 5625U',
            'price' => 18990000,
            'discount' => 1000000,
            'image' => 'https://cdn.tgdd.vn/Products/Images/44/293022/lenovo-thinkpad-e14-gen-4-r5-21eb002hvn-thumb-600x600.jpg',
            'desc' => "Đẳng cấp doanh nhân ThinkPad E14 bền bỉ vô song.\n- Bàn phím ThinkPad trứ danh gõ siêu êm.\n- Trackpoint đỏ huyền thoại.\n- Vỏ kim loại đạt chuẩn độ bền quân đội Mỹ.\nThích hợp coder, kế toán, văn phòng cao cấp.",
            'variants' => ['8GB - 512GB'],
        ],
    ];

    $count = 0;
    foreach ($products as $p) {
        $dm = DanhMuc::firstOrCreate(['ten_danhmuc' => $p['category']], ['ten_danhmuc' => $p['category'], 'trang_thai' => 1, 'hinh_anh' => '']);
        $th = ThuongHieu::firstOrCreate(['ten_thuonghieu' => $p['brand']], ['ten_thuonghieu' => $p['brand'], 'trang_thai' => 1, 'hinh_anh' => '']);

        $sp = SanPham::create([
            'id_danhmuc' => $dm->id_danhmuc,
            'id_thuonghieu' => $th->id_thuonghieu,
            'tenSP' => $p['name'],
            'maSP' => strtoupper(Str::random(8)),
            'giaSP' => $p['price'],
            'giamgiaSP' => $p['discount'],
            'hinhanh' => json_encode([$p['image']]), // Assuming 'hinhanh' might take a JSON string or string
            'soluong' => 100,
            'khoiluong' => 2.0,
            'mota' => $p['desc'],
        ]);

        foreach ($p['variants'] as $v) {
            BienThe::create([
                'id_sanpham' => $sp->id_sanpham,
                'ten_bienthe' => $v,
                'gia' => $p['price'] + ($v == '16GB - 512GB' ? 1000000 : 0),
                'soluong' => 50,
            ]);
        }
        $count++;
    }
    echo "Successfully inserted $count products!\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
