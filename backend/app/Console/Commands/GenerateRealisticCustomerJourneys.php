<?php

namespace App\Console\Commands;

use App\Models\DanhGia;
use App\Models\DatHang;
use App\Models\DatHangChiTiet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GenerateRealisticCustomerJourneys extends Command
{
    protected $signature = 'demo:customer-journeys
        {--users=100 : So khach hang can co}
        {--force : Tao them mot lo moi thay vi bu du lieu con thieu}';

    protected $description = 'Tao du lieu khach hang Viet Nam, luot xem, don hang hoan tat va danh gia san pham.';

    private array $names = [
        'Nguyễn Thị Hoài', 'Trần Minh Quân', 'Lê Thanh Huyền', 'Phạm Quốc Anh', 'Hoàng Gia Bảo',
        'Võ Ngọc Mai', 'Đặng Tuấn Kiệt', 'Bùi Khánh Linh', 'Đỗ Minh Khang', 'Ngô Hải Yến',
        'Dương Đức Thành', 'Phan Thảo Vy', 'Huỳnh Nhật Nam', 'Mai Phương Anh', 'Tạ Hữu Phước',
        'Trương Bảo Ngọc', 'Lý Minh Triết', 'Cao Thu Hà', 'Hồ Anh Duy', 'Nguyễn Bích Ngân',
        'Trần Hoàng Long', 'Lê Mỹ Duyên', 'Phạm Minh Đức', 'Hoàng Kim Chi', 'Võ Thành Đạt',
        'Đặng Khánh An', 'Bùi Nhật Hạ', 'Đỗ Quang Huy', 'Ngô Thanh Tâm', 'Dương Minh Châu',
        'Phan Gia Hân', 'Huỳnh Thiên Phúc', 'Mai Ngọc Trâm', 'Tạ Minh Tùng', 'Trương Hải Đăng',
        'Lý Thanh Bình', 'Cao Phương Nhi', 'Hồ Minh Nhật', 'Nguyễn Hà My', 'Trần Đức Hiếu',
        'Lê Quốc Việt', 'Phạm Thị Ngọc Ánh', 'Hoàng Minh Tuấn', 'Võ Thùy Trang', 'Đặng Gia Khánh',
        'Bùi Hoài Nam', 'Đỗ Yến Nhi', 'Ngô Đức Dũng', 'Dương Lan Anh', 'Phan Hữu Lộc',
        'Huỳnh Minh Thư', 'Mai Anh Khoa', 'Tạ Bảo Trân', 'Trương Quốc Hưng', 'Lý Ngọc Diệp',
        'Cao Tuấn Anh', 'Hồ Thị Thanh Trúc', 'Nguyễn Minh Phương', 'Trần Gia Huy', 'Lê Nhật Minh',
        'Phạm Bảo Châu', 'Hoàng Anh Tú', 'Võ Minh Tâm', 'Đặng Thị Thu Trang', 'Bùi Quang Vinh',
        'Đỗ Minh Anh', 'Ngô Khánh Vy', 'Dương Quốc Bảo', 'Phan Nhật Linh', 'Huỳnh Đức Mạnh',
        'Mai Thanh Sơn', 'Tạ Phương Uyên', 'Trương Minh Hiếu', 'Lý Hải Nam', 'Cao Bảo Anh',
        'Hồ Ngọc Hân', 'Nguyễn Đức Phát', 'Trần Thị Mỹ Linh', 'Lê Hoàng Phúc', 'Phạm Gia Bảo',
        'Hoàng Thảo Nguyên', 'Võ Nhật Quang', 'Đặng Minh Khôi', 'Bùi Thanh Hằng', 'Đỗ Hải Anh',
        'Ngô Phương Thảo', 'Dương Tuấn Minh', 'Phan Ngọc Khuê', 'Huỳnh Khánh Duy', 'Mai Đức Anh',
        'Tạ Thanh Mai', 'Trương Bích Phương', 'Lý Quốc Cường', 'Cao Minh Trí', 'Hồ Gia Linh',
        'Nguyễn Quỳnh Như', 'Trần Hải Đăng', 'Lê Thị Thuỳ Dương', 'Phạm Minh Khoa', 'Hoàng Nhật Lệ',
    ];

    private array $positiveReviews = [
        'Máy chạy rất mượt, đóng gói kỹ, giao đúng hẹn. Mình dùng làm việc cả ngày vẫn ổn.',
        'Sản phẩm đẹp hơn ảnh, bàn phím gõ thích, màn hình sáng và màu khá chuẩn.',
        'Shop tư vấn nhanh, cấu hình đúng như mô tả. Mua về cài phần mềm là dùng được ngay.',
        'Giá ổn so với cấu hình, máy mới nguyên seal, bảo hành rõ ràng.',
        'Mình mua cho em trai học đồ họa, hiệu năng tốt và không bị nóng quá.',
        'Phụ kiện đầy đủ, tem bảo hành rõ. Trải nghiệm mua hàng rất yên tâm.',
        'Giao hàng nhanh, máy không trầy xước, pin dùng tốt trong tầm giá.',
        'Laptop build chắc, loa nghe ổn, chạy đa nhiệm văn phòng và code rất ngon.',
        'Dịch vụ hỗ trợ sau mua khá nhiệt tình, hỏi gì cũng được phản hồi.',
        'Mua lần đầu ở shop nhưng khá hài lòng, sẽ quay lại nếu cần nâng cấp.',
    ];

    private array $neutralReviews = [
        'Máy ổn, đúng cấu hình, nhưng giao hơi trễ hơn dự kiến một ngày.',
        'Sản phẩm dùng được, đóng gói chắc. Giá có thể tốt hơn một chút.',
        'Hiệu năng tốt nhưng quạt hơi rõ tiếng khi chạy nặng.',
        'Tổng thể hài lòng, chỉ tiếc ảnh sản phẩm chưa đầy đủ để xem trước.',
        'Máy đẹp, nhưng phần quà tặng giao sau nên hơi bất tiện.',
        'Dùng văn phòng thì ổn, chơi game lâu máy hơi nóng.',
    ];

    private array $negativeReviews = [
        'Giao hàng chậm, mình phải gọi hỏi nhiều lần mới có cập nhật.',
        'Máy chạy được nhưng vỏ có một vết xước nhỏ, shop cần kiểm hàng kỹ hơn.',
        'Đóng gói chưa chắc chắn lắm, nhận hàng hơi lo dù máy vẫn hoạt động.',
        'Pin tụt nhanh hơn kỳ vọng, cần shop hỗ trợ kiểm tra thêm.',
        'Tư vấn ban đầu hơi sơ sài, mình phải tự hỏi lại nhiều thông tin.',
        'Màn hình có hở sáng nhẹ, dùng được nhưng chưa thật sự hài lòng.',
    ];

    private array $spamReviews = [
        'Giao hàng chậm vcl, hẹn 2 ngày mà kéo gần tuần, bực thật.',
        'Shop làm ăn như cứt, gọi hỗ trợ mãi không ai nghe máy.',
        'Máy lỗi màn hình, trải nghiệm chán đéo chịu được.',
        'Đóng gói ẩu quá, nhận hàng mà tức muốn chửi.',
        'Tư vấn vòng vo, mất thời gian vl, lần sau né.',
        'Giao sai hẹn, làm ăn kiểu này bực mình thật sự.',
    ];

    public function handle(): int
    {
        $targetUsers = max(1, (int) $this->option('users'));
        $variants = $this->variants();

        if ($variants->count() < 5) {
            $this->error('Khong du bien the san pham co gia de tao hanh trinh khach hang.');

            return self::FAILURE;
        }

        $existing = $this->option('force')
            ? collect()
            : User::query()
                ->where(function ($query) {
                    $query->where('email', 'like', 'khachhang%@nextgen.local')
                        ->orWhereIn('id', $this->demoCustomerIdsQuery());
                })
                ->where('role', 'user')
                ->where('status', 'active')
                ->get();

        $missing = $this->option('force') ? $targetUsers : max(0, $targetUsers - $existing->count());

        if ($missing === 0) {
            $this->info("Da co san {$existing->count()} khach hang hanh trinh, khong can tao them.");

            return self::SUCCESS;
        }

        $createdUsers = 0;
        $createdViews = 0;
        $createdOrders = 0;
        $createdReviews = 0;

        DB::transaction(function () use ($missing, $targetUsers, $variants, &$createdUsers, &$createdViews, &$createdOrders, &$createdReviews) {
            $startIndex = $this->option('force')
                ? (int) now()->format('His')
                : $this->nextUserIndex();

            for ($i = 0; $i < $missing; $i++) {
                $number = $startIndex + $i;
                $name = $this->names[($number - 1) % count($this->names)];
                $joinedAt = Carbon::now()
                    ->subDays(rand(7, 90))
                    ->setTime(rand(8, 21), rand(0, 59), rand(0, 59));

                $user = User::create([
                    'name' => $name,
                    'email' => $this->emailFor($name, $number),
                    'phone' => '09'.str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                    'date_of_birth' => Carbon::now()->subYears(rand(20, 42))->subDays(rand(0, 364))->toDateString(),
                    'gender' => $this->guessGender($name),
                    'password' => Hash::make('12345678'),
                    'role' => 'user',
                    'status' => 'active',
                    'email_verified_at' => $joinedAt,
                    'created_at' => $joinedAt,
                    'updated_at' => $joinedAt,
                ]);
                $createdUsers++;

                $viewedProductIds = $variants
                    ->shuffle()
                    ->pluck('id_sanpham')
                    ->unique()
                    ->take(rand(4, 8))
                    ->values();

                foreach ($viewedProductIds as $offset => $productId) {
                    $this->insertViewedProduct($user->id, (int) $productId, $joinedAt->copy()->addHours($offset + rand(1, 12)));
                    $createdViews++;
                }

                $ordersForUser = $number % 4 === 0 ? 2 : 1;

                for ($orderIndex = 0; $orderIndex < $ordersForUser; $orderIndex++) {
                    $orderTime = $joinedAt->copy()->addDays(rand(1, 12) + ($orderIndex * 7))->setTime(rand(9, 22), rand(0, 59));
                    $lineVariants = $variants->shuffle()->take(rand(1, 3));
                    $total = 0;

                    $order = DatHang::create([
                        'id_khachhang' => $user->id,
                        'tongtien' => 0,
                        'trangthai' => 'done',
                        'diachi' => $this->address(),
                        'PTTT' => collect(['COD', 'VNPAY', 'MOMO'])->random(),
                        'trang_thai_thanh_toan' => 'paid',
                        'nha_cung_cap_thanh_toan' => 'demo',
                        'ma_don_hang_thanh_toan' => 'CJ-'.$user->id.'-'.Str::upper(Str::random(8)),
                        'thanh_toan_luc' => $orderTime,
                        'du_lieu_thanh_toan' => [
                            'customer_journey_demo' => true,
                            'generated_by' => 'demo:customer-journeys',
                            'user_name' => $name,
                        ],
                        'giam_gia' => 0,
                        'created_at' => $orderTime,
                        'updated_at' => $orderTime,
                    ]);

                    foreach ($lineVariants as $variant) {
                        $quantity = rand(1, 2);
                        $price = (float) $variant->gia;
                        $total += $price * $quantity;

                        DatHangChiTiet::create([
                            'id_dathang' => $order->id_dathang,
                            'id_bienthe' => $variant->id_bienthe,
                            'soluong' => $quantity,
                            'gia' => $price,
                            'hoantien' => 0,
                            'created_at' => $orderTime,
                            'updated_at' => $orderTime,
                        ]);
                    }

                    $order->update(['tongtien' => $total]);
                    $createdOrders++;

                    if ($orderIndex === 0) {
                        $reviewVariant = $lineVariants->first();
                        $review = $this->reviewFor($number);
                        DanhGia::create([
                            'id_dathang' => $order->id_dathang,
                            'id_bienthe' => $reviewVariant->id_bienthe,
                            'user_id' => $user->id,
                            'danhgia' => $review['rating'],
                            'binhluan' => $review['comment'],
                            'trangthai' => $review['status'],
                            'created_at' => $orderTime->copy()->addDays(rand(3, 10))->setTime(rand(9, 22), rand(0, 59)),
                        ]);
                        $createdReviews++;
                    }
                }
            }
        });

        $this->clearCaches();
        $this->info("Da tao {$createdUsers}/{$targetUsers} khach hang, {$createdViews} luot xem, {$createdOrders} don hang va {$createdReviews} danh gia.");

        return self::SUCCESS;
    }

    private function variants()
    {
        return DB::table('bienthe')
            ->join('sanpham', 'bienthe.id_sanpham', '=', 'sanpham.id_sanpham')
            ->where('bienthe.gia', '>', 0)
            ->where('bienthe.soluong', '>', 0)
            ->select('bienthe.id_bienthe', 'bienthe.id_sanpham', 'bienthe.gia')
            ->get();
    }

    private function nextUserIndex(): int
    {
        $count = User::query()
            ->where(function ($query) {
                $query->where('email', 'like', 'khachhang%@nextgen.local')
                    ->orWhereIn('id', $this->demoCustomerIdsQuery());
            })
            ->where('role', 'user')
            ->count();

        return $count + 1;
    }

    private function demoCustomerIdsQuery()
    {
        return DB::table('dathang')
            ->where('du_lieu_thanh_toan', 'like', '%customer_journey_demo%')
            ->select('id_khachhang');
    }

    private function emailFor(string $name, int $number): string
    {
        $slug = Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.');

        return $slug.'.'.str_pad((string) $number, 3, '0', STR_PAD_LEFT).'@gmail.com';
    }

    private function guessGender(string $name): string
    {
        foreach (['Thị', 'Hoài', 'Huyền', 'Mai', 'Linh', 'Yến', 'Vy', 'Ngọc', 'Hà', 'Ngân', 'Duyên', 'Chi', 'Hạ', 'Tâm', 'Châu', 'Hân', 'Thư', 'Trâm', 'Nhi', 'My', 'Ánh', 'Trang', 'Dương', 'Khuê', 'Lệ'] as $hint) {
            if (str_contains($name, $hint)) {
                return 'female';
            }
        }

        return 'male';
    }

    private function insertViewedProduct(int $userId, int $productId, Carbon $viewedAt): void
    {
        $userColumn = Schema::hasColumn('sanpham_daxem', 'id_khachhang') ? 'id_khachhang' : 'id_user';
        $timeColumn = Schema::hasColumn('sanpham_daxem', 'xem_luc') ? 'xem_luc' : 'viewed_at';

        DB::table('sanpham_daxem')->insert([
            $userColumn => $userId,
            'id_sanpham' => $productId,
            $timeColumn => $viewedAt,
            'created_at' => $viewedAt,
            'updated_at' => $viewedAt,
        ]);
    }

    private function reviewFor(int $number): array
    {
        if ($number % 20 === 0) {
            return [
                'rating' => rand(1, 2),
                'comment' => collect($this->spamReviews)->random(),
                'status' => 'spam',
            ];
        }

        if ($number % 7 === 0) {
            return [
                'rating' => rand(1, 2),
                'comment' => collect($this->negativeReviews)->random(),
                'status' => collect(['approved', 'pending'])->random(),
            ];
        }

        if ($number % 5 === 0) {
            return [
                'rating' => 3,
                'comment' => collect($this->neutralReviews)->random(),
                'status' => 'approved',
            ];
        }

        return [
            'rating' => rand(4, 5),
            'comment' => collect($this->positiveReviews)->random(),
            'status' => 'approved',
        ];
    }

    private function address(): string
    {
        return collect([
            '24 Nguyen Huu Tho, Quan 7, TP.HCM',
            '18 Pham Van Dong, Bac Tu Liem, Ha Noi',
            '92 Nguyen Van Linh, Hai Chau, Da Nang',
            '41 Le Loi, Quan 1, TP.HCM',
            '75 Hoang Quoc Viet, Cau Giay, Ha Noi',
            '36 Vo Van Ngan, Thu Duc, TP.HCM',
            '129 Tran Phu, Ha Dong, Ha Noi',
            '58 Dien Bien Phu, Binh Thanh, TP.HCM',
        ])->random();
    }

    private function clearCaches(): void
    {
        foreach (['all', 'week', 'month', 'year'] as $period) {
            Cache::forget("dashboard_data_{$period}");
        }

        Cache::forget('sanpham_cache_bust');
        Cache::forget('mobile_home_v2');
    }
}
