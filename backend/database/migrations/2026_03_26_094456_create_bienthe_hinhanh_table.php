<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bienthe_hinhanh', function (Blueprint $table) {
            $table->id('id_bienthe_hinhanh');
            $table->unsignedBigInteger('id_bienthe');
            $table->string('duongdan');
            $table->tinyInteger('thutu')->default(0);
            $table->tinyInteger('macdinh')->default(0);

            $table->foreign('id_bienthe')
                ->references('id_bienthe')
                ->on('bienthe')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bienthe_hinhanh');
    }
};
