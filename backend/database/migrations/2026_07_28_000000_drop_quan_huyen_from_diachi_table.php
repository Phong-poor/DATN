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
        if (Schema::hasColumn('diachi', 'quan_huyen')) {
            Schema::table('diachi', function (Blueprint $table) {
                $table->dropColumn('quan_huyen');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('diachi', 'quan_huyen')) {
            Schema::table('diachi', function (Blueprint $table) {
                $table->string('quan_huyen', 255)->nullable()->after('tinh_thanhpho');
            });
        }
    }
};
