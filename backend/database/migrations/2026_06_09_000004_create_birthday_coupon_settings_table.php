<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cai_dat_ma_sinh_nhat', function (Blueprint $table) {
            $table->id();
            $table->boolean('kichhoat')->default(true);
            $table->time('giochay')->default('08:00:00');
            $table->integer('id_voucher')->nullable();
            $table->string('mavoucher')->default('BIRTHDAY');
            $table->string('id_mau_email')->default('birthday_default');
            $table->boolean('gui_mot_lan_moi_nam')->default(true);
            $table->boolean('thu_lai_khi_that_bai')->default(true);
            $table->boolean('thongbao_admin')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cai_dat_ma_sinh_nhat');
    }
};
