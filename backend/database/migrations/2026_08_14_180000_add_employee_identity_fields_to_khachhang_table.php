<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('khachhang', function (Blueprint $table) {
            $table->string('so_cccd', 12)->nullable()->unique()->after('sodienthoai');
            $table->date('ngay_cap_cccd')->nullable()->after('so_cccd');
            $table->string('noi_cap_cccd')->nullable()->after('ngay_cap_cccd');
            $table->string('anh_cccd_mat_truoc')->nullable()->after('noi_cap_cccd');
            $table->string('anh_cccd_mat_sau')->nullable()->after('anh_cccd_mat_truoc');
        });
    }

    public function down(): void
    {
        Schema::table('khachhang', function (Blueprint $table) {
            $table->dropUnique(['so_cccd']);
            $table->dropColumn([
                'so_cccd', 'ngay_cap_cccd', 'noi_cap_cccd',
                'anh_cccd_mat_truoc', 'anh_cccd_mat_sau',
            ]);
        });
    }
};
