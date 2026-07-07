<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MacbookCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = $this->ensureCategory('Macbook');
        $brandId = $this->ensureBrand('Apple');

        DB::transaction(function () use ($categoryId, $brandId) {
            foreach ($this->products() as $product) {
                $productId = $this->upsertProduct($product, $categoryId, $brandId);

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

    private function ensureCategory(string $name): int
    {
        $category = DB::table('danhmuc')->where('ten_danhmuc', $name)->first();

        if ($category) {
            return (int) $category->id_danhmuc;
        }

        return (int) DB::table('danhmuc')->insertGetId([
            'ten_danhmuc' => $name,
            'trangthai' => 1,
            'id_danhmuc_cha' => null,
        ]);
    }

    private function ensureBrand(string $name): int
    {
        $brand = DB::table('thuonghieu')->where('ten_thuonghieu', $name)->first();

        if ($brand) {
            return (int) $brand->id_thuonghieu;
        }

        return (int) DB::table('thuonghieu')->insertGetId([
            'ten_thuonghieu' => $name,
            'danh_muc_ids' => null,
            'logo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function upsertProduct(array $product, int $categoryId, int $brandId): int
    {
        $payload = [
            'id_danhmuc' => $categoryId,
            'id_thuonghieu' => $brandId,
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

    private function variant(string $name, int $price, int $stock, string $chip, string $gpu, string $ram, string $ssd, string $color): array
    {
        return [
            'name' => $name,
            'price' => $price,
            'stock' => $stock,
            'attributes' => [
                $this->attr('RAM', $ram),
                $this->attr('SSD', $ssd),
                $this->attr('CPU', $chip),
                $this->attr('GPU', $gpu),
                $this->attr('Màu sắc', $color),
            ],
        ];
    }

    private function attr(string $name, string $value): array
    {
        return [
            'ten_thuoctinh' => $name,
            'giatri' => $value,
        ];
    }

    private function products(): array
    {
        return [
            [
                'sku' => 'NGP-MB-001',
                'name' => 'MacBook Air 13 inch M1 2020 8CPU 7GPU',
                'weight' => 1.29,
                'specs' => ['screen' => '13.3 inch Retina', 'battery' => '49.9Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('8GB RAM / 256GB SSD / Space Gray', 18990000, 10, 'Apple M1', 'Apple 7-core GPU', '8GB', '256GB', 'Space Gray'),
                    $this->variant('8GB RAM / 512GB SSD / Silver', 22990000, 7, 'Apple M1', 'Apple 8-core GPU', '8GB', '512GB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-002',
                'name' => 'MacBook Air 13 inch M2 2022 8CPU 10GPU',
                'weight' => 1.24,
                'specs' => ['screen' => '13.6 inch Liquid Retina', 'battery' => '52.6Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('8GB RAM / 512GB SSD / Midnight', 27990000, 8, 'Apple M2', 'Apple 10-core GPU', '8GB', '512GB', 'Midnight'),
                    $this->variant('16GB RAM / 512GB SSD / Starlight', 32990000, 5, 'Apple M2', 'Apple 10-core GPU', '16GB', '512GB', 'Starlight'),
                ],
            ],
            [
                'sku' => 'NGP-MB-003',
                'name' => 'MacBook Air 13 inch M3 2024 8CPU 8GPU',
                'weight' => 1.24,
                'specs' => ['screen' => '13.6 inch Liquid Retina', 'battery' => '52.6Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('8GB RAM / 256GB SSD / Midnight', 26990000, 12, 'Apple M3', 'Apple 8-core GPU', '8GB', '256GB', 'Midnight'),
                    $this->variant('16GB RAM / 512GB SSD / Silver', 34990000, 6, 'Apple M3', 'Apple 10-core GPU', '16GB', '512GB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-004',
                'name' => 'MacBook Air 13 inch M3 2024 8CPU 10GPU',
                'weight' => 1.24,
                'specs' => ['screen' => '13.6 inch Liquid Retina', 'battery' => '52.6Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('8GB RAM / 512GB SSD / Space Gray', 31990000, 9, 'Apple M3', 'Apple 10-core GPU', '8GB', '512GB', 'Space Gray'),
                    $this->variant('24GB RAM / 1TB SSD / Starlight', 45990000, 3, 'Apple M3', 'Apple 10-core GPU', '24GB', '1TB', 'Starlight'),
                ],
            ],
            [
                'sku' => 'NGP-MB-005',
                'name' => 'MacBook Air 15 inch M2 2023 8CPU 10GPU',
                'weight' => 1.51,
                'specs' => ['screen' => '15.3 inch Liquid Retina', 'battery' => '66.5Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('8GB RAM / 256GB SSD / Midnight', 29990000, 8, 'Apple M2', 'Apple 10-core GPU', '8GB', '256GB', 'Midnight'),
                    $this->variant('16GB RAM / 512GB SSD / Silver', 37990000, 5, 'Apple M2', 'Apple 10-core GPU', '16GB', '512GB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-006',
                'name' => 'MacBook Air 15 inch M3 2024 8CPU 10GPU',
                'weight' => 1.51,
                'specs' => ['screen' => '15.3 inch Liquid Retina', 'battery' => '66.5Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('8GB RAM / 512GB SSD / Midnight', 36990000, 8, 'Apple M3', 'Apple 10-core GPU', '8GB', '512GB', 'Midnight'),
                    $this->variant('16GB RAM / 1TB SSD / Space Gray', 47990000, 4, 'Apple M3', 'Apple 10-core GPU', '16GB', '1TB', 'Space Gray'),
                ],
            ],
            [
                'sku' => 'NGP-MB-007',
                'name' => 'MacBook Pro 13 inch M2 2022 Touch Bar',
                'weight' => 1.4,
                'specs' => ['screen' => '13.3 inch Retina', 'battery' => '58.2Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('8GB RAM / 256GB SSD / Space Gray', 29990000, 6, 'Apple M2', 'Apple 10-core GPU', '8GB', '256GB', 'Space Gray'),
                    $this->variant('16GB RAM / 512GB SSD / Silver', 38990000, 4, 'Apple M2', 'Apple 10-core GPU', '16GB', '512GB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-008',
                'name' => 'MacBook Pro 14 inch M3 2023 8CPU 10GPU',
                'weight' => 1.55,
                'specs' => ['screen' => '14.2 inch Liquid Retina XDR', 'battery' => '70Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('8GB RAM / 512GB SSD / Space Gray', 39990000, 7, 'Apple M3', 'Apple 10-core GPU', '8GB', '512GB', 'Space Gray'),
                    $this->variant('16GB RAM / 1TB SSD / Silver', 52990000, 3, 'Apple M3', 'Apple 10-core GPU', '16GB', '1TB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-009',
                'name' => 'MacBook Pro 14 inch M3 Pro 11CPU 14GPU',
                'weight' => 1.61,
                'specs' => ['screen' => '14.2 inch Liquid Retina XDR', 'battery' => '72.4Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('18GB RAM / 512GB SSD / Space Black', 52990000, 6, 'Apple M3 Pro', 'Apple 14-core GPU', '18GB', '512GB', 'Space Black'),
                    $this->variant('18GB RAM / 1TB SSD / Silver', 60990000, 4, 'Apple M3 Pro', 'Apple 14-core GPU', '18GB', '1TB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-010',
                'name' => 'MacBook Pro 14 inch M3 Pro 12CPU 18GPU',
                'weight' => 1.61,
                'specs' => ['screen' => '14.2 inch Liquid Retina XDR', 'battery' => '72.4Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('18GB RAM / 1TB SSD / Space Black', 66990000, 4, 'Apple M3 Pro', 'Apple 18-core GPU', '18GB', '1TB', 'Space Black'),
                    $this->variant('36GB RAM / 1TB SSD / Silver', 82990000, 2, 'Apple M3 Pro', 'Apple 18-core GPU', '36GB', '1TB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-011',
                'name' => 'MacBook Pro 16 inch M3 Pro 12CPU 18GPU',
                'weight' => 2.14,
                'specs' => ['screen' => '16.2 inch Liquid Retina XDR', 'battery' => '100Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('18GB RAM / 512GB SSD / Space Black', 64990000, 5, 'Apple M3 Pro', 'Apple 18-core GPU', '18GB', '512GB', 'Space Black'),
                    $this->variant('36GB RAM / 1TB SSD / Silver', 89990000, 2, 'Apple M3 Pro', 'Apple 18-core GPU', '36GB', '1TB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-012',
                'name' => 'MacBook Pro 14 inch M3 Max 14CPU 30GPU',
                'weight' => 1.62,
                'specs' => ['screen' => '14.2 inch Liquid Retina XDR', 'battery' => '72.4Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('36GB RAM / 1TB SSD / Space Black', 89990000, 3, 'Apple M3 Max', 'Apple 30-core GPU', '36GB', '1TB', 'Space Black'),
                    $this->variant('64GB RAM / 2TB SSD / Silver', 119990000, 1, 'Apple M3 Max', 'Apple 30-core GPU', '64GB', '2TB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-013',
                'name' => 'MacBook Pro 16 inch M3 Max 14CPU 30GPU',
                'weight' => 2.16,
                'specs' => ['screen' => '16.2 inch Liquid Retina XDR', 'battery' => '100Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('36GB RAM / 1TB SSD / Space Black', 96990000, 3, 'Apple M3 Max', 'Apple 30-core GPU', '36GB', '1TB', 'Space Black'),
                    $this->variant('64GB RAM / 2TB SSD / Silver', 129990000, 1, 'Apple M3 Max', 'Apple 30-core GPU', '64GB', '2TB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-014',
                'name' => 'MacBook Pro 16 inch M3 Max 16CPU 40GPU',
                'weight' => 2.16,
                'specs' => ['screen' => '16.2 inch Liquid Retina XDR', 'battery' => '100Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('48GB RAM / 1TB SSD / Space Black', 109990000, 2, 'Apple M3 Max', 'Apple 40-core GPU', '48GB', '1TB', 'Space Black'),
                    $this->variant('128GB RAM / 4TB SSD / Silver', 179990000, 1, 'Apple M3 Max', 'Apple 40-core GPU', '128GB', '4TB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-015',
                'name' => 'MacBook Air 13 inch M4 2025 10CPU 8GPU',
                'weight' => 1.24,
                'specs' => ['screen' => '13.6 inch Liquid Retina', 'battery' => '53.8Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('16GB RAM / 256GB SSD / Sky Blue', 27990000, 10, 'Apple M4', 'Apple 8-core GPU', '16GB', '256GB', 'Sky Blue'),
                    $this->variant('16GB RAM / 512GB SSD / Midnight', 32990000, 7, 'Apple M4', 'Apple 10-core GPU', '16GB', '512GB', 'Midnight'),
                ],
            ],
            [
                'sku' => 'NGP-MB-016',
                'name' => 'MacBook Air 15 inch M4 2025 10CPU 10GPU',
                'weight' => 1.51,
                'specs' => ['screen' => '15.3 inch Liquid Retina', 'battery' => '66.5Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('16GB RAM / 256GB SSD / Sky Blue', 32990000, 8, 'Apple M4', 'Apple 10-core GPU', '16GB', '256GB', 'Sky Blue'),
                    $this->variant('24GB RAM / 1TB SSD / Starlight', 49990000, 3, 'Apple M4', 'Apple 10-core GPU', '24GB', '1TB', 'Starlight'),
                ],
            ],
            [
                'sku' => 'NGP-MB-017',
                'name' => 'MacBook Pro 14 inch M4 2024 10CPU 10GPU',
                'weight' => 1.55,
                'specs' => ['screen' => '14.2 inch Liquid Retina XDR', 'battery' => '72.4Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('16GB RAM / 512GB SSD / Space Black', 44990000, 6, 'Apple M4', 'Apple 10-core GPU', '16GB', '512GB', 'Space Black'),
                    $this->variant('24GB RAM / 1TB SSD / Silver', 60990000, 3, 'Apple M4', 'Apple 10-core GPU', '24GB', '1TB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-018',
                'name' => 'MacBook Pro 14 inch M4 Pro 12CPU 16GPU',
                'weight' => 1.6,
                'specs' => ['screen' => '14.2 inch Liquid Retina XDR', 'battery' => '72.4Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('24GB RAM / 512GB SSD / Space Black', 57990000, 5, 'Apple M4 Pro', 'Apple 16-core GPU', '24GB', '512GB', 'Space Black'),
                    $this->variant('48GB RAM / 1TB SSD / Silver', 79990000, 2, 'Apple M4 Pro', 'Apple 16-core GPU', '48GB', '1TB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-019',
                'name' => 'MacBook Pro 16 inch M4 Pro 14CPU 20GPU',
                'weight' => 2.14,
                'specs' => ['screen' => '16.2 inch Liquid Retina XDR', 'battery' => '100Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('24GB RAM / 512GB SSD / Space Black', 72990000, 4, 'Apple M4 Pro', 'Apple 20-core GPU', '24GB', '512GB', 'Space Black'),
                    $this->variant('48GB RAM / 1TB SSD / Silver', 94990000, 2, 'Apple M4 Pro', 'Apple 20-core GPU', '48GB', '1TB', 'Silver'),
                ],
            ],
            [
                'sku' => 'NGP-MB-020',
                'name' => 'MacBook Pro 16 inch M4 Max 16CPU 40GPU',
                'weight' => 2.15,
                'specs' => ['screen' => '16.2 inch Liquid Retina XDR', 'battery' => '100Wh', 'warranty' => '12 tháng'],
                'variants' => [
                    $this->variant('48GB RAM / 1TB SSD / Space Black', 109990000, 2, 'Apple M4 Max', 'Apple 40-core GPU', '48GB', '1TB', 'Space Black'),
                    $this->variant('128GB RAM / 4TB SSD / Silver', 189990000, 1, 'Apple M4 Max', 'Apple 40-core GPU', '128GB', '4TB', 'Silver'),
                ],
            ],
        ];
    }
}
