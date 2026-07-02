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
        Schema::create('sanpham_daxem', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_khachhang');
            $table->unsignedBigInteger('id_sanpham');
            $table->timestamp('xem_luc')->useCurrent();
            $table->timestamps();

            $table->foreign('id_khachhang')->references('id')->on('khachhang')->onDelete('cascade');
            $table->foreign('id_sanpham')->references('id_sanpham')->on('sanpham')->onDelete('cascade');

            $table->unique(['id_khachhang', 'id_sanpham']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanpham_daxem');
    }
};
