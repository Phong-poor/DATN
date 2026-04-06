<?php

namespace App\Console\Commands;

use App\Mail\BirthdayCouponMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBirthdayCoupons extends Command
{
    /**
     * Tên command để chạy trong terminal
     */
    protected $signature = 'birthday:send-coupons';

    /**
     * Mô tả command
     */
    protected $description = 'Send birthday coupon emails to users whose birthday is today';

    /**
     * Hàm xử lý chính
     */
    public function handle(): int
    {
        $today = Carbon::today();

        // Mã giảm giá fix cứng
        $birthdayCoupon = 'HAPPYBIRTHDAY2026';

        $users = User::query()
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->get();

        if ($users->isEmpty()) {
            $this->info('Không có user nào sinh nhật hôm nay.');
            return self::SUCCESS;
        }

        foreach ($users as $user) {
            if (empty($user->email)) {
                $this->warn("User ID {$user->id} không có email, bỏ qua.");
                continue;
            }

            try {
                Mail::to($user->email)->send(
                    new BirthdayCouponMail(
                        $user->name ?? 'Bạn',
                        $birthdayCoupon
                    )
                );

                $this->info("Đã gửi email cho: {$user->email}");
            } catch (\Exception $e) {
                $this->error("Gửi thất bại cho {$user->email}: " . $e->getMessage());
            }
        }

        $this->info('Hoàn tất gửi email sinh nhật.');
        return self::SUCCESS;
    }
}