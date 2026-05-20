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
        Schema::table('dathang', function (Blueprint $table) {
            // Xóa ->change() để biến đây thành lệnh TẠO MỚI cột lydo
            $table->string('lydo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dathang', function (Blueprint $table) {
            // Hàm down sẽ xóa cột này nếu rollback
            $table->dropColumn('lydo');
        });
    }
};