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
        Schema::create('bienthe_combo_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_bienthe');
            $table->unsignedBigInteger('id_combo');
            $table->string('loai_uudai'); // free or discount
            $table->decimal('giakhuyenmai_override', 12, 2)->nullable();
            $table->string('mota_uudai')->nullable();
            $table->integer('gioi_han_soluong')->nullable();
            $table->integer('da_su_dung')->default(0);
            $table->dateTime('ngay_het_han')->nullable();
            $table->tinyInteger('trangthai')->default(1);
            $table->timestamps();

            $table->foreign('id_bienthe')->references('id_bienthe')->on('bienthe')->onDelete('cascade');
            $table->foreign('id_combo')->references('id_combo')->on('combos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bienthe_combo_offers');
    }
};
