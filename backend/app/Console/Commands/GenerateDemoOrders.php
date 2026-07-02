<?php

namespace App\Console\Commands;

use App\Models\DatHang;
use App\Models\DatHangChiTiet;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateDemoOrders extends Command
{
    protected $signature = 'demo:orders
        {--count=20 : So don demo can co trong ngay}
        {--date=today : Ngay tao don, vi du 2026-06-28 hoac today}
        {--force : Tao them dung so don, khong bu theo so da co}';

    protected $description = 'Tao don hang demo hang ngay de dashboard co du lieu bieu do.';

    public function handle(): int
    {
        $targetCount = max(1, (int) $this->option('count'));
        $date = $this->parseDate((string) $this->option('date'));
        $batchDate = $date->toDateString();

        $existingCount = DatHang::query()
            ->whereDate('created_at', $batchDate)
            ->where('du_lieu_thanh_toan', 'like', '%"demo_orders":true%')
            ->count();

        $ordersToCreate = $this->option('force')
            ? $targetCount
            : max(0, $targetCount - $existingCount);

        if ($ordersToCreate === 0) {
            $this->info("Ngay {$batchDate} da co {$existingCount} don demo, khong can tao them.");

            return self::SUCCESS;
        }

        $users = $this->users();
        $variants = $this->variants();

        if ($variants->isEmpty()) {
            $this->error('Khong co bien the san pham nao de tao don demo.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($ordersToCreate, $users, $variants, $date, $batchDate) {
            for ($i = 0; $i < $ordersToCreate; $i++) {
                $createdAt = $date->copy()
                    ->setTime(rand(8, 22), rand(0, 59), rand(0, 59));

                $lineCount = rand(1, 3);
                $selectedVariants = $variants->shuffle()->take($lineCount);
                $total = 0;
                $lines = [];

                foreach ($selectedVariants as $variant) {
                    $quantity = rand(1, 2);
                    $price = (float) $variant->gia;
                    $total += $price * $quantity;

                    $lines[] = [
                        'id_bienthe' => $variant->id_bienthe,
                        'soluong' => $quantity,
                        'gia' => $price,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                        'hoantien' => 0,
                    ];
                }

                $user = $users->random();
                $order = DatHang::create([
                    'id_khachhang' => $user->id,
                    'tongtien' => $total,
                    'trangthai' => 'done',
                    'diachi' => $this->demoAddress(),
                    'PTTT' => collect(['COD', 'VNPAY', 'MOMO'])->random(),
                    'trang_thai_thanh_toan' => 'paid',
                    'nha_cung_cap_thanh_toan' => 'demo',
                    'ma_don_hang_thanh_toan' => 'DEMO-'.$batchDate.'-'.Str::upper(Str::random(8)),
                    'thanh_toan_luc' => $createdAt,
                    'du_lieu_thanh_toan' => [
                        'demo_orders' => true,
                        'batch_date' => $batchDate,
                        'generated_by' => 'demo:orders',
                    ],
                    'giam_gia' => 0,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                foreach ($lines as $line) {
                    $line['id_dathang'] = $order->id_dathang;
                    DatHangChiTiet::create($line);
                }
            }
        });

        $this->clearDashboardCache();
        $this->info("Da tao {$ordersToCreate} don demo cho ngay {$batchDate}.");

        return self::SUCCESS;
    }

    private function parseDate(string $value): CarbonInterface
    {
        if ($value === '' || strtolower($value) === 'today') {
            return now();
        }

        return Carbon::parse($value);
    }

    private function users()
    {
        $users = User::query()
            ->where('role', 'user')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 'locked');
            })
            ->get();

        if ($users->isNotEmpty()) {
            return $users;
        }

        return collect(range(1, 5))->map(function ($index) {
            return User::create([
                'name' => 'Khach hang demo '.$index,
                'email' => 'demo.customer'.$index.'@nextgen.test',
                'password' => Hash::make('12345678'),
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        });
    }

    private function variants()
    {
        return DB::table('bienthe')
            ->join('sanpham', 'bienthe.id_sanpham', '=', 'sanpham.id_sanpham')
            ->where('bienthe.soluong', '>', 0)
            ->where('bienthe.gia', '>', 0)
            ->select('bienthe.id_bienthe', 'bienthe.gia')
            ->get();
    }

    private function demoAddress(): string
    {
        return collect([
            '12 Nguyen Van Linh, Quan 7, TP.HCM',
            '88 Tran Duy Hung, Cau Giay, Ha Noi',
            '45 Le Duan, Hai Chau, Da Nang',
            '19 Vo Van Ngan, Thu Duc, TP.HCM',
            '72 Nguyen Trai, Thanh Xuan, Ha Noi',
        ])->random();
    }

    private function clearDashboardCache(): void
    {
        foreach (['all', 'week', 'month', 'year'] as $period) {
            Cache::forget("dashboard_data_{$period}");
        }
    }
}
