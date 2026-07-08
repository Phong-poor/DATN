<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vong_quays') || !Schema::hasTable('vouchers')) {
            return;
        }

        DB::statement('
            UPDATE vong_quays vq
            LEFT JOIN vouchers v ON v.id = vq.id_voucher
            SET vq.id_voucher = NULL
            WHERE vq.id_voucher IS NOT NULL
              AND v.id IS NULL
        ');
    }

    public function down(): void
    {
        //
    }
};
