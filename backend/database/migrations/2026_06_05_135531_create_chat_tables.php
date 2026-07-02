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
        Schema::create('cuoc_tro_chuyen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_khachhang');
            $table->text('tin_nhan_cuoi')->nullable();
            $table->timestamp('tin_nhan_cuoi_luc')->nullable();
            $table->timestamps();

            $table->foreign('id_khachhang')->references('id')->on('khachhang')->onDelete('cascade');
        });

        Schema::create('noi_dung_tro_chuyen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cuoc_tro_chuyen');
            $table->unsignedBigInteger('id_nguoigui');
            $table->text('noidung')->nullable();
            $table->boolean('daxem')->default(false);
            $table->string('duongdan_dinhkem')->nullable();
            $table->string('ten_dinhkem')->nullable();
            $table->timestamps();

            $table->foreign('id_cuoc_tro_chuyen')->references('id')->on('cuoc_tro_chuyen')->onDelete('cascade');
            $table->foreign('id_nguoigui')->references('id')->on('khachhang')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('noi_dung_tro_chuyen');
        Schema::dropIfExists('cuoc_tro_chuyen');
    }
};
