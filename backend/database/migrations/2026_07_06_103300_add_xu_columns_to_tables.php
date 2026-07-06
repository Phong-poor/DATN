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
        if (Schema::hasTable('khachhang') && !Schema::hasColumn('khachhang', 'xu')) {
            Schema::table('khachhang', function (Blueprint $table) {
                $table->integer('xu')->default(0)->after('trangthai');
            });
        }

        if (Schema::hasTable('dathang')) {
            Schema::table('dathang', function (Blueprint $table) {
                if (!Schema::hasColumn('dathang', 'xu_dung')) {
                    $table->integer('xu_dung')->default(0)->after('giam_gia');
                }
                if (!Schema::hasColumn('dathang', 'xu_nhan')) {
                    $table->integer('xu_nhan')->default(0)->after('xu_dung');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('khachhang') && Schema::hasColumn('khachhang', 'xu')) {
            Schema::table('khachhang', function (Blueprint $table) {
                $table->dropColumn('xu');
            });
        }

        if (Schema::hasTable('dathang')) {
            Schema::table('dathang', function (Blueprint $table) {
                if (Schema::hasColumn('dathang', 'xu_dung')) {
                    $table->dropColumn('xu_dung');
                }
                if (Schema::hasColumn('dathang', 'xu_nhan')) {
                    $table->dropColumn('xu_nhan');
                }
            });
        }
    }
};
