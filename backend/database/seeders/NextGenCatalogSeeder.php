<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NextGenCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = $this->categoryIds();
        $brandIds = $this->brandIds();

        DB::transaction(function () use ($categoryIds, $brandIds) {
            foreach ($this->products() as $product) {
                $productId = $this->upsertProduct($product, $categoryIds, $brandIds);

                DB::table('bienthe')->where('id_sanpham', $productId)->delete();

                foreach ($product['variants'] as $variant) {
                    DB::table('bienthe')->insert([
                        'id_sanpham' => $productId,
                        'ten_bienthe' => $variant['name'],
                        'gia' => $variant['price'],
                        'soluong' => $variant['stock'],
                        'thuoc_tinh_json' => json_encode($variant['attributes'], JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }
        });

        Cache::put('sanpham_cache_bust', (string) microtime(true));
        Cache::forget('sanpham_attribute_options');
    }

    private function categoryIds(): array
    {
        $names = [
            'Laptop Gaming',
            'Laptop văn phòng',
            'Macbook',
            'Laptop học sinh',
            'Chuột',
            'Bàn phím',
            'Tai nghe',
            'Lót chuột',
            'Ổ cứng SSD',
            'RAM',
            'Màn hình',
            'Hub chuyển đổi',
            'Webcam',
            'Balo laptop',
            'Router',
            'Microphone',
        ];

        foreach ($names as $name) {
            $existing = DB::table('danhmuc')->where('ten_danhmuc', $name)->first();

            if (! $existing) {
                DB::table('danhmuc')->insert([
                    'ten_danhmuc' => $name,
                    'trangthai' => 1,
                    'id_danhmuc_cha' => null,
                ]);
            }
        }

        return DB::table('danhmuc')->pluck('id_danhmuc', 'ten_danhmuc')->all();
    }

    private function brandIds(): array
    {
        $names = [
            'Asus',
            'HP',
            'Lenovo',
            'MSI',
            'Apple',
            'Logitech',
            'Razer',
            'Akko',
            'DareU',
            'Acer',
            'Dell',
            'Gigabyte',
            'LG',
            'Samsung',
            'Kingston',
            'Crucial',
            'Corsair',
            'SteelSeries',
            'HyperX',
            'Keychron',
            'Ugreen',
            'TP-Link',
            'Elgato',
        ];

        foreach ($names as $name) {
            $existing = DB::table('thuonghieu')->where('ten_thuonghieu', $name)->first();

            if (! $existing) {
                DB::table('thuonghieu')->insert([
                    'ten_thuonghieu' => $name,
                    'danh_muc_ids' => null,
                    'logo' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return DB::table('thuonghieu')->pluck('id_thuonghieu', 'ten_thuonghieu')->all();
    }

    private function upsertProduct(array $product, array $categoryIds, array $brandIds): int
    {
        $payload = [
            'id_danhmuc' => $categoryIds[$product['category']],
            'id_thuonghieu' => $brandIds[$product['brand']],
            'tenSP' => $product['name'],
            'SKU' => $product['sku'],
            'trangthai' => 1,
            'hinhanh' => null,
            'khoiluong' => $product['weight'],
            'thong_so_ky_thuat' => json_encode($product['specs'], JSON_UNESCAPED_UNICODE),
        ];

        $existing = DB::table('sanpham')->where('SKU', $product['sku'])->first();

        if ($existing) {
            DB::table('sanpham')->where('id_sanpham', $existing->id_sanpham)->update($payload);

            return (int) $existing->id_sanpham;
        }

        return (int) DB::table('sanpham')->insertGetId($payload);
    }

    private function laptopVariants(string $cpu, string $gpu, int $basePrice, array $stocks = [8, 6]): array
    {
        return [
            [
                'name' => '16GB RAM / 512GB SSD',
                'price' => $basePrice,
                'stock' => $stocks[0],
                'attributes' => [
                    $this->attr('RAM', '16GB'),
                    $this->attr('SSD', '512GB'),
                    $this->attr('CPU', $cpu),
                    $this->attr('GPU', $gpu),
                    $this->attr('Màu sắc', 'Đen'),
                ],
            ],
            [
                'name' => '32GB RAM / 1TB SSD',
                'price' => $basePrice + 3500000,
                'stock' => $stocks[1],
                'attributes' => [
                    $this->attr('RAM', '32GB'),
                    $this->attr('SSD', '1TB'),
                    $this->attr('CPU', $cpu),
                    $this->attr('GPU', $gpu),
                    $this->attr('Màu sắc', 'Xám'),
                ],
            ],
        ];
    }

    private function simpleVariant(string $name, int $price, int $stock, array $attributes): array
    {
        return [
            'name' => $name,
            'price' => $price,
            'stock' => $stock,
            'attributes' => array_map(fn ($item) => $this->attr($item[0], $item[1], $item[2] ?? null), $attributes),
        ];
    }

    private function attr(string $name, string $value, ?string $hex = null): array
    {
        return array_filter([
            'ten_thuoctinh' => $name,
            'giatri' => $value,
            'ma_mau' => $hex,
        ], fn ($value) => $value !== null);
    }

    private function products(): array
    {
        return [
            [
                'sku' => 'NGP-LT-001',
                'name' => 'Laptop Gaming ASUS ROG Strix G16 G614JV-N4084W',
                'category' => 'Laptop Gaming',
                'brand' => 'Asus',
                'weight' => 2.5,
                'specs' => ['screen' => '16 inch FHD+ 165Hz', 'battery' => '90Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i7-13650HX', 'RTX 4060 8GB', 39990000, [9, 5]),
            ],
            [
                'sku' => 'NGP-LT-002',
                'name' => 'Laptop Gaming ASUS TUF Gaming A15 FA507NV-LP110W',
                'category' => 'Laptop Gaming',
                'brand' => 'Asus',
                'weight' => 2.2,
                'specs' => ['screen' => '15.6 inch FHD 144Hz', 'battery' => '90Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('AMD Ryzen 7 7735HS', 'RTX 4060 8GB', 28990000, [10, 6]),
            ],
            [
                'sku' => 'NGP-LT-003',
                'name' => 'Laptop ASUS Vivobook S 16 S3607VA-RP056W',
                'category' => 'Laptop văn phòng',
                'brand' => 'Asus',
                'weight' => 1.7,
                'specs' => ['screen' => '16 inch 2.5K', 'battery' => '70Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-13500H', 'Intel Iris Xe', 18990000, [12, 8]),
            ],
            [
                'sku' => 'NGP-LT-004',
                'name' => 'Laptop ASUS Zenbook 14 OLED UX3405MA-PP152W',
                'category' => 'Laptop văn phòng',
                'brand' => 'Asus',
                'weight' => 1.2,
                'specs' => ['screen' => '14 inch 3K OLED 120Hz', 'battery' => '75Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core Ultra 7 155H', 'Intel Arc Graphics', 29990000, [7, 4]),
            ],
            [
                'sku' => 'NGP-LT-005',
                'name' => 'Laptop Lenovo Legion Slim 5 16IRH8 82YA00BSVN',
                'category' => 'Laptop Gaming',
                'brand' => 'Lenovo',
                'weight' => 2.4,
                'specs' => ['screen' => '16 inch WQXGA 165Hz', 'battery' => '80Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i7-13700H', 'RTX 4060 8GB', 36990000, [8, 5]),
            ],
            [
                'sku' => 'NGP-LT-006',
                'name' => 'Laptop Lenovo LOQ 15IRX9 83DV00D5VN',
                'category' => 'Laptop Gaming',
                'brand' => 'Lenovo',
                'weight' => 2.4,
                'specs' => ['screen' => '15.6 inch FHD 144Hz', 'battery' => '60Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-13450HX', 'RTX 4050 6GB', 23990000, [11, 7]),
            ],
            [
                'sku' => 'NGP-LT-007',
                'name' => 'Laptop Lenovo ThinkPad E14 Gen 5 21JK00HGVN',
                'category' => 'Laptop văn phòng',
                'brand' => 'Lenovo',
                'weight' => 1.4,
                'specs' => ['screen' => '14 inch WUXGA', 'battery' => '57Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-1335U', 'Intel Iris Xe', 17990000, [13, 8]),
            ],
            [
                'sku' => 'NGP-LT-008',
                'name' => 'Laptop Lenovo IdeaPad Slim 5 14ABR8 82XE004VVN',
                'category' => 'Laptop học sinh',
                'brand' => 'Lenovo',
                'weight' => 1.5,
                'specs' => ['screen' => '14 inch WUXGA', 'battery' => '56Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('AMD Ryzen 5 7530U', 'AMD Radeon Graphics', 13990000, [15, 9]),
            ],
            [
                'sku' => 'NGP-LT-009',
                'name' => 'Laptop MSI Cyborg 15 A13VE-218VN',
                'category' => 'Laptop Gaming',
                'brand' => 'MSI',
                'weight' => 1.98,
                'specs' => ['screen' => '15.6 inch FHD 144Hz', 'battery' => '53.5Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i7-13620H', 'RTX 4050 6GB', 24990000, [9, 5]),
            ],
            [
                'sku' => 'NGP-LT-010',
                'name' => 'Laptop MSI Katana 15 B13VFK-676VN',
                'category' => 'Laptop Gaming',
                'brand' => 'MSI',
                'weight' => 2.25,
                'specs' => ['screen' => '15.6 inch FHD 144Hz', 'battery' => '53.5Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i7-13620H', 'RTX 4060 8GB', 30990000, [8, 4]),
            ],
            [
                'sku' => 'NGP-LT-011',
                'name' => 'Laptop MSI Modern 14 C13M-609VN',
                'category' => 'Laptop văn phòng',
                'brand' => 'MSI',
                'weight' => 1.4,
                'specs' => ['screen' => '14 inch FHD', 'battery' => '39.3Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-1335U', 'Intel Iris Xe', 12990000, [14, 8]),
            ],
            [
                'sku' => 'NGP-LT-012',
                'name' => 'Laptop Acer Nitro V 15 ANV15-51-58AN',
                'category' => 'Laptop Gaming',
                'brand' => 'Acer',
                'weight' => 2.1,
                'specs' => ['screen' => '15.6 inch FHD 144Hz', 'battery' => '57Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-13420H', 'RTX 4050 6GB', 22990000, [12, 7]),
            ],
            [
                'sku' => 'NGP-LT-013',
                'name' => 'Laptop Acer Predator Helios Neo 16 PHN16-71-53M7',
                'category' => 'Laptop Gaming',
                'brand' => 'Acer',
                'weight' => 2.6,
                'specs' => ['screen' => '16 inch WUXGA 165Hz', 'battery' => '90Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-13500HX', 'RTX 4060 8GB', 34990000, [7, 5]),
            ],
            [
                'sku' => 'NGP-LT-014',
                'name' => 'Laptop Acer Swift Go 14 OLED SFG14-73-71ZX',
                'category' => 'Laptop văn phòng',
                'brand' => 'Acer',
                'weight' => 1.32,
                'specs' => ['screen' => '14 inch 2.8K OLED 90Hz', 'battery' => '65Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core Ultra 7 155H', 'Intel Arc Graphics', 23990000, [10, 6]),
            ],
            [
                'sku' => 'NGP-LT-015',
                'name' => 'Laptop Dell Gaming G15 5530 i7H165W11GR4060',
                'category' => 'Laptop Gaming',
                'brand' => 'Dell',
                'weight' => 2.65,
                'specs' => ['screen' => '15.6 inch FHD 165Hz', 'battery' => '86Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i7-13650HX', 'RTX 4060 8GB', 37990000, [8, 4]),
            ],
            [
                'sku' => 'NGP-LT-016',
                'name' => 'Laptop Dell Inspiron 15 3520 N5I5052W1',
                'category' => 'Laptop học sinh',
                'brand' => 'Dell',
                'weight' => 1.83,
                'specs' => ['screen' => '15.6 inch FHD 120Hz', 'battery' => '41Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-1235U', 'Intel Iris Xe', 13990000, [16, 8]),
            ],
            [
                'sku' => 'NGP-LT-017',
                'name' => 'Laptop Dell XPS 13 9340 Ultra 7',
                'category' => 'Laptop văn phòng',
                'brand' => 'Dell',
                'weight' => 1.19,
                'specs' => ['screen' => '13.4 inch FHD+', 'battery' => '55Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core Ultra 7 155H', 'Intel Arc Graphics', 41990000, [5, 3]),
            ],
            [
                'sku' => 'NGP-LT-018',
                'name' => 'Laptop HP Victus 16-r0129TX 8C5N4PA',
                'category' => 'Laptop Gaming',
                'brand' => 'HP',
                'weight' => 2.3,
                'specs' => ['screen' => '16.1 inch FHD 144Hz', 'battery' => '70Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-13500H', 'RTX 4050 6GB', 25990000, [11, 6]),
            ],
            [
                'sku' => 'NGP-LT-019',
                'name' => 'Laptop HP Pavilion 15-eg3094TU 8C5L3PA',
                'category' => 'Laptop học sinh',
                'brand' => 'HP',
                'weight' => 1.75,
                'specs' => ['screen' => '15.6 inch FHD', 'battery' => '41Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-1335U', 'Intel Iris Xe', 15990000, [14, 8]),
            ],
            [
                'sku' => 'NGP-LT-020',
                'name' => 'Laptop HP Envy x360 14-fc0090TU',
                'category' => 'Laptop văn phòng',
                'brand' => 'HP',
                'weight' => 1.39,
                'specs' => ['screen' => '14 inch 2.8K OLED Touch', 'battery' => '59Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core Ultra 5 125U', 'Intel Graphics', 26990000, [7, 4]),
            ],
            [
                'sku' => 'NGP-LT-021',
                'name' => 'MacBook Air 13 inch M2 2022 8CPU 8GPU',
                'category' => 'Macbook',
                'brand' => 'Apple',
                'weight' => 1.24,
                'specs' => ['screen' => '13.6 inch Liquid Retina', 'battery' => '52.6Wh', 'warranty' => '12 tháng'],
                'variants' => $this->laptopVariants('Apple M2', 'Apple 8-core GPU', 23990000, [12, 6]),
            ],
            [
                'sku' => 'NGP-LT-022',
                'name' => 'MacBook Air 15 inch M3 2024 8CPU 10GPU',
                'category' => 'Macbook',
                'brand' => 'Apple',
                'weight' => 1.51,
                'specs' => ['screen' => '15.3 inch Liquid Retina', 'battery' => '66.5Wh', 'warranty' => '12 tháng'],
                'variants' => $this->laptopVariants('Apple M3', 'Apple 10-core GPU', 32990000, [9, 5]),
            ],
            [
                'sku' => 'NGP-LT-023',
                'name' => 'MacBook Pro 14 inch M3 Pro 11CPU 14GPU',
                'category' => 'Macbook',
                'brand' => 'Apple',
                'weight' => 1.61,
                'specs' => ['screen' => '14.2 inch Liquid Retina XDR', 'battery' => '72.4Wh', 'warranty' => '12 tháng'],
                'variants' => $this->laptopVariants('Apple M3 Pro', 'Apple 14-core GPU', 52990000, [5, 3]),
            ],
            [
                'sku' => 'NGP-LT-024',
                'name' => 'Laptop Gigabyte G5 KF-E3VN313SH',
                'category' => 'Laptop Gaming',
                'brand' => 'Gigabyte',
                'weight' => 2.08,
                'specs' => ['screen' => '15.6 inch FHD 144Hz', 'battery' => '54Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-12500H', 'RTX 4060 8GB', 24990000, [10, 6]),
            ],
            [
                'sku' => 'NGP-LT-025',
                'name' => 'Laptop LG Gram 16 2024 16Z90S-G.AH75A5',
                'category' => 'Laptop văn phòng',
                'brand' => 'LG',
                'weight' => 1.19,
                'specs' => ['screen' => '16 inch WQXGA', 'battery' => '77Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core Ultra 7 155H', 'Intel Arc Graphics', 39990000, [6, 4]),
            ],
            [
                'sku' => 'NGP-LT-026',
                'name' => 'Laptop ASUS ExpertBook B1 B1402CVA-NK0156W',
                'category' => 'Laptop văn phòng',
                'brand' => 'Asus',
                'weight' => 1.45,
                'specs' => ['screen' => '14 inch FHD', 'battery' => '42Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-1335U', 'Intel UHD Graphics', 14990000, [12, 8]),
            ],
            [
                'sku' => 'NGP-LT-027',
                'name' => 'Laptop Lenovo Yoga Slim 7 14IMH9 83CV003VVN',
                'category' => 'Laptop văn phòng',
                'brand' => 'Lenovo',
                'weight' => 1.39,
                'specs' => ['screen' => '14 inch OLED 120Hz', 'battery' => '65Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core Ultra 7 155H', 'Intel Arc Graphics', 28990000, [8, 4]),
            ],
            [
                'sku' => 'NGP-LT-028',
                'name' => 'Laptop Acer Aspire 7 A715-76G-5806',
                'category' => 'Laptop Gaming',
                'brand' => 'Acer',
                'weight' => 2.1,
                'specs' => ['screen' => '15.6 inch FHD 144Hz', 'battery' => '50Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-12450H', 'RTX 3050 4GB', 18990000, [13, 8]),
            ],
            [
                'sku' => 'NGP-LT-029',
                'name' => 'Laptop HP 15-fd0304TU i5-1334U',
                'category' => 'Laptop học sinh',
                'brand' => 'HP',
                'weight' => 1.59,
                'specs' => ['screen' => '15.6 inch FHD', 'battery' => '41Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-1334U', 'Intel Iris Xe', 13990000, [15, 9]),
            ],
            [
                'sku' => 'NGP-LT-030',
                'name' => 'Laptop Dell Latitude 5440 Core i5-1335U',
                'category' => 'Laptop văn phòng',
                'brand' => 'Dell',
                'weight' => 1.39,
                'specs' => ['screen' => '14 inch FHD', 'battery' => '54Wh', 'warranty' => '24 tháng'],
                'variants' => $this->laptopVariants('Intel Core i5-1335U', 'Intel Iris Xe', 19990000, [9, 5]),
            ],
            [
                'sku' => 'NGP-AC-001',
                'name' => 'Bàn phím Logitech G Pro X TKL Light Speed',
                'category' => 'Bàn phím',
                'brand' => 'Logitech',
                'weight' => 0.92,
                'specs' => ['connectivity' => 'LIGHTSPEED Wireless, Bluetooth, USB-C', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('Tactile Switch / Đen', 3890000, 18, [['Switch', 'Tactile'], ['Kết nối', 'Wireless'], ['Màu sắc', 'Đen', '#111827']])],
            ],
            [
                'sku' => 'NGP-AC-002',
                'name' => 'Chuột Gaming Logitech G102 LightSync Gen 2',
                'category' => 'Chuột',
                'brand' => 'Logitech',
                'weight' => 0.09,
                'specs' => ['sensor' => '8000 DPI', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('Đen / USB', 490000, 35, [['DPI', '8000'], ['Kết nối', 'USB'], ['Màu sắc', 'Đen', '#111827']])],
            ],
            [
                'sku' => 'NGP-AC-003',
                'name' => 'Chuột Logitech MX Master 3S Wireless',
                'category' => 'Chuột',
                'brand' => 'Logitech',
                'weight' => 0.14,
                'specs' => ['sensor' => '8000 DPI Darkfield', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('Graphite / Wireless', 2390000, 22, [['DPI', '8000'], ['Kết nối', 'Bluetooth'], ['Màu sắc', 'Graphite', '#374151']])],
            ],
            [
                'sku' => 'NGP-AC-004',
                'name' => 'Chuột Razer DeathAdder V3 Wired',
                'category' => 'Chuột',
                'brand' => 'Razer',
                'weight' => 0.059,
                'specs' => ['sensor' => 'Focus Pro 30K', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('Đen / USB-C', 1590000, 20, [['DPI', '30000'], ['Kết nối', 'USB-C'], ['Màu sắc', 'Đen', '#111827']])],
            ],
            [
                'sku' => 'NGP-AC-005',
                'name' => 'Bàn phím Razer BlackWidow V4 X Green Switch',
                'category' => 'Bàn phím',
                'brand' => 'Razer',
                'weight' => 1.1,
                'specs' => ['layout' => 'Full-size', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('Green Switch / Đen', 3290000, 12, [['Switch', 'Green'], ['Kết nối', 'USB'], ['Màu sắc', 'Đen', '#111827']])],
            ],
            [
                'sku' => 'NGP-AC-006',
                'name' => 'Tai nghe Razer Barracuda X 2022 Wireless',
                'category' => 'Tai nghe',
                'brand' => 'Razer',
                'weight' => 0.25,
                'specs' => ['connectivity' => '2.4GHz, Bluetooth', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('Đen / Wireless', 2490000, 16, [['Kết nối', 'Wireless'], ['Driver', '40mm'], ['Màu sắc', 'Đen', '#111827']])],
            ],
            [
                'sku' => 'NGP-AC-007',
                'name' => 'Bàn phím cơ Akko 5075B Plus Dracula Castle',
                'category' => 'Bàn phím',
                'brand' => 'Akko',
                'weight' => 0.85,
                'specs' => ['layout' => '75%', 'warranty' => '12 tháng'],
                'variants' => [$this->simpleVariant('Akko CS Jelly Pink / Bluetooth', 2190000, 18, [['Switch', 'Jelly Pink'], ['Kết nối', 'Bluetooth'], ['Màu sắc', 'Dracula Castle']])],
            ],
            [
                'sku' => 'NGP-AC-008',
                'name' => 'Bàn phím cơ Keychron K2 V2 RGB Hot-swap',
                'category' => 'Bàn phím',
                'brand' => 'Keychron',
                'weight' => 0.79,
                'specs' => ['layout' => '75%', 'warranty' => '12 tháng'],
                'variants' => [$this->simpleVariant('Brown Switch / Aluminum', 2390000, 15, [['Switch', 'Brown'], ['Kết nối', 'Bluetooth'], ['Màu sắc', 'Xám', '#6b7280']])],
            ],
            [
                'sku' => 'NGP-AC-009',
                'name' => 'Bàn phím DareU EK75 Pro Triple Mode',
                'category' => 'Bàn phím',
                'brand' => 'DareU',
                'weight' => 0.82,
                'specs' => ['layout' => '75%', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('Dream Switch / Trắng xanh', 1290000, 24, [['Switch', 'Dream'], ['Kết nối', 'Triple Mode'], ['Màu sắc', 'Trắng xanh']])],
            ],
            [
                'sku' => 'NGP-AC-010',
                'name' => 'Tai nghe SteelSeries Arctis Nova 7 Wireless',
                'category' => 'Tai nghe',
                'brand' => 'SteelSeries',
                'weight' => 0.32,
                'specs' => ['battery' => '38 giờ', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('Đen / 2.4GHz', 4590000, 10, [['Kết nối', 'Wireless'], ['Driver', '40mm'], ['Màu sắc', 'Đen', '#111827']])],
            ],
            [
                'sku' => 'NGP-AC-011',
                'name' => 'Tai nghe HyperX Cloud III Wireless',
                'category' => 'Tai nghe',
                'brand' => 'HyperX',
                'weight' => 0.33,
                'specs' => ['battery' => '120 giờ', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('Đen đỏ / Wireless', 3990000, 13, [['Kết nối', 'Wireless'], ['Driver', '53mm'], ['Màu sắc', 'Đen đỏ']])],
            ],
            [
                'sku' => 'NGP-AC-012',
                'name' => 'SSD Samsung 990 Pro 1TB NVMe PCIe 4.0',
                'category' => 'Ổ cứng SSD',
                'brand' => 'Samsung',
                'weight' => 0.01,
                'specs' => ['read' => '7450MB/s', 'warranty' => '60 tháng'],
                'variants' => [$this->simpleVariant('1TB / M.2 2280', 2890000, 28, [['Dung lượng', '1TB'], ['Chuẩn', 'NVMe PCIe 4.0']])],
            ],
            [
                'sku' => 'NGP-AC-013',
                'name' => 'SSD Kingston NV2 1TB NVMe PCIe 4.0',
                'category' => 'Ổ cứng SSD',
                'brand' => 'Kingston',
                'weight' => 0.01,
                'specs' => ['read' => '3500MB/s', 'warranty' => '36 tháng'],
                'variants' => [$this->simpleVariant('1TB / M.2 2280', 1590000, 40, [['Dung lượng', '1TB'], ['Chuẩn', 'NVMe PCIe 4.0']])],
            ],
            [
                'sku' => 'NGP-AC-014',
                'name' => 'RAM Crucial 16GB DDR5 5600MHz Laptop',
                'category' => 'RAM',
                'brand' => 'Crucial',
                'weight' => 0.02,
                'specs' => ['type' => 'DDR5 SODIMM', 'warranty' => '36 tháng'],
                'variants' => [$this->simpleVariant('16GB / DDR5 5600MHz', 1290000, 32, [['RAM', '16GB'], ['Bus', '5600MHz'], ['Loại', 'DDR5 SODIMM']])],
            ],
            [
                'sku' => 'NGP-AC-015',
                'name' => 'RAM Corsair Vengeance 32GB DDR5 6000MHz',
                'category' => 'RAM',
                'brand' => 'Corsair',
                'weight' => 0.08,
                'specs' => ['type' => 'DDR5 Desktop', 'warranty' => '36 tháng'],
                'variants' => [$this->simpleVariant('32GB Kit 2x16GB / DDR5', 2990000, 18, [['RAM', '32GB'], ['Bus', '6000MHz'], ['Loại', 'DDR5 Desktop']])],
            ],
            [
                'sku' => 'NGP-AC-016',
                'name' => 'Màn hình LG UltraGear 27GP850-B 27 inch 2K 165Hz',
                'category' => 'Màn hình',
                'brand' => 'LG',
                'weight' => 6.3,
                'specs' => ['panel' => 'Nano IPS', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('27 inch / 2K 165Hz', 6990000, 11, [['Kích thước', '27 inch'], ['Độ phân giải', '2K'], ['Tần số quét', '165Hz']])],
            ],
            [
                'sku' => 'NGP-AC-017',
                'name' => 'Màn hình Dell P2723QE 27 inch 4K USB-C',
                'category' => 'Màn hình',
                'brand' => 'Dell',
                'weight' => 7.2,
                'specs' => ['panel' => 'IPS', 'warranty' => '36 tháng'],
                'variants' => [$this->simpleVariant('27 inch / 4K 60Hz', 9990000, 8, [['Kích thước', '27 inch'], ['Độ phân giải', '4K'], ['Kết nối', 'USB-C']])],
            ],
            [
                'sku' => 'NGP-AC-018',
                'name' => 'Hub Ugreen USB-C 7 in 1 HDMI 4K',
                'category' => 'Hub chuyển đổi',
                'brand' => 'Ugreen',
                'weight' => 0.12,
                'specs' => ['ports' => 'HDMI, USB-A, USB-C PD, SD, TF', 'warranty' => '18 tháng'],
                'variants' => [$this->simpleVariant('7 in 1 / Xám', 790000, 30, [['Cổng', '7 in 1'], ['Kết nối', 'USB-C'], ['Màu sắc', 'Xám', '#6b7280']])],
            ],
            [
                'sku' => 'NGP-AC-019',
                'name' => 'Webcam Logitech Brio 4K Ultra HD',
                'category' => 'Webcam',
                'brand' => 'Logitech',
                'weight' => 0.16,
                'specs' => ['resolution' => '4K Ultra HD', 'warranty' => '24 tháng'],
                'variants' => [$this->simpleVariant('4K / USB-C', 3990000, 9, [['Độ phân giải', '4K'], ['Kết nối', 'USB-C'], ['Màu sắc', 'Đen', '#111827']])],
            ],
            [
                'sku' => 'NGP-AC-020',
                'name' => 'Router TP-Link Archer AX55 Wi-Fi 6 AX3000',
                'category' => 'Router',
                'brand' => 'TP-Link',
                'weight' => 0.54,
                'specs' => ['wifi' => 'Wi-Fi 6 AX3000', 'warranty' => '36 tháng'],
                'variants' => [$this->simpleVariant('AX3000 / Đen', 1890000, 17, [['Wi-Fi', 'AX3000'], ['Băng tần', 'Dual-band'], ['Màu sắc', 'Đen', '#111827']])],
            ],
        ];
    }
}
