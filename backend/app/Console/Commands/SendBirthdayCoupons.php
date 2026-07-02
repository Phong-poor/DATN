<?php

namespace App\Console\Commands;

use App\Services\BirthdayCouponService;
use Illuminate\Console\Command;

class SendBirthdayCoupons extends Command
{
    /**
     * Tên command để chạy trong terminal
     */
    protected $signature = 'birthdays:send-coupons {--force : Force run ignoring enabled and run_time settings}';

    /**
     * Mô tả command
     */
    protected $description = 'Send automatic birthday coupon emails to users whose birthday is today based on settings';

    /**
     * Hàm xử lý chính
     */
    public function handle(BirthdayCouponService $service): int
    {
        $forced = $this->option('force') ?? false;
        $result = $service->runAutomaticBirthdayCoupons(null, $forced);

        if ($result['success']) {
            $this->info('Completed. Found: '.($result['users_found'] ?? 0).', Sent: '.($result['sent'] ?? 0).', Failed: '.($result['failed'] ?? 0).', Skipped: '.($result['skipped'] ?? 0));
        } else {
            $this->warn('Stopped. Reason: '.($result['reason'] ?? 'Unknown'));
        }

        return self::SUCCESS;
    }
}
