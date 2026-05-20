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
        Schema::table('giatri_thuoctinh', function (Blueprint $table) {
            $table->json('danh_muc_ids')->nullable()->after('gia_cong_them');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('giatri_thuoctinh', function (Blueprint $table) {
            $table->dropColumn('danh_muc_ids');
        });
    }
};
