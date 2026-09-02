<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportCatalogImagesSeeder extends Seeder
{
    public function run(): void
    {
        $imageRoot = public_path('ảnh laptop');

        if (!is_dir($imageRoot)) {
            throw new RuntimeException("Không tìm thấy thư mục ảnh: {$imageRoot}");
        }

        $usbCategoryId = DB::table('danhmuc')->where('ten_danhmuc', 'USB')->value('id_danhmuc');
        if (!$usbCategoryId) {
            $usbCategoryId = DB::table('danhmuc')->insertGetId([
                'id_danhmuc_cha' => 9,
                'ten_danhmuc' => 'USB',
                'trangthai' => 'active',
            ]);
        }

        $otherAccessoryCategoryId = DB::table('danhmuc')->where('ten_danhmuc', 'Phụ kiện khác')->value('id_danhmuc');
        if (!$otherAccessoryCategoryId) {
            $otherAccessoryCategoryId = DB::table('danhmuc')->insertGetId([
                'id_danhmuc_cha' => 9,
                'ten_danhmuc' => 'Phụ kiện khác',
                'trangthai' => 'active',
            ]);
        }

        $categories = [
            'gaming' => 2,
            'office' => 3,
            'macbook' => 4,
            'mouse' => 10,
            'keyboard' => 11,
            'usb' => (int) $usbCategoryId,
            'other' => (int) $otherAccessoryCategoryId,
        ];

        $brandIds = [];
        foreach ([
            'Asus' => [2, 3, 7],
            'HP' => [2, 3, 7],
            'Lenovo' => [2, 3, 7],
            'MSI' => [2, 3],
            'Apple' => [4],
            'Logitech' => [10, 11],
            'Acer' => [2, 3, 7],
            'Dell' => [2, 3, 7],
            'Targus' => [11, $otherAccessoryCategoryId],
            'E-Dra' => [10, 11],
            'ADATA' => [$usbCategoryId],
            'Kioxia' => [$usbCategoryId],
            'SanDisk' => [$usbCategoryId],
            'Ugreen' => [$otherAccessoryCategoryId],
            'Rezo' => [$otherAccessoryCategoryId],
            'AVA+' => [$otherAccessoryCategoryId],
            'OEM' => [$otherAccessoryCategoryId],
        ] as $brand => $categoryIds) {
            $brandId = DB::table('thuonghieu')->where('ten_thuonghieu', $brand)->value('id_thuonghieu');
            $payload = [
                'danh_muc_ids' => json_encode(array_map('strval', $categoryIds), JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ];

            if ($brandId) {
                DB::table('thuonghieu')->where('id_thuonghieu', $brandId)->update($payload);
            } else {
                $brandId = DB::table('thuonghieu')->insertGetId($payload + [
                    'ten_thuonghieu' => $brand,
                    'created_at' => now(),
                ]);
            }
            $brandIds[$brand] = (int) $brandId;
        }

        $products = [
            ['Apple MacBook Air M1 256GB 2020 Chính hãng Apple Việt Nam', 'Apple MacBook Air M1 256GB 2020 I Chính hãng Apple Việt Nam', 'macbook', 'Apple', 'macbook-air-silver-select-201810.webp'],
            ['MacBook Air M5 13 inch 2026 10CPU 8GPU 16GB 512GB Chính hãng Apple Việt Nam', 'MacBook Air M5 13 inch 2026 10CPU 8GPU 16GB 512GB  Chính hãng Apple Việt Nam', 'macbook', 'Apple'],
            ['MacBook Neo 13 inch A18 Pro 2026 6CPU 5GPU 8GB 256GB Chính hãng Apple Việt Nam', 'MacBook Neo 13 inch A18 Pro 2026 6CPU 5GPU 8GB 256GB  Chính hãng Apple Việt Nam', 'macbook', 'Apple'],
            ['Laptop Acer Gaming Aspire 7 A715-59G-59RD', 'Laptop Acer Gaming Aspire 7 A715-59G-59RD', 'gaming', 'Acer'],
            ['Laptop ASUS VivoBook 14 X1407CA-LY008W', 'Laptop ASUS VivoBook 14 X1407CA-LY008W', 'office', 'Asus'],
            ['Laptop Dell 14 DC14250 DC4C5386W', 'Laptop Dell 14 DC14250 DC4C5386W', 'office', 'Dell'],
            ['Laptop Dell Pro 15 Essential PV15250 VKVKD', 'Laptop Dell Pro 15 Essential PV15250 VKVKD - Nhập khẩu chính hãng', 'office', 'Dell'],
            ['Laptop HP OmniBook 5 AI 16-AF1048TU BZ7Q9PA', 'Laptop HP Omnibook 5 AI 16-AF1048TU BZ7Q9PA', 'office', 'HP'],
            ['Laptop HP Victus 15-FA2731TX B85LNPA', 'Laptop HP Victus 15-FA2731TX B85LNPA', 'gaming', 'HP'],
            ['Laptop Lenovo IdeaPad Slim 3 14IWC11 83RQ002PVN', 'Laptop Lenovo IdeaPad Slim 3 14IWC11 83RQ002PVN', 'office', 'Lenovo'],
            ['Laptop MSI Katana 15 B13VEK-2440VN', 'Laptop MSI Katana 15 B13VEK-2440VN', 'gaming', 'MSI'],
            ['Laptop ASUS Vivobook Go 14 E1404', 'lap/1', 'office', 'Asus'],
            ['Laptop Lenovo IdeaPad Slim 3 16IPH11', 'lap/2', 'office', 'Lenovo'],
            ['Laptop Acer Aspire Lite 14 AL14-45P', 'lap/3', 'office', 'Acer'],
            ['Laptop Lenovo Yoga Slim 7 14IPH11', 'lap/4', 'office', 'Lenovo'],
            ['Laptop ASUS TUF Gaming A14 FA401', 'lap/5', 'gaming', 'Asus'],
            ['Laptop ASUS ExpertBook P1403', 'lap/6', 'office', 'Asus'],
            ['Laptop MSI Modern 15 F1MG-1225VN', 'lap/8', 'office', 'MSI'],
            ['Laptop Acer Predator Helios Neo 16 PHN16-I31', 'lap/9', 'gaming', 'Acer'],
            ['Laptop Lenovo Yoga Slim 7 14AGP11', 'lap/10', 'office', 'Lenovo'],
            ['Chuột gaming không dây E-Dra Thunderbird TM620W Triple Mode', 'Chuột gaming không dây E-Dra Thunderbird TM620W Triple Mode', 'mouse', 'E-Dra'],
            ['Bàn phím Bluetooth Targus EcoSmart AKB868AP', 'Bàn phím Bluetooth Targus EcoSmart AKB868AP', 'keyboard', 'Targus'],
            ['Bàn phím cơ gaming E-Dra EK3104K đen', 'Bàn phím cơ gaming E-Dra EK3104K đen', 'keyboard', 'E-Dra'],
            ['Combo bàn phím và chuột không dây Logitech MK295 Silent', 'Combo bàn phím + Chuột không dây Logitech MK295 Silent', 'keyboard', 'Logitech'],
            ['USB ADATA UC310 64GB', 'usb1', 'usb', 'ADATA'],
            ['USB Kioxia 64GB USB 3.2 Gen 1 màu đen', 'usb2', 'usb', 'Kioxia'],
            ['USB Kioxia 64GB USB 3.2 Gen 1 màu trắng', 'usb3', 'usb', 'Kioxia'],
            ['USB SanDisk Ultra Fit USB 3.0', 'usb4', 'usb', 'SanDisk'],
            ['USB SanDisk 32GB USB 3.0', 'usb5', 'usb', 'SanDisk'],
            ['Cáp Ugreen USB-C to Lightning Silicone US387 dài 1m', 'Cáp Ugreen USB-C to Lightning silicone US387 dài 1m', 'other', 'Ugreen'],
            ['Hub chuyển đổi Ugreen USB-C 5 trong 1 CM478 15495', 'Hub chuyển đổi Ugreen USB-C 5 IN 1 CM478 15495', 'other', 'Ugreen'],
            ['Đế laptop xoay 360 độ AVA+', 'de2', 'other', 'AVA+'],
            ['Đế kê laptop gấp gọn bằng nhôm', 'Đế', 'other', 'OEM'],
            ['Loa Bluetooth Rezo TouchBass 1 màu đen', 'loa', 'other', 'Rezo'],
            ['Loa Bluetooth di động màu đen', 'loa2', 'other', 'OEM'],
            ['Túi chống sốc laptop Targus CityGear 3', 'túi', 'other', 'Targus'],
        ];

        // Giá cơ sở theo đúng thứ tự danh sách trên, dùng để tạo biến thể thật.
        $basePrices = [
            15990000, 32990000, 18990000, 18990000, 15490000, 16990000,
            18490000, 23990000, 22490000, 14490000, 21990000, 11990000,
            16490000, 9990000, 26990000, 31990000, 14990000, 13990000,
            38990000, 28490000, 890000, 1190000, 790000, 749000,
            179000, 159000, 169000, 219000, 149000, 349000, 1090000,
            690000, 390000, 590000, 449000, 790000,
        ];

        DB::transaction(function () use ($products, $basePrices, $imageRoot, $categories, $brandIds): void {
            foreach ($products as $productIndex => $definition) {
                [$name, $folder, $categoryKey, $brand] = $definition;
                $preferredMain = $definition[4] ?? null;
                $absoluteFolder = $imageRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
                $images = $this->imageFiles($absoluteFolder);
                if (!$images) {
                    $this->command?->warn("Bỏ qua {$name}: thư mục không có ảnh.");
                    continue;
                }

                $mainIndex = $this->mainImageIndex($images, $preferredMain);
                $mainFile = $images[$mainIndex];
                unset($images[$mainIndex]);
                $gallery = array_slice(array_values($images), 0, 10);
                $publicFolder = 'ảnh laptop/' . str_replace('\\', '/', $folder);

                $productId = DB::table('sanpham')->where('tenSP', $name)->value('id_sanpham');
                $payload = [
                    'id_danhmuc' => $categories[$categoryKey],
                    'id_thuonghieu' => $brandIds[$brand],
                    'tenSP' => $name,
                    'trangthai' => '1',
                    'hinhanh' => $publicFolder . '/' . basename($mainFile),
                    'khoiluong' => null,
                    'thong_so_ky_thuat' => json_encode(['Nguồn dữ liệu' => 'Ảnh sản phẩm trong dự án'], JSON_UNESCAPED_UNICODE),
                ];

                if ($productId) {
                    DB::table('sanpham')->where('id_sanpham', $productId)->update($payload);
                } else {
                    $payload['SKU'] = 'IMP-' . strtoupper(substr(sha1($name), 0, 10));
                    $productId = DB::table('sanpham')->insertGetId($payload);
                }

                DB::table('bienthe_hinhanh')->where('id_sanpham', $productId)->delete();
                foreach ($gallery as $order => $image) {
                    DB::table('bienthe_hinhanh')->insert([
                        'id_sanpham' => $productId,
                        'duongdan' => $publicFolder . '/' . basename($image),
                        'thutu' => $order,
                    ]);
                }

                foreach ($this->variantDefinitions($categoryKey, $folder, $basePrices[$productIndex]) as $variant) {
                    $existingVariant = DB::table('bienthe')
                        ->where('id_sanpham', $productId)
                        ->where('ten_bienthe', $variant['name'])
                        ->first();
                    $variantPayload = [
                        'gia' => $variant['price'],
                        'soluong' => $variant['stock'],
                        'thuoc_tinh_json' => json_encode($variant['attributes'], JSON_UNESCAPED_UNICODE),
                    ];
                    if ($existingVariant) {
                        DB::table('bienthe')->where('id_bienthe', $existingVariant->id_bienthe)->update($variantPayload);
                    } else {
                        DB::table('bienthe')->insert($variantPayload + [
                            'id_sanpham' => $productId,
                            'ten_bienthe' => $variant['name'],
                        ]);
                    }
                }
            }
        });
    }

    private function variantDefinitions(string $category, string $folder, int $basePrice): array
    {
        $attribute = static fn (string $id, string $name, string $value, ?string $hex = null): array => [
            'id_thuoctinh' => $id, 'ten_thuoctinh' => $name, 'giatri' => $value, 'hex' => $hex,
        ];

        if (in_array($category, ['gaming', 'office', 'macbook'], true)) {
            $isMac = $category === 'macbook';
            $standardRam = (str_contains($folder, 'M1') || str_contains($folder, 'Neo')) ? '8GB' : '16GB';
            $standardStorage = str_contains($folder, '512GB') ? '512GB SSD' : '256GB SSD';
            $color = $isMac ? 'Bạc' : 'Đen';
            return [
                [
                    'name' => "{$standardRam} - {$standardStorage} - {$color}",
                    'price' => $basePrice, 'stock' => 12,
                    'attributes' => [
                        $attribute('ram', 'RAM', $standardRam),
                        $attribute('storage', 'Ổ cứng', $standardStorage),
                        $attribute('color', 'Màu sắc', $color, $isMac ? '#C0C0C0' : '#111827'),
                    ],
                ],
                [
                    'name' => '16GB - 512GB SSD - Xám',
                    'price' => $basePrice + ($isMac ? 4000000 : 2000000), 'stock' => 8,
                    'attributes' => [
                        $attribute('ram', 'RAM', '16GB'),
                        $attribute('storage', 'Ổ cứng', '512GB SSD'),
                        $attribute('color', 'Màu sắc', 'Xám', '#6B7280'),
                    ],
                ],
            ];
        }

        if ($category === 'usb') {
            $capacity = str_contains($folder, 'usb5') ? '32GB' : '64GB';
            $color = str_contains($folder, 'usb3') ? 'Trắng' : 'Đen';
            return [[
                'name' => "{$capacity} - {$color}", 'price' => $basePrice, 'stock' => 35,
                'attributes' => [
                    $attribute('capacity', 'Dung lượng', $capacity),
                    $attribute('color', 'Màu sắc', $color, $color === 'Trắng' ? '#F8FAFC' : '#111827'),
                ],
            ]];
        }

        $variants = [[
            'name' => 'Phiên bản màu đen', 'price' => $basePrice, 'stock' => 20,
            'attributes' => [$attribute('color', 'Màu sắc', 'Đen', '#111827')],
        ]];
        if (in_array($category, ['mouse', 'keyboard'], true)) {
            $variants[] = [
                'name' => 'Phiên bản màu trắng', 'price' => $basePrice, 'stock' => 15,
                'attributes' => [$attribute('color', 'Màu sắc', 'Trắng', '#F8FAFC')],
            ];
        }
        return $variants;
    }

    private function imageFiles(string $folder): array
    {
        if (!is_dir($folder)) {
            return [];
        }

        $files = array_values(array_filter(scandir($folder) ?: [], function (string $file) use ($folder): bool {
            return is_file($folder . DIRECTORY_SEPARATOR . $file)
                && preg_match('/\.(png|jpe?g|webp)$/i', $file) === 1;
        }));
        natcasesort($files);
        return array_values($files);
    }

    private function mainImageIndex(array $images, ?string $preferred): int
    {
        if ($preferred !== null) {
            $index = array_search($preferred, $images, true);
            if ($index !== false) {
                return (int) $index;
            }
        }

        foreach ($images as $index => $image) {
            if (preg_match('/(?:^|_)0[1-9](?:_|\.)/i', $image) || preg_match('/^1\.(png|jpe?g|webp)$/i', $image)) {
                return $index;
            }
        }
        return 0;
    }
}
