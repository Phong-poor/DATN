<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportDellAudioMousepadSeeder extends Seeder
{
    public function run(): void
    {
        $publicRoot = base_path('../frontend/public');
        foreach (['dell', 'tainghe', 'lotchuot'] as $folder) {
            if (!is_dir($publicRoot . DIRECTORY_SEPARATOR . $folder)) {
                throw new RuntimeException("Không tìm thấy thư mục frontend/public/{$folder}");
            }
        }

        $categories = ['laptop' => 3, 'headphone' => 12, 'mousepad' => 13];
        $brands = [
            'Dell' => $this->brand('Dell', [2, 3, 7]),
            'Baseus' => $this->brand('Baseus', [12]),
            'Havit' => $this->brand('Havit', [12]),
            'Asus' => $this->brand('Asus', [13]),
        ];

        $products = [
            $this->product('Laptop Dell 15 DC15250', ['dell/1'], 'laptop', 'Dell', [
                $this->variant('16GB - 512GB SSD - Đen', 17490000, 12, ['RAM' => '16GB', 'Ổ cứng' => '512GB SSD', 'Màu sắc' => ['Đen', '#111827']]),
                $this->variant('32GB - 1TB SSD - Đen', 20490000, 7, ['RAM' => '32GB', 'Ổ cứng' => '1TB SSD', 'Màu sắc' => ['Đen', '#111827']]),
            ]),
            $this->product('Laptop Dell Pro 15 Essential PV15250 VKVKD', ['dell/2'], 'laptop', 'Dell', [
                $this->variant('16GB - 512GB SSD - Đen', 18490000, 10, ['RAM' => '16GB', 'Ổ cứng' => '512GB SSD', 'Màu sắc' => ['Đen', '#111827']]),
                $this->variant('32GB - 1TB SSD - Đen', 21490000, 6, ['RAM' => '32GB', 'Ổ cứng' => '1TB SSD', 'Màu sắc' => ['Đen', '#111827']]),
            ]),
            $this->product('Laptop Dell 14 DC14250 DC4C5386W', ['dell/3'], 'laptop', 'Dell', [
                $this->variant('16GB - 512GB SSD - Bạc', 16990000, 11, ['RAM' => '16GB', 'Ổ cứng' => '512GB SSD', 'Màu sắc' => ['Bạc', '#C0C0C0']]),
                $this->variant('32GB - 1TB SSD - Bạc', 19990000, 6, ['RAM' => '32GB', 'Ổ cứng' => '1TB SSD', 'Màu sắc' => ['Bạc', '#C0C0C0']]),
            ]),
            $this->product('Laptop Dell XPS 13 9350', ['dell/4', 'dell/5'], 'laptop', 'Dell', [
                $this->variant('16GB - 512GB SSD - Đen', 32990000, 9, ['RAM' => '16GB', 'Ổ cứng' => '512GB SSD', 'Màu sắc' => ['Đen', '#111827']]),
                $this->variant('32GB - 1TB SSD - Graphite', 38990000, 5, ['RAM' => '32GB', 'Ổ cứng' => '1TB SSD', 'Màu sắc' => ['Graphite', '#374151']]),
            ]),
            $this->product('Laptop Dell Latitude 3450 i5-1335U', ['dell/6'], 'laptop', 'Dell', [
                $this->variant('16GB - 512GB SSD - Đen', 18990000, 12, ['CPU' => 'Intel Core i5-1335U', 'RAM' => '16GB', 'Ổ cứng' => '512GB SSD', 'Màu sắc' => ['Đen', '#111827']]),
                $this->variant('32GB - 1TB SSD - Đen', 21990000, 6, ['CPU' => 'Intel Core i5-1335U', 'RAM' => '32GB', 'Ổ cứng' => '1TB SSD', 'Màu sắc' => ['Đen', '#111827']]),
            ]),
            $this->product('Laptop Dell Inspiron 16 5640', ['dell/7'], 'laptop', 'Dell', [
                $this->variant('16GB - 512GB SSD - Bạc', 20990000, 10, ['RAM' => '16GB', 'Ổ cứng' => '512GB SSD', 'Màu sắc' => ['Bạc', '#C0C0C0']]),
                $this->variant('32GB - 1TB SSD - Bạc', 23990000, 6, ['RAM' => '32GB', 'Ổ cứng' => '1TB SSD', 'Màu sắc' => ['Bạc', '#C0C0C0']]),
            ]),
            $this->product('Laptop Dell Inspiron 14 5441', ['dell/8'], 'laptop', 'Dell', [
                $this->variant('16GB - 512GB SSD - Bạc', 19490000, 10, ['RAM' => '16GB', 'Ổ cứng' => '512GB SSD', 'Màu sắc' => ['Bạc', '#C0C0C0']]),
                $this->variant('32GB - 1TB SSD - Bạc', 22490000, 6, ['RAM' => '32GB', 'Ổ cứng' => '1TB SSD', 'Màu sắc' => ['Bạc', '#C0C0C0']]),
            ]),
            $this->product('Tai nghe Bluetooth Baseus Bowie H1i ANC', ['tainghe'], 'headphone', 'Baseus', [
                $this->variant('Trắng kem', 1290000, 18, ['Kết nối' => 'Bluetooth 5.3', 'Màu sắc' => ['Trắng kem', '#EDE4D4'], 'Chống ồn' => 'ANC']),
            ], range(1, 5), 'jpg'),
            $this->product('Tai nghe Bluetooth Havit H655BT ANC', ['tainghe'], 'headphone', 'Havit', [
                $this->variant('Xanh navy', 890000, 16, ['Kết nối' => 'Bluetooth', 'Màu sắc' => ['Xanh navy', '#1E3A5F'], 'Chống ồn' => 'ANC']),
                $this->variant('Đen', 890000, 14, ['Kết nối' => 'Bluetooth', 'Màu sắc' => ['Đen', '#111827'], 'Chống ồn' => 'ANC']),
            ], range(6, 16), 'jpg'),
            $this->product('Lót chuột ASUS ROG Strix Slice', ['lotchuot'], 'mousepad', 'Asus', [
                $this->variant('350 x 250 mm - ROG Neon', 390000, 24, ['Kích thước' => '350 x 250 mm', 'Bề mặt' => 'Vải tốc độ', 'Họa tiết' => 'ROG Neon']),
            ], range(1, 9), 'webp'),
            $this->product('Lót chuột ASUS ROG Hone Ace Aim Lab Edition', ['lotchuot'], 'mousepad', 'Asus', [
                $this->variant('508 x 420 mm - Đen', 990000, 18, ['Kích thước' => '508 x 420 mm', 'Bề mặt' => 'Vải lai phủ nano', 'Màu sắc' => ['Đen', '#111827']]),
            ], range(10, 15), 'webp'),
        ];

        DB::transaction(function () use ($products, $publicRoot, $categories, $brands): void {
            foreach ($products as $definition) {
                $images = $this->images($publicRoot, $definition);
                if (!$images) {
                    $this->command?->warn("Bỏ qua {$definition['name']}: không có ảnh.");
                    continue;
                }

                $productId = DB::table('sanpham')->where('tenSP', $definition['name'])->value('id_sanpham');
                $payload = [
                    'id_danhmuc' => $categories[$definition['category']],
                    'id_thuonghieu' => $brands[$definition['brand']],
                    'tenSP' => $definition['name'],
                    'trangthai' => '1',
                    'hinhanh' => $images[0],
                    'khoiluong' => null,
                    'thong_so_ky_thuat' => json_encode(['Nguồn dữ liệu' => 'Bộ ảnh sản phẩm trong frontend/public'], JSON_UNESCAPED_UNICODE),
                ];
                if ($productId) {
                    DB::table('sanpham')->where('id_sanpham', $productId)->update($payload);
                } else {
                    $productId = DB::table('sanpham')->insertGetId($payload + ['SKU' => 'NEW-' . strtoupper(substr(sha1($definition['name']), 0, 10))]);
                }

                DB::table('bienthe_hinhanh')->where('id_sanpham', $productId)->delete();
                foreach (array_slice($images, 1, 15) as $order => $path) {
                    DB::table('bienthe_hinhanh')->insert(['id_sanpham' => $productId, 'duongdan' => $path, 'thutu' => $order]);
                }

                foreach ($definition['variants'] as $variant) {
                    DB::table('bienthe')->updateOrInsert(
                        ['id_sanpham' => $productId, 'ten_bienthe' => $variant['name']],
                        ['gia' => $variant['price'], 'soluong' => $variant['stock'], 'thuoc_tinh_json' => json_encode($variant['attributes'], JSON_UNESCAPED_UNICODE)]
                    );
                }
            }
        });
    }

    private function brand(string $name, array $categories): int
    {
        $id = DB::table('thuonghieu')->where('ten_thuonghieu', $name)->value('id_thuonghieu');
        $payload = ['danh_muc_ids' => json_encode(array_map('strval', $categories)), 'updated_at' => now()];
        if ($id) {
            DB::table('thuonghieu')->where('id_thuonghieu', $id)->update($payload);
            return (int) $id;
        }
        return (int) DB::table('thuonghieu')->insertGetId($payload + ['ten_thuonghieu' => $name, 'created_at' => now()]);
    }

    private function product(string $name, array $folders, string $category, string $brand, array $variants, ?array $numbers = null, ?string $extension = null): array
    {
        return compact('name', 'folders', 'category', 'brand', 'variants', 'numbers', 'extension');
    }

    private function variant(string $name, int $price, int $stock, array $attributes): array
    {
        $normalized = [];
        foreach ($attributes as $attributeName => $value) {
            [$attributeValue, $hex] = is_array($value) ? $value : [$value, null];
            $normalized[] = ['id_thuoctinh' => md5($attributeName), 'ten_thuoctinh' => $attributeName, 'giatri' => $attributeValue, 'hex' => $hex];
        }
        return compact('name', 'price', 'stock') + ['attributes' => $normalized];
    }

    private function images(string $root, array $definition): array
    {
        $paths = [];
        if ($definition['numbers']) {
            foreach ($definition['numbers'] as $number) {
                $relative = $definition['folders'][0] . '/' . $number . '.' . $definition['extension'];
                if (is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative))) $paths[] = $relative;
            }
            return $paths;
        }
        foreach ($definition['folders'] as $folder) {
            $absolute = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
            $files = array_values(array_filter(scandir($absolute) ?: [], fn ($file) => is_file($absolute . DIRECTORY_SEPARATOR . $file) && preg_match('/\.(png|jpe?g|webp)$/i', $file)));
            natcasesort($files);
            $files = array_values($files);
            usort($files, static function (string $left, string $right): int {
                $score = static fn (string $file): int => preg_match('/(?:^|_)0?1(?:_|\.)/i', $file) ? 0 : 1;
                return ($score($left) <=> $score($right)) ?: strnatcasecmp($left, $right);
            });
            foreach ($files as $file) $paths[] = $folder . '/' . $file;
        }
        return $paths;
    }
}
