<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers') || Schema::hasColumn('vouchers', 'tu_dong_gui')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->boolean('tu_dong_gui')->default(true)->after('ngay_su_kien');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vouchers') || ! Schema::hasColumn('vouchers', 'tu_dong_gui')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('tu_dong_gui');
        });
    }
};
