<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nhom_thuoctinh', function (Blueprint $table) {
            $table->id('id_nhom');
            $table->string('ten_nhom');
            $table->boolean('trangthai')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhom_thuoctinh');
    }
};
