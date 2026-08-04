<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PromotionVisibilityTest extends TestCase
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
            $table->unsignedBigInteger('id_voucher');
        });

        if (! Schema::hasColumn('dathang', 'id_khuyenmai')) {
            Schema::table('dathang', function (Blueprint $table) {
                $table->unsignedBigInteger('id_khuyenmai')->nullable();
            });
        }
    }

    public function test_public_promotions_do_not_include_birthday_vouchers(): void
    {

        Promotion::create([
            'ten' => 'Quà sinh nhật',
            'danhmuc' => 'birthday',
            'code' => 'BIRTHDAY-HIDDEN',
            'loai' => 'fixed',
            'giatri' => 100000,
            'trangthai' => 'open',
            'congkhai' => 1,
        ]);

        Promotion::create([
            'ten' => 'Khuyến mãi công khai',
            'danhmuc' => 'product',
            'code' => 'PUBLIC-VOUCHER',
            'loai' => 'fixed',
            'giatri' => 50000,
            'trangthai' => 'open',
            'congkhai' => 1,
        ]);

        $response = $this->getJson('/api/promotions');

        $response->assertOk()
            ->assertJsonFragment(['code' => 'PUBLIC-VOUCHER'])
            ->assertJsonMissing(['code' => 'BIRTHDAY-HIDDEN']);
    }

    public function test_admin_can_create_update_and_delete_an_event_campaign_in_database(): void
    {
        Sanctum::actingAs(User::factory()->create(['vaitro' => 'admin']));

        $createResponse = $this->postJson('/api/admin/promotions', [
            'ten' => 'Quốc khánh',
            'danhmuc' => 'event',
            'code' => 'QUOCKHANH', 'ngay_su_kien' => '02-09',
            'loai' => 'percent',
            'giatri' => 10,
            'trangthai' => 'open',
            'congkhai' => true,
        ]);

        $createResponse->assertOk();
        $campaignId = $createResponse->json('promotion.id');
        $this->assertDatabaseHas('vouchers', [
            'id' => $campaignId,
            'ten' => 'Quốc khánh',
            'danhmuc' => 'event',
            'code' => 'QUOCKHANH', 'ngay_su_kien' => '02-09',
        ]);

        $this->putJson("/api/admin/promotions/{$campaignId}", [
            'ten' => 'Quốc khánh siêu sale',
            'danhmuc' => 'event',
            'code' => 'QUOCKHANH', 'ngay_su_kien' => '02-09',
            'loai' => 'percent',
            'giatri' => 20,
            'trangthai' => 'open',
            'congkhai' => true,
        ])->assertOk();

        $this->assertDatabaseHas('vouchers', [
            'id' => $campaignId,
            'ten' => 'Quốc khánh siêu sale',
            'giatri' => 20,
        ]);

        $this->deleteJson("/api/admin/promotions/{$campaignId}")->assertOk();
        $this->assertDatabaseMissing('vouchers', ['id' => $campaignId]);
    }
}
