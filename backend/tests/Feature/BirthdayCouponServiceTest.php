<?php

namespace Tests\Feature;

use App\Models\BirthdayCouponLog;
use App\Models\BirthdayCouponSetting;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserVoucher;
use App\Services\BirthdayCouponService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BirthdayCouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private BirthdayCouponService $service;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $table) {
                $table->id();
                $table->string('ten');
                $table->string('danhmuc');
                $table->string('code')->unique();
                $table->string('loai');
                $table->decimal('giatri', 15, 2)->default(0);
                $table->dateTime('ngaybatdau')->nullable();
                $table->dateTime('ngayketthuc')->nullable();
                $table->string('trangthai')->default('open');
                $table->boolean('congkhai')->default(false);
            });
        }

        if (! Schema::hasTable('khachhang_voucher')) {
            Schema::create('khachhang_voucher', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_user');
                $table->unsignedBigInteger('id_voucher');
                $table->unsignedTinyInteger('trang_thai')->default(0);
                $table->dateTime('ngay_nhan')->nullable();
                $table->dateTime('het_han_luc')->nullable();
                $table->dateTime('da_su_dung_luc')->nullable();
            });
        }

        Carbon::setTestNow('2026-08-02 08:30:00');
        Mail::fake();
        $this->service = app(BirthdayCouponService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_merge([
            'ten' => 'Khach hang test',
            'email' => 'birthday@example.com',
            'matkhau' => 'password',
            'ngaysinh' => '2000-08-02',
            'vaitro' => 'user',
            'trangthai' => 'active',
        ], $overrides));
    }

    private function promotion(array $overrides = []): Promotion
    {
        return Promotion::create(array_merge([
            'ten' => 'Qua sinh nhat',
            'danhmuc' => 'birthday',
            'code' => 'BIRTHDAY2026',
            'loai' => 'percent',
            'giatri' => 10,
            'trangthai' => 'open',
            'congkhai' => false,
        ], $overrides));
    }

    private function settings(array $overrides = []): BirthdayCouponSetting
    {
        return BirthdayCouponSetting::create(array_merge([
            'kichhoat' => true,
            'giochay' => '08:30',
            'thoi_han_ngay' => 30,
            'mavoucher' => 'BIRTHDAY2026',
            'gui_mot_lan_moi_nam' => true,
            'thu_lai_khi_that_bai' => true,
            'thongbao_admin' => true,
        ], $overrides));
    }

    public function test_scan_finds_active_user_with_birthday_on_selected_day(): void
    {
        $user = $this->user();
        $this->assertTrue($this->service->getBirthdayUsers('2026-08-02')->contains($user));
    }

    public function test_scan_does_not_find_user_before_their_birthday(): void
    {
        $this->user(['ngaysinh' => '2000-08-03']);
        $this->assertCount(0, $this->service->getBirthdayUsers('2026-08-02'));
    }

    public function test_scan_excludes_locked_account(): void
    {
        $this->user(['trangthai' => 'locked']);
        $this->assertCount(0, $this->service->getBirthdayUsers('2026-08-02'));
    }

    public function test_invalid_email_is_rejected_without_creating_log(): void
    {
        $result = $this->service->sendBirthdayCouponToUser(
            $this->user(['email' => 'invalid-email']),
            $this->promotion()
        );

        $this->assertSame('failed', $result['status']);
        $this->assertSame(0, BirthdayCouponLog::count());
    }

    public function test_inactive_birthday_promotion_is_rejected(): void
    {
        $result = $this->service->sendBirthdayCouponToUser(
            $this->user(),
            $this->promotion(['trangthai' => 'closed'])
        );

        $this->assertSame('failed', $result['status']);
        $this->assertSame(0, BirthdayCouponLog::count());
    }

    public function test_valid_birthday_coupon_is_assigned_and_logged_as_sent(): void
    {
        $this->settings();
        $user = $this->user();
        $promotion = $this->promotion();

        $result = $this->service->sendBirthdayCouponToUser($user, $promotion);

        $this->assertSame(['success' => true, 'status' => 'sent'], $result);
        $this->assertDatabaseHas('khachhang_voucher', [
            'id_user' => $user->id,
            'id_voucher' => $promotion->id,
            'trang_thai' => 0,
        ]);
        $this->assertDatabaseHas('nhat_ky_gui_ma_sinh_nhat', [
            'id_khachhang' => $user->id,
            'mavoucher' => 'BIRTHDAY2026',
            'trangthai' => 'sent',
        ]);
    }

    public function test_coupon_expiry_uses_configured_number_of_days(): void
    {
        $this->settings(['thoi_han_ngay' => 30]);
        $user = $this->user();
        $promotion = $this->promotion();

        $this->service->sendBirthdayCouponToUser($user, $promotion);
        $voucher = UserVoucher::firstOrFail();

        $this->assertSame('2026-09-01', $voucher->het_han_luc->toDateString());
    }

    public function test_second_automatic_send_in_same_year_is_skipped(): void
    {
        $this->settings();
        $user = $this->user();
        $promotion = $this->promotion();

        $this->assertSame('sent', $this->service->sendBirthdayCouponToUser($user, $promotion)['status']);
        $this->assertSame('skipped', $this->service->sendBirthdayCouponToUser($user, $promotion)['status']);
        $this->assertSame(1, BirthdayCouponLog::count());
    }

    public function test_automatic_sender_stops_when_disabled(): void
    {
        $this->settings(['kichhoat' => false]);
        $result = $this->service->runAutomaticBirthdayCoupons('2026-08-02');

        $this->assertFalse($result['success']);
        $this->assertSame('Disabled in settings', $result['reason']);
    }

    public function test_automatic_sender_reports_zero_when_no_birthday_users_exist(): void
    {
        $promotion = $this->promotion();
        $this->settings(['id_voucher' => $promotion->id]);

        $result = $this->service->runAutomaticBirthdayCoupons('2026-08-02');

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['users_found']);
        $this->assertSame(0, $result['sent']);
        $this->assertSame(0, $result['failed']);
    }
}
