<?php

namespace App\Services;

use App\Models\BirthdayCouponLog;
use App\Models\BirthdayCouponSetting;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserVoucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BirthdayCouponService
{
    /**
     * Retrieve active or default birthday settings
     */
    public function getSettings()
    {
        $settings = BirthdayCouponSetting::first();
        if (! $settings) {
            $settings = BirthdayCouponSetting::create([
                'kichhoat' => true,
                'giochay' => '08:30',
                'thoi_han_ngay' => 30,
                'mavoucher' => 'HAPPYBDAY100',
                'id_voucher' => null,
                'id_mau_email' => 'tpl-bday-default',
                'gui_mot_lan_moi_nam' => true,
                'thu_lai_khi_that_bai' => true,
                'thongbao_admin' => true,
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
        if (! $settings->id_voucher) {
            if ($settings->mavoucher) {
                return Promotion::where('code', $settings->mavoucher)
                    ->where('danhmuc', 'birthday')
                    ->whereIn('trangthai', ['running', 'open'])
                    ->where(fn ($q) => $q->whereNull('ngaybatdau')->orWhereDate('ngaybatdau', '<=', today()))
                    ->where(fn ($q) => $q->whereNull('ngayketthuc')->orWhereDate('ngayketthuc', '>=', today()))
                    ->first();
            }

            return null;
        }

        return Promotion::where('id', $settings->id_voucher)
            ->where('danhmuc', 'birthday')
            ->whereIn('trangthai', ['running', 'open'])
            ->where(fn ($q) => $q->whereNull('ngaybatdau')->orWhereDate('ngaybatdau', '<=', today()))
            ->where(fn ($q) => $q->whereNull('ngayketthuc')->orWhereDate('ngayketthuc', '>=', today()))
            ->first();
    }

    /**
     * Get birthday users for a date
     */
    public function getBirthdayUsers($date)
    {
        $targetDate = Carbon::parse($date);

        return User::whereMonth('ngaysinh', $targetDate->month)
            ->whereDay('ngaysinh', $targetDate->day)
            ->where('vaitro', 'user')
            ->where('trangthai', '!=', 'locked')
            ->whereNotNull('email')
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
     * Assign voucher to user in khachhang_voucher
     */
    public function assignVoucherToUser($user, $promotion)
    {
        $exists = UserVoucher::where('id_user', $user->id)
            ->where('id_voucher', $promotion->id)
            ->whereYear('ngay_nhan', Carbon::now()->year)
            ->first();

        if ($exists) {
            return $exists;
        }

        return UserVoucher::create([
            'id_user' => $user->id,
            'id_voucher' => $promotion->id,
            'trang_thai' => 0, // 0 means unused
            'ngay_nhan' => now(),
            'het_han_luc' => now()->addDays(max(1, (int) $this->getSettings()->thoi_han_ngay))->endOfDay(),
            'da_su_dung_luc' => null,
        ]);
    }

    /**
     * Send coupon to a single user
     */
    public function sendBirthdayCouponToUser($user, $promotion, $force = false)
    {
        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'status' => 'failed', 'error' => 'Địa chỉ email khách hàng không hợp lệ.'];
        }
        $today = today();
        $notStarted = $promotion->ngaybatdau && Carbon::parse($promotion->ngaybatdau)->startOfDay()->gt($today);
        $expired = $promotion->ngayketthuc && Carbon::parse($promotion->ngayketthuc)->endOfDay()->lt($today);
        if ($promotion->danhmuc !== 'birthday' || ! in_array($promotion->trangthai, ['running', 'open'], true) || $notStarted || $expired) {
            return ['success' => false, 'status' => 'failed', 'error' => 'Mã khuyến mãi sinh nhật không hoạt động.'];
        }

        $lock = Cache::lock('birthday-coupon:'.$user->id.':'.now()->year, 180);
        if (! $lock->get()) {
            return ['success' => false, 'status' => 'skipped', 'message' => 'Email đang được một tiến trình khác xử lý.'];
        }

        try {
            return $this->sendBirthdayCouponUnlocked($user, $promotion, $force);
        } finally {
            $lock->release();
        }
    }

    private function sendBirthdayCouponUnlocked($user, $promotion, $force = false)
    {
        $settings = $this->getSettings();

        // Check if user already received birthday coupon this year (if not forced and limit once per year is enabled)
        if (! $force && $settings->gui_mot_lan_moi_nam) {
            $alreadySentThisYear = BirthdayCouponLog::where('id_khachhang', $user->id)
                ->where('trangthai', 'sent')
                ->whereYear('guiluc', Carbon::now()->year)
                ->exists();

            if ($alreadySentThisYear) {
                Log::info("Skipping user {$user->email}: already received a coupon this year.");

                return [
                    'success' => false,
                    'status' => 'skipped',
                    'message' => 'User already received coupon this year',
                ];
            }
        }

        if (! $force) {
            $failedAttempts = BirthdayCouponLog::where('id_khachhang', $user->id)
                ->where('trangthai', 'failed')
                ->whereDate('created_at', today())
                ->count();
            if ((! $settings->thu_lai_khi_that_bai && $failedAttempts > 0) || $failedAttempts >= 3) {
                return ['success' => false, 'status' => 'skipped', 'message' => 'Đã đạt giới hạn thử gửi lại hôm nay.'];
            }
        }

        // Assign voucher
        $userVoucher = $this->assignVoucherToUser($user, $promotion);

        // Initialize log entry
        $log = BirthdayCouponLog::create([
            'id_khachhang' => $user->id,
            'ngaysinh' => $user->ngaysinh ? Carbon::parse($user->ngaysinh) : Carbon::today(),
            'id_voucher' => $promotion->id,
            'id_khachhang_voucher' => $userVoucher->id,
            'mavoucher' => $promotion->code,
            'email' => $user->email,
            'trangthai' => 'pending',
            'guiluc' => null,
            'thongbaoloi' => null,
        ]);

        try {
            Log::info("Sending birthday coupon to user {$user->email}");
            $this->mailBirthdayCoupon($user, $promotion->code, $promotion, $userVoucher->het_han_luc);

            $log->update([
                'trangthai' => 'sent',
                'guiluc' => Carbon::now(),
                'id_khachhang_voucher' => $userVoucher->id,
            ]);

            Log::info("Birthday coupon sent successfully to {$user->email}");

            return [
                'success' => true,
                'status' => 'sent',
            ];
        } catch (\Exception $e) {
            Log::error("Birthday coupon failed for {$user->email}: ".$e->getMessage());

            $log->update([
                'trangthai' => 'failed',
                'thongbaoloi' => $e->getMessage(),
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
        if (! $promotion) {
            // If the code doesn't exist, retrieve or mock it (legacy fallback)
            $promotion = Promotion::create([
                'ten' => 'Sinh Nhật Khách Hàng',
                'danhmuc' => 'birthday',
                'code' => $voucherCode,
                'loai' => 'fixed',
                'giatri' => 100000,
                'trangthai' => 'running',
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

        if (! $promotion) {
            Log::error('sendBulkBirthdayCoupons: No active birthday promotion found.');

            return $results;
        }

        foreach ($userIds as $id) {
            $user = User::find($id);
            if (! $user) {
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
    public function runAutomaticBirthdayCoupons($date = null, $force = false, $promotionId = null)
    {
        Log::info('Birthday auto sender started');

        $settings = $this->getSettings();
        Log::info('Auto birthday setting loaded');

        if (! $force && ! $settings->kichhoat) {
            Log::info('Auto birthday sender disabled');

            return [
                'success' => false,
                'reason' => 'Disabled in settings',
            ];
        }

        if (! $force) {
            $currentTime = Carbon::now()->format('H:i');
            $runTime = substr((string) $settings->giochay, 0, 5);
            if ($currentTime < $runTime) {
                Log::info("Current time {$currentTime} does not match run_time {$settings->giochay}");

                return [
                    'success' => false,
                    'reason' => 'Time mismatch',
                ];
            }
        }

        $promotion = $promotionId
            ? Promotion::where('id', $promotionId)->where('danhmuc', 'birthday')->whereIn('trangthai', ['running', 'open'])->first()
            : $this->getBirthdayPromotion();
        if (! $promotion) {
            Log::error('Birthday auto sender error: promotion is not configured or active.');

            return [
                'success' => false,
                'reason' => 'Vui lòng chọn mã khuyến mãi sinh nhật trước khi chạy tự động.',
            ];
        }

        $targetDate = $date ? Carbon::parse($date) : Carbon::today();
        $users = $this->getBirthdayUsers($targetDate);
        $userCount = $users->count();
        Log::info("Found {$userCount} birthday users for date ".$targetDate->toDateString());

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
    private function mailBirthdayCoupon(User $user, $voucherCode, $promo = null, $expiresAt = null)
    {
        $discountDesc = 'Món quà giảm giá đặc biệt';
        if ($promo) {
            if ($promo->loai === 'percent') {
                $discountDesc = "Giảm giá {$promo->giatri}%";
            } elseif ($promo->loai === 'fixed') {
                $discountDesc = 'Giảm ngay '.number_format($promo->giatri, 0, ',', '.').'đ';
            }
        }

        $emailData = [
            'name' => $user->ten,
            'voucher_code' => $voucherCode,
            'discount' => $discountDesc,
            'expiry' => $expiresAt ? Carbon::parse($expiresAt)->format('d/m/Y H:i') : 'Theo thời hạn của chương trình',
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
                            <h2 style="color: #1e293b; margin: 10px 0;">'.$emailData['discount'].'</h2>
                            <p style="margin: 0; font-size: 13px; color: #64748b;">Mã Voucher:</p>
                            <div style="background-color: #ffffff; display: inline-block; padding: 10px 20px; border-radius: 6px; border: 1px solid #cbd5e1; font-family: monospace; font-size: 20px; font-weight: bold; color: #2563eb; margin: 8px 0; letter-spacing: 1px;">
                                '.$emailData['voucher_code'].'
                            </div>
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">Hạn sử dụng: '.$emailData['expiry'].'</p>
                            <p style="margin: 6px 0 0; font-size: 12px; font-weight: 700; color: #dc2626;">Mã chỉ được sử dụng 01 lần cho tài khoản nhận email này.</p>
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
