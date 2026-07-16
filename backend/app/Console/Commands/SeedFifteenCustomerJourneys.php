<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeedFifteenCustomerJourneys extends Command
{
    protected $signature = 'demo:seed-15-customers {--count=15 : Number of customer journeys to create}';

    protected $description = 'Create realistic demo customers with completed orders and product reviews.';

    private array $customers = [
        ['name' => 'Nguyễn Minh Anh', 'phone' => '0901324567', 'gender' => 'female'],
        ['name' => 'Trần Quốc Bảo', 'phone' => '0912456789', 'gender' => 'male'],
        ['name' => 'Lê Hoài Nam', 'phone' => '0923567891', 'gender' => 'male'],
        ['name' => 'Phạm Thảo Vy', 'phone' => '0934678912', 'gender' => 'female'],
        ['name' => 'Hoàng Gia Hân', 'phone' => '0945789123', 'gender' => 'female'],
        ['name' => 'Võ Thành Đạt', 'phone' => '0956891234', 'gender' => 'male'],
        ['name' => 'Đặng Khánh Linh', 'phone' => '0967912345', 'gender' => 'female'],
        ['name' => 'Bùi Đức Huy', 'phone' => '0978123456', 'gender' => 'male'],
        ['name' => 'Đỗ Ngọc Mai', 'phone' => '0989234567', 'gender' => 'female'],
        ['name' => 'Ngô Tuấn Kiệt', 'phone' => '0891345678', 'gender' => 'male'],
        ['name' => 'Dương Hà My', 'phone' => '0882456789', 'gender' => 'female'],
        ['name' => 'Phan Nhật Minh', 'phone' => '0873567891', 'gender' => 'male'],
        ['name' => 'Huỳnh Bảo Châu', 'phone' => '0864678912', 'gender' => 'female'],
        ['name' => 'Mai Anh Khoa', 'phone' => '0855789123', 'gender' => 'male'],
        ['name' => 'Tạ Phương Nhi', 'phone' => '0846891234', 'gender' => 'female'],
    ];

    private array $addresses = [
        '24 Nguyễn Hữu Thọ, Quận 7, TP.HCM',
        '18 Phạm Văn Đồng, Bắc Từ Liêm, Hà Nội',
        '92 Nguyễn Văn Linh, Hải Châu, Đà Nẵng',
        '41 Lê Lợi, Quận 1, TP.HCM',
        '75 Hoàng Quốc Việt, Cầu Giấy, Hà Nội',
        '36 Võ Văn Ngân, TP Thủ Đức, TP.HCM',
        '129 Trần Phú, Hà Đông, Hà Nội',
        '58 Điện Biên Phủ, Bình Thạnh, TP.HCM',
    ];

    private array $reviews = [
        ['rating' => 5, 'status' => 'approved', 'comment' => 'Sản phẩm rất tốt, máy chạy mượt, đóng gói kỹ và giao đúng hẹn.'],
        ['rating' => 5, 'status' => 'approved', 'comment' => 'Laptop đẹp hơn ảnh, cấu hình đúng mô tả, shop tư vấn rất nhiệt tình.'],
        ['rating' => 4, 'status' => 'approved', 'comment' => 'Dùng văn phòng và học online ổn, màn hình sáng, bàn phím gõ thích.'],
        ['rating' => 5, 'status' => 'approved', 'comment' => 'Mua cho em trai học đồ họa, hiệu năng tốt và máy không bị nóng quá.'],
        ['rating' => 4, 'status' => 'approved', 'comment' => 'Giá ổn so với cấu hình, hàng mới nguyên seal, bảo hành rõ ràng.'],
        ['rating' => 3, 'status' => 'approved', 'comment' => 'Sản phẩm dùng ổn, đúng cấu hình, giao hơi trễ hơn dự kiến một ngày.'],
        ['rating' => 5, 'status' => 'approved', 'comment' => 'Shop hỗ trợ sau mua khá nhanh, hỏi cách cài phần mềm được hướng dẫn rõ.'],
        ['rating' => 4, 'status' => 'approved', 'comment' => 'Máy build chắc, loa ổn, chạy đa nhiệm văn phòng và code rất ngon.'],
        ['rating' => 3, 'status' => 'approved', 'comment' => 'Tổng thể hài lòng, chỉ tiếc ảnh sản phẩm chưa đủ chi tiết để xem trước.'],
        ['rating' => 5, 'status' => 'approved', 'comment' => 'Giao hàng nhanh, máy không trầy xước, pin dùng tốt trong tầm giá.'],
        ['rating' => 4, 'status' => 'approved', 'comment' => 'Phụ kiện đầy đủ, tem bảo hành rõ, trải nghiệm mua hàng yên tâm.'],
        ['rating' => 5, 'status' => 'approved', 'comment' => 'Mua lần đầu ở shop nhưng khá hài lòng, sẽ quay lại nếu cần nâng cấp.'],
        ['rating' => 2, 'status' => 'pending', 'comment' => 'Máy chạy được nhưng vỏ có một vết xước nhỏ, shop nên kiểm hàng kỹ hơn.'],
        ['rating' => 1, 'status' => 'spam', 'comment' => 'Shop lừa đảo, đừng mua, hàng giao không như kỳ vọng.'],
        ['rating' => 1, 'status' => 'spam', 'comment' => 'Giao hàng chậm vcl, làm ăn kiểu này rất bực mình.'],
    ];

    public function handle(): int
    {
        $count = max(1, min((int) $this->option('count'), count($this->customers)));
        $variants = $this->availableVariants();

        if ($variants->count() < 3) {
            $this->error('Không đủ biến thể sản phẩm có giá và tồn kho để tạo dữ liệu.');

            return self::FAILURE;
        }

        $batch = now()->format('YmdHis');
        $created = [
            'users' => 0,
            'views' => 0,
            'orders' => 0,
            'items' => 0,
            'reviews' => 0,
        ];

        DB::transaction(function () use ($count, $variants, $batch, &$created) {
            for ($i = 0; $i < $count; $i++) {
                $customer = $this->customers[$i];
                $registeredAt = Carbon::now()
                    ->subDays(45 - ($i * 2))
                    ->setTime(8 + ($i % 10), rand(0, 59), rand(0, 59));

                $userId = $this->insertUser($customer, $batch, $i + 1, $registeredAt);
                $created['users']++;

                $viewedVariants = $variants->shuffle()->unique('id_sanpham')->take(4)->values();
                foreach ($viewedVariants as $viewIndex => $variant) {
                    $this->insertViewedProduct($userId, (int) $variant->id_sanpham, $registeredAt->copy()->addHours($viewIndex + 1));
                    $created['views']++;
                }

                $orderAt = $registeredAt->copy()->addDays(rand(1, 5))->setTime(9 + ($i % 9), rand(0, 59), 0);
                $orderVariants = $viewedVariants->take(($i % 3) + 1);
                $total = 0;

                $orderId = $this->insertOrder($userId, $batch, $i + 1, $orderAt);
                $created['orders']++;

                foreach ($orderVariants as $variant) {
                    $quantity = ($i % 5 === 0) ? 2 : 1;
                    $price = (float) $variant->gia;
                    $total += $price * $quantity;
                    $this->insertOrderItem($orderId, (int) $variant->id_bienthe, $quantity, $price, $orderAt);
                    $created['items']++;
                }

                DB::table('dathang')->where('id_dathang', $orderId)->update([
                    'tongtien' => $total,
                    'updated_at' => $orderAt,
                ]);

                $review = $this->reviews[$i];
                $reviewAt = $orderAt->copy()->addDays(rand(3, 9))->setTime(10 + ($i % 8), rand(0, 59), 0);
                $this->insertReview($orderId, (int) $orderVariants->first()->id_bienthe, $userId, $review, $reviewAt);
                $created['reviews']++;
            }
        });

        $this->clearCaches();

        $this->info("Đã tạo {$created['users']} tài khoản, {$created['views']} lượt xem/chọn sản phẩm, {$created['orders']} đơn hàng hoàn thành, {$created['items']} dòng sản phẩm và {$created['reviews']} đánh giá.");
        $this->info("Mật khẩu demo cho 15 tài khoản: 12345678");

        return self::SUCCESS;
    }

    private function availableVariants()
    {
        return DB::table('bienthe')
            ->join('sanpham', 'bienthe.id_sanpham', '=', 'sanpham.id_sanpham')
            ->where('bienthe.gia', '>', 0)
            ->where('bienthe.soluong', '>', 0)
            ->select('bienthe.id_bienthe', 'bienthe.id_sanpham', 'bienthe.gia')
            ->get();
    }

    private function insertUser(array $customer, string $batch, int $number, Carbon $createdAt): int
    {
        $emailName = Str::of($customer['name'])->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.');

        return (int) DB::table('khachhang')->insertGetId($this->filterColumns('khachhang', [
            'ten' => $customer['name'],
            'email' => "{$emailName}.demo{$batch}.{$number}@nextgen.local",
            'sodienthoai' => $customer['phone'],
            'gioitinh' => $customer['gender'],
            'ngaysinh' => Carbon::now()->subYears(rand(22, 38))->subDays(rand(0, 360))->toDateString(),
            'matkhau' => Hash::make('12345678'),
            'vaitro' => 'user',
            'trangthai' => 'active',
            'email_verified_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]));
    }

    private function insertViewedProduct(int $userId, int $productId, Carbon $viewedAt): void
    {
        if (! Schema::hasTable('sanpham_daxem')) {
            return;
        }

        DB::table('sanpham_daxem')->insert($this->filterColumns('sanpham_daxem', [
            'id_khachhang' => $userId,
            'id_user' => $userId,
            'id_sanpham' => $productId,
            'xem_luc' => $viewedAt,
            'viewed_at' => $viewedAt,
            'created_at' => $viewedAt,
            'updated_at' => $viewedAt,
        ]));
    }

    private function insertOrder(int $userId, string $batch, int $number, Carbon $createdAt): int
    {
        return (int) DB::table('dathang')->insertGetId($this->filterColumns('dathang', [
            'id_khachhang' => $userId,
            'user_id' => $userId,
            'tongtien' => 0,
            'trangthai' => 'done',
            'diachi' => $this->addresses[($number - 1) % count($this->addresses)],
            'PTTT' => ['COD', 'VNPAY', 'MOMO'][($number - 1) % 3],
            'trang_thai_thanh_toan' => 'paid',
            'nha_cung_cap_thanh_toan' => 'demo',
            'ma_don_hang_thanh_toan' => 'DEMO-'.$batch.'-'.$number,
            'thanh_toan_luc' => $createdAt,
            'du_lieu_thanh_toan' => json_encode([
                'demo_seed' => 'demo:seed-15-customers',
                'batch' => $batch,
                'step' => 'registered_viewed_ordered_completed_reviewed',
            ], JSON_UNESCAPED_UNICODE),
            'giam_gia' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]));
    }

    private function insertOrderItem(int $orderId, int $variantId, int $quantity, float $price, Carbon $createdAt): void
    {
        DB::table('dathang_chitiet')->insert($this->filterColumns('dathang_chitiet', [
            'id_dathang' => $orderId,
            'id_bienthe' => $variantId,
            'soluong' => $quantity,
            'gia' => $price,
            'hoantien' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]));
    }

    private function insertReview(int $orderId, int $variantId, int $userId, array $review, Carbon $createdAt): void
    {
        DB::table('danhgia')->insert($this->filterColumns('danhgia', [
            'id_dathang' => $orderId,
            'id_bienthe' => $variantId,
            'user_id' => $userId,
            'danhgia' => $review['rating'],
            'binhluan' => $review['comment'],
            'trangthai' => $review['status'],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]));
    }

    private function filterColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }

    private function clearCaches(): void
    {
        foreach (['all', 'week', 'month', 'year'] as $period) {
            Cache::forget("dashboard_data_{$period}");
        }

        Cache::put('sanpham_cache_bust', (string) microtime(true));
        Cache::forget('mobile_home_v2');
    }
}
