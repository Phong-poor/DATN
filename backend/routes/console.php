<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:mark-missing-checkouts', function () {
    $count = DB::table('cham_cong')
        ->where('ngay_cham_cong', '<', Carbon::today('Asia/Ho_Chi_Minh')->toDateString())
        ->whereNotNull('gio_vao')
        ->whereNull('gio_ra')
        ->where(function ($query) {
            $query->whereNull('trang_thai')->orWhere('trang_thai', 'working');
        })
        ->update([
            'trang_thai' => 'missing_checkout',
            'tong_gio' => 0,
            'tong_cong' => 0,
            'ghi_chu' => 'Quên chấm ra; chờ quản trị viên xác minh và bổ sung.',
            'updated_at' => now(),
        ]);

    $this->info("Đã đánh dấu {$count} ca quên chấm ra.");
})->purpose('Đánh dấu các ca ngày trước còn thiếu giờ ra');

Schedule::command('attendance:mark-missing-checkouts')
    ->dailyAt('00:05')
    ->timezone('Asia/Ho_Chi_Minh')
    ->withoutOverlapping();
Schedule::command('birthdays:send-coupons')->everyMinute();

