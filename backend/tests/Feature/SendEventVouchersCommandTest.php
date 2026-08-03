<?php

namespace Tests\Feature;

use App\Mail\EventVoucherMail;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SendEventVouchersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('ten');
            $table->string('danhmuc');
            $table->string('code')->unique();
            $table->string('ngay_su_kien', 5)->nullable();
            $table->boolean('tu_dong_gui')->default(true);
            $table->string('loai');
            $table->decimal('giatri', 15, 2)->default(0);
            $table->dateTime('ngaybatdau')->nullable();
            $table->dateTime('ngayketthuc')->nullable();
            $table->string('trangthai')->default('open');
            $table->text('mota')->nullable();
            $table->string('loai_dieu_kien')->nullable();
            $table->decimal('dieu_kien', 15, 2)->nullable();
            $table->boolean('congkhai')->default(true);
            $table->decimal('dieu_kien_tang', 15, 2)->nullable();
            $table->unsignedInteger('so_luong_phat')->nullable();
        });

        Schema::create('khachhang_voucher', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_voucher');
            $table->integer('trang_thai')->default(0);
            $table->dateTime('ngay_nhan')->nullable();
            $table->dateTime('het_han_luc')->nullable();
            $table->dateTime('da_su_dung_luc')->nullable();
            $table->unique(['id_user', 'id_voucher']);
        });
    }

    public function test_command_assigns_event_voucher_sends_mail_and_prevents_duplicate_in_same_year(): void
    {
        Mail::fake();

        $customer = User::factory()->create([
            'vaitro' => 'user',
            'trangthai' => 'active',
        ]);

        $promotion = Promotion::create([
            'ten' => 'QUOCKHANH',
            'danhmuc' => 'event',
            'code' => 'QUOCKHANH', 'ngay_su_kien' => '02-09',
            'loai' => 'percent',
            'giatri' => 10,
            'trangthai' => 'open',
            'congkhai' => true,
        ]);

        $arguments = ['code' => 'QUOCKHANH', '--email' => [$customer->email]];

        $this->artisan('events:send-vouchers', $arguments)->assertSuccessful();

        $this->assertDatabaseHas('khachhang_voucher', [
            'id_user' => $customer->id,
            'id_voucher' => $promotion->id,
            'trang_thai' => 0,
        ]);
        Mail::assertSent(EventVoucherMail::class, 1);

        $this->artisan('events:send-vouchers', $arguments)->assertSuccessful();
        Mail::assertSent(EventVoucherMail::class, 1);
    }

    public function test_each_event_code_selects_its_own_email_theme(): void
    {
        $customer = new User(['ten' => 'Khách hàng']);
        $nationalDay = new EventVoucherMail($customer, new Promotion(['code' => 'QUOCKHANH']));
        $christmas = new EventVoucherMail($customer, new Promotion(['code' => 'GIANGSINH']));

        $this->assertSame('Tự Hào Việt Nam', $nationalDay->theme()['headline']);
        $this->assertSame('Giáng Sinh An Lành', $christmas->theme()['headline']);
        $this->assertNotSame($nationalDay->theme()['primary'], $christmas->theme()['primary']);
    }

    public function test_command_does_not_send_when_event_auto_send_is_disabled(): void
    {
        Mail::fake();
        $customer = User::factory()->create(['vaitro' => 'user', 'trangthai' => 'active']);

        Promotion::create([
            'ten' => 'Quốc Khánh',
            'danhmuc' => 'event',
            'code' => 'QUOCKHANH',
            'ngay_su_kien' => '02-09',
            'tu_dong_gui' => false,
            'loai' => 'percent',
            'giatri' => 10,
            'trangthai' => 'open',
        ]);

        $this->artisan('events:send-vouchers', ['code' => 'QUOCKHANH', '--email' => [$customer->email]])
            ->assertFailed();

        Mail::assertNothingSent();
        $this->assertDatabaseCount('khachhang_voucher', 0);
    }
}
