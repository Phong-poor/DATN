<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lien_ket_affiliate', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_affiliate_khachhang');
            $table->unsignedBigInteger('id_khachhang_duoc_gioithieu')->nullable();
            $table->unsignedBigInteger('id_donhang')->nullable();
            $table->decimal('so_tien', 15, 2)->default(0);
            $table->string('trangthai')->default('pending');
            $table->timestamp('duoc_duyet_luc')->nullable();
            $table->timestamp('duoc_thanh_toan_luc')->nullable();
            $table->text('ghichu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lien_ket_affiliate');
    }
};
