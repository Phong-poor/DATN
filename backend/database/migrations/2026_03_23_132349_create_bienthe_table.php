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
        Schema::create('bienthe', function (Blueprint $table) {
            $table->id('id_bienthe');

            $table->unsignedBigInteger('id_sanpham');

            $table->string('ten_bienthe')->nullable(); 
            // "16GB - 512GB"

            $table->decimal('gia', 12, 2);   // giá riêng
            $table->integer('soluong');      // tồn kho riêng

            $table->timestamps();

            $table->foreign('id_sanpham')
                ->references('id_sanpham')
                ->on('sanpham')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bienthe');
    }
};
