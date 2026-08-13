<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('dathang')) {
            return;
        }

        if (! Schema::hasColumn('dathang', 'lydo')) {
            Schema::table('dathang', function (Blueprint $table) {
                $table->text('lydo')->nullable();
            });
        }

        if (Schema::hasColumn('dathang', 'ly_do_huy')) {
            // 1. Transfer data from ly_do_huy to lydo
            DB::statement('UPDATE dathang SET lydo = ly_do_huy WHERE lydo IS NULL AND ly_do_huy IS NOT NULL');

            // 2. Drop ly_do_huy column
            Schema::table('dathang', function (Blueprint $table) {
                $table->dropColumn('ly_do_huy');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('dathang')) {
            return;
        }

        if (! Schema::hasColumn('dathang', 'ly_do_huy')) {
            Schema::table('dathang', function (Blueprint $table) {
                $table->text('ly_do_huy')->nullable()->after('PTTT');
            });
        }

        // Copy back if needed (optional, but good for rollbacks)
        if (Schema::hasColumn('dathang', 'lydo')) {
            DB::statement('UPDATE dathang SET ly_do_huy = lydo WHERE ly_do_huy IS NULL AND lydo IS NOT NULL');
        }
    }
};
