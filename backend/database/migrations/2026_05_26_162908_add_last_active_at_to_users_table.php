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
        if (! Schema::hasColumn('khachhang', 'hoat_dong_cuoi_luc')) {
            Schema::table('khachhang', function (Blueprint $table) {
                $table->timestamp('hoat_dong_cuoi_luc')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('khachhang', 'hoat_dong_cuoi_luc')) {
            Schema::table('khachhang', function (Blueprint $table) {
                $table->dropColumn('hoat_dong_cuoi_luc');
            });
        }
    }
};
