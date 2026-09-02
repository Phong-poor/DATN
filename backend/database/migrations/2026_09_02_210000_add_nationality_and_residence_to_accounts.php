<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['admins', 'khachhang'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'quoc_tich')) {
                    $table->string('quoc_tich', 100)->nullable()->after('gioitinh');
                }
                if (! Schema::hasColumn($tableName, 'dia_chi_thuong_tru')) {
                    $table->string('dia_chi_thuong_tru', 500)->nullable()->after('quoc_tich');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['admins', 'khachhang'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $columns = array_values(array_filter(
                    ['quoc_tich', 'dia_chi_thuong_tru'],
                    fn (string $column) => Schema::hasColumn($tableName, $column)
                ));
                if ($columns) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
