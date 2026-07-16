<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('khachhang', 'anhdaidien')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE khachhang MODIFY anhdaidien TEXT NULL');
            return;
        }

        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('khachhang', function ($table) {
            $table->text('anhdaidien')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('khachhang', 'anhdaidien')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE khachhang MODIFY anhdaidien VARCHAR(255) NULL');
            return;
        }

        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('khachhang', function ($table) {
            $table->string('anhdaidien')->nullable()->change();
        });
    }
};
