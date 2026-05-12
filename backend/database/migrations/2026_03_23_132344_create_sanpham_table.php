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
        Schema::create('sanpham', function (Blueprint $table) {
            $table->id('id_sanpham');

            $table->unsignedBigInteger('id_danhmuc');
            $table->unsignedBigInteger('id_thuonghieu');

            $table->string('tenSP');
            $table->string('maSP')->nullable();
            $table->decimal('giaSP', 12, 2);
            $table->decimal('giamgiaSP', 12, 2)->default(0);

            $table->string('hinhanh')->nullable();
            $table->integer('soluong')->default(0);
            $table->float('khoiluong')->nullable();
            $table->text('mota')->nullable();

            $table->timestamps();

            // FK
            $table->foreign('id_danhmuc')
                ->references('id_danhmuc')
                ->on('danhmuc')
                ->onDelete('cascade');

            $table->foreign('id_thuonghieu')
                ->references('id_thuonghieu')
                ->on('thuonghieu')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanpham');
    }
};
