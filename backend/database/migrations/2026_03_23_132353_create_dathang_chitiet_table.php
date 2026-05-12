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
        Schema::create('dathang_chitiet', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_dathang');
            $table->unsignedBigInteger('id_bienthe');

            $table->integer('soluong');
            $table->decimal('gia', 12, 2);

            $table->timestamps();

            $table->foreign('id_dathang')
                ->references('id_dathang')
                ->on('dathang')
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
        Schema::dropIfExists('dathang_chitiet');
    }
};
