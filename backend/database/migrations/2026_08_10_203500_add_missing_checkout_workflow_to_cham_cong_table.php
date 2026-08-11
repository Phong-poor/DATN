<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cham_cong', function (Blueprint $table) {
            $table->string('trang_thai', 32)->default('working')->after('tong_cong')->index();
            $table->text('ly_do_dieu_chinh')->nullable()->after('ghi_chu');
            $table->unsignedBigInteger('dieu_chinh_boi')->nullable()->after('ly_do_dieu_chinh');
            $table->timestamp('dieu_chinh_luc')->nullable()->after('dieu_chinh_boi');
            $table->foreign('dieu_chinh_boi')->references('id')->on('khachhang')->nullOnDelete();
        });

        DB::table('cham_cong')->whereNotNull('gio_ra')->update(['trang_thai' => 'completed']);
    }

    public function down(): void
    {
        Schema::table('cham_cong', function (Blueprint $table) {
            $table->dropForeign(['dieu_chinh_boi']);
            $table->dropColumn(['trang_thai', 'ly_do_dieu_chinh', 'dieu_chinh_boi', 'dieu_chinh_luc']);
        });
    }
};
