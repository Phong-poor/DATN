<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('giohang', function (Blueprint $table) {
            if (! Schema::hasColumn('giohang', 'id_combo')) {
                $table->unsignedBigInteger('id_combo')->nullable()->after('soluong');
            }

            if (! Schema::hasColumn('giohang', 'id_nhom_combo')) {
                $table->string('id_nhom_combo')->nullable()->after('id_combo');
            }
        });

        try {
            DB::statement('ALTER TABLE giohang DROP INDEX giohang_user_id_id_bienthe_unique');
        } catch (\Throwable $e) {
            // The index may have already been removed on existing databases.
        }

        $userColumn = Schema::hasColumn('giohang', 'user_id') ? 'user_id' : 'id_khachhang';

        if (Schema::hasColumn('giohang', $userColumn)) {
            try {
                Schema::table('giohang', function (Blueprint $table) use ($userColumn) {
                    $table->index([$userColumn, 'id_bienthe']);
                });
            } catch (\Throwable $e) {
                // Keep migration idempotent for local databases with manual indexes.
            }

            if (Schema::hasColumn('giohang', 'id_nhom_combo')) {
                try {
                    Schema::table('giohang', function (Blueprint $table) use ($userColumn) {
                        $table->index([$userColumn, 'id_nhom_combo']);
                    });
                } catch (\Throwable $e) {
                    // Keep migration idempotent for local databases with manual indexes.
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('giohang', function (Blueprint $table) {
            try {
                $table->dropIndex(['id_khachhang', 'id_bienthe']);
                $table->dropIndex(['id_khachhang', 'id_nhom_combo']);
            } catch (\Throwable $e) {
                // Ignore missing indexes.
            }
        });
    }
};
