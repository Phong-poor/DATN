<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dathang') && ! Schema::hasColumn('dathang', 'id_nhanvien')) {
            Schema::table('dathang', function (Blueprint $table) {
                $table->foreignId('id_nhanvien')
                    ->nullable()
                    ->after('id_khachhang')
                    ->constrained('admins')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('noi_dung_tro_chuyen') && ! Schema::hasColumn('noi_dung_tro_chuyen', 'nguoigui_type')) {
            Schema::table('noi_dung_tro_chuyen', function (Blueprint $table) {
                $table->string('nguoigui_type')->nullable()->after('id_nguoigui');
            });

            DB::table('noi_dung_tro_chuyen')
                ->whereNull('nguoigui_type')
                ->update(['nguoigui_type' => User::class]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dathang') && Schema::hasColumn('dathang', 'id_nhanvien')) {
            Schema::table('dathang', function (Blueprint $table) {
                $table->dropConstrainedForeignId('id_nhanvien');
            });
        }

        if (Schema::hasTable('noi_dung_tro_chuyen') && Schema::hasColumn('noi_dung_tro_chuyen', 'nguoigui_type')) {
            Schema::table('noi_dung_tro_chuyen', function (Blueprint $table) {
                $table->dropColumn('nguoigui_type');
            });
        }
    }
};
