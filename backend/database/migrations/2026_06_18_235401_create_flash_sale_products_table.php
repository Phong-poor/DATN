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
        Schema::create('flash_sale_products', function (Blueprint $table) {
            $table->id('id_flash_sale_product');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('id_bienthe');
            $table->decimal('gia_flash_sale', 12, 2);
            $table->integer('so_luong_gioi_han');
            $table->integer('so_luong_da_ban')->default(0);
            $table->timestamps();

            // Set up foreign keys
            $table->foreign('session_id')
                ->references('id_session')
                ->on('flash_sale_sessions')
                ->onDelete('cascade');

            $table->foreign('id_bienthe')
                ->references('id_bienthe')
                ->on('bienthe')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_sale_products');
    }
};
