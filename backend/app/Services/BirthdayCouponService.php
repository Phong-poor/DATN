<?php

namespace App\Services;

use App\Models\User;
use App\Models\Promotion;
use App\Models\BirthdayCouponLog;
use App\Models\BirthdayCouponSetting;
use App\Models\UserVoucher;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BirthdayCouponService
{
    /**
     * Retrieve active or default birthday settings
     */
    public function getSettings()
    {
        $settings = BirthdayCouponSetting::first();
        if (!$settings) {
            $settings = BirthdayCouponSetting::create([
                'enabled' => true,
                'run_time' => '08:30',
                'promotion_code' => 'HAPPYBDAY100',
                'promotion_id' => null,
                'email_template_id' => 'tpl-bday-default',
                'send_once_per_year' => true,
                'retry_if_failed' => true,
                'notify_admin' => true,
            ]);
        }
        return $settings;
    }

    /**
     * Retrieve active birthday promotion from settings
     */
    public function getBirthdayPromotion()
    {
        $settings = $this->getSettings();
        if (!$settings->promotion_id) {
            if ($settings->promotion_code) {
                return Promotion::where('code', $settings->promotion_code)
                    ->where('category', 'birthday')
                    ->whereIn('status', ['running', 'open'])
                    ->first();
            }
            return null;
        }

        return Promotion::where('id', $settings->promotion_id)
            ->where('category', 'birthday')
            ->whereIn('status', ['running', 'open'])
            ->first();
    }

    /**
     * Get birthday users for a date
     */
    public function getBirthdayUsers($date)
    {
        $targetDate = Carbon::parse($date);
        return User::whereMonth('date_of_birth', $targetDate->month)
            ->whereDay('date_of_birth', $targetDate->day)
            ->get();
    }

    /**
     * Scan birthday users (legacy support)
     */
    public function scanBirthdayUsers($date)
    {
        return $this->getBirthdayUsers($date);
    }

    /**
     * Assign voucher to user in users_voucher
     */
    public function assignVoucherToUser($user, $promotion)
    {
        $exists = UserVoucher::where('id_user', $user->id)
            ->where('id_promotion', $promotion->id)
            ->whereYear('ngay_nhan', Carbon::now()->year)
            ->first();

        if ($exists) {
            return $exists;
        }

        return UserVoucher::create([
            'id_user' => $user->id,
            'id_promotion' => $promotion->id,
            'trang_thai' => 0, // 0 means unused
            'ngay_nhan' => now(),
        ]);
    }

    /**
     * Send coupon to a single user
     */
    public function sendBirthdayCouponToUser($user, $promotion, $force = false)
    {
        $settings = $this->getSettings();

        // Check if user already received birthday coupon this year (if not forced and limit once per year is enabled)
        if (!$force && $settings->send_once_per_year) {
            $alreadySentThisYear = BirthdayCouponLog::where('user_id', $user->id)
                ->where('status', 'sent')
                ->whereYear('sent_at', Carbon::now()->year)
                ->exists();

            if ($alreadySentThisYear) {
                Log::info("Skipping user {$user->email}: already received a coupon this year.");
                return [
                    'success' => false,
                    'status' => 'skipped',
                    'message' => 'User already received coupon this year'
                ];
            }
        }

        // Assign voucher
        $userVoucher = $this->assignVoucherToUser($user, $promotion);

        // Initialize log entry
        $log = BirthdayCouponLog::updateOrCreate([
            'user_id' => $user->id,
            'birthday_date' => $user->date_of_birth ? Carbon::parse($user->date_of_birth) : Carbon::today(),
        ], [
            'promotion_id' => $promotion->id,
            'user_voucher_id' => $userVoucher->id,
            'voucher_code' => $promotion->code,
            'email' => $user->email,
            'status' => 'pending',
            'sent_at' => null,
            'error_message' => null,
        ]);

        try {
            Log::info("Sending birthday coupon to user {$user->email}");
            $this->mailBirthdayCoupon($user, $promotion->code, $promotion);

            $log->update([
                'status' => 'sent',
                'sent_at' => Carbon::now(),
                'user_voucher_id' => $userVoucher->id,
            ]);

            Log::info("Birthday coupon sent successfully to {$user->email}");
            return [
                'success' => true,
                'status' => 'sent',
            ];
        } catch (\Exception $e) {
            Log::error("Birthday coupon failed for {$user->email}: " . $e->getMessage());

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Legacy support function for sendBirthdayCoupon
     */
    public function sendBirthdayCoupon($userId, $voucherCode, $settings = null, $ignoreOncePerYear = false)
    {
        $user = User::findOrFail($userId);
        
        // Find promotion by code
        $promotion = Promotion::where('code', $voucherCode)->first();
        if (!$promotion) {
            // If the code doesn't exist, retrieve or mock it (legacy fallback)
            $promotion = Promotion::create([
                'name' => 'Sinh Nhật Khách Hàng',
                'category' => 'birthday',
                'code' => $voucherCode,
                'type' => 'fixed',
                'value' => 100000,
                'status' => 'running',
            ]);
        }

        return $this->sendBirthdayCouponToUser($user, $promotion, $ignoreOncePerYear);
    }

    /**
     * Send bulk coupons
     */
    public function sendBulkBirthdayCoupons(array $userIds, $promotionId = null)
    {
        $promotion = null;
        if ($promotionId) {
            $promotion = Promotion::find($promotionId);
        } else {
            $promotion = $this->getBirthdayPromotion();
        }

        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        if (!$promotion) {
            Log::error("sendBulkBirthdayCoupons: No active birthday promotion found.");
            return $results;
        }

        foreach ($userIds as $id) {
            $user = User::find($id);
            if (!$user) {
                $results['failed']++;
                continue;
            }
            $res = $this->sendBirthdayCouponToUser($user, $promotion);
            $results[$res['status']]++;
        }

        return $results;
    }

    /**
     * Run automatic birthday coupons
     */
    public function runAutomaticBirthdayCoupons($date = null, $force = false)
    {
        Log::info("Birthday auto sender started");

        $settings = $this->getSettings();
        Log::info("Auto birthday setting loaded");

        if (!$force && !$settings->enabled) {
            Log::info("Auto birthday sender disabled");
            return [
                'success' => false,
                'reason' => 'Disabled in settings'
            ];
        }

        if (!$force) {
            $currentTime = Carbon::now()->format('H:i');
            if ($currentTime !== $settings->run_time) {
                Log::info("Current time {$currentTime} does not match run_time {$settings->run_time}");
                return [
                    'success' => false,
                    'reason' => 'Time mismatch'
                ];
            }
        }

        $promotion = $this->getBirthdayPromotion();
        if (!$promotion) {
            Log::error("Birthday auto sender error: promotion is not configured or active.");
            return [
                'success' => false,
                'reason' => 'Vui lòng chọn mã khuyến mãi sinh nhật trước khi chạy tự động.'
            ];
        }

        $targetDate = $date ? Carbon::parse($date) : Carbon::today();
        $users = $this->getBirthdayUsers($targetDate);
        $userCount = $users->count();
        Log::info("Found {$userCount} birthday users for date " . $targetDate->toDateString());

        $sentCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            $res = $this->sendBirthdayCouponToUser($user, $promotion);
            if ($res['status'] === 'sent') {
                $sentCount++;
            } elseif ($res['status'] === 'failed') {
                $failCount++;
            } elseif ($res['status'] === 'skipped') {
                $skippedCount++;
            }
        }

        Log::info("Birthday auto sender finished. Sent: {$sentCount}, Failed: {$failCount}, Skipped: {$skippedCount}");

        return [
            'success' => true,
            'users_found' => $userCount,
            'sent' => $sentCount,
            'failed' => $failCount,
            'skipped' => $skippedCount,
        ];
    }

    /**
     * Legacy support function for sendAutomaticBirthdayCoupons
     */
    public function sendAutomaticBirthdayCoupons($forced = false)
    {
        return $this->runAutomaticBirthdayCoupons(null, $forced);
    }

    /**
     * Helper to dispatch HTML email
     */
    private function mailBirthdayCoupon(User $user, $voucherCode, $promo = null)
    {
        $discountDesc = "Món quà giảm giá đặc biệt";
        if ($promo) {
            if ($promo->type === 'percent') {
                $discountDesc = "Giảm giá {$promo->value}%";
            } elseif ($promo->type === 'fixed') {
                $discountDesc = "Giảm ngay " . number_format($promo->value, 0, ',', '.') . "đ";
            }
        }

        $emailData = [
            'name' => $user->name,
            'voucher_code' => $voucherCode,
            'discount' => $discountDesc,
            'expiry' => $promo && $promo->end_date ? Carbon::parse($promo->end_date)->format('d/m/Y') : '30 ngày kể từ hôm nay',
        ];

        Mail::send([], [], function ($message) use ($user, $emailData) {
            $message->to($user->email)
                ->subject('🎂 Chúc mừng sinh nhật từ Predator Group!')
                ->html('
                    <div style="font-family: \'Be Vietnam Pro\', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <h1 style="color: #2563eb; margin: 0;">Happy Birthday! 🎂</h1>
                            <p style="color: #64748b; font-size: 16px; margin: 5px 0 0 0;">Predator Group chúc bạn tuổi mới tràn đầy niềm vui và thành công!</p>
                        </div>
                        <div style="background-color: #f8fafc; padding: 20px; border-radius: 8px; border: 1px dashed #2563eb; text-align: center; margin-bottom: 20px;">
                            <p style="margin: 0; font-size: 14px; color: #475569;">Quà tặng sinh nhật đặc biệt dành cho bạn:</p>
                            <h2 style="color: #1e293b; margin: 10px 0;">' . $emailData['discount'] . '</h2>
                            <p style="margin: 0; font-size: 13px; color: #64748b;">Mã Voucher:</p>
                            <div style="background-color: #ffffff; display: inline-block; padding: 10px 20px; border-radius: 6px; border: 1px solid #cbd5e1; font-family: monospace; font-size: 20px; font-weight: bold; color: #2563eb; margin: 8px 0; letter-spacing: 1px;">
                                ' . $emailData['voucher_code'] . '
                            </div>
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">Hạn sử dụng: ' . $emailData['expiry'] . '</p>
                        </div>
                        <p style="font-size: 14px; color: #475569; line-height: 1.6;">Để sử dụng ưu đãi, bạn vui lòng nhập mã trên tại trang thanh toán khi mua hàng tại hệ thống của chúng tôi.</p>
                        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;" />
                        <div style="text-align: center; font-size: 12px; color: #94a3b8;">
                            <p style="margin: 0;">Predator Group - Hệ thống máy tính & thiết bị công nghệ hàng đầu.</p>
                            <p style="margin: 5px 0 0 0;">Cảm ơn bạn đã luôn đồng hành cùng chúng tôi!</p>
                        </div>
                    </div>
                ');
        });
    }
}
