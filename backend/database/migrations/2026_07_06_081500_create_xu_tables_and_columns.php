<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('khachhang', function (Blueprint $table) {
            if (!Schema::hasColumn('khachhang', 'xu')) {
                $table->unsignedBigInteger('xu')->default(0)->after('trangthai');
            }
        });

        Schema::table('dathang', function (Blueprint $table) {
            if (!Schema::hasColumn('dathang', 'xu_dung')) {
                $table->unsignedBigInteger('xu_dung')->default(0)->after('tongtien');
            }

            if (!Schema::hasColumn('dathang', 'xu_nhan')) {
                $table->unsignedBigInteger('xu_nhan')->default(0)->after('xu_dung');
            }
        });

        if (!Schema::hasTable('cauhinh_xu')) {
            Schema::create('cauhinh_xu', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('ti_le_quy_doi')->default(1);
                $table->decimal('ti_le_tich_luy', 5, 2)->default(1);
                $table->unsignedTinyInteger('phan_tram_giam_toi_da')->default(50);
                $table->boolean('trang_thai')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lich_su_xu')) {
            Schema::create('lich_su_xu', function (Blueprint $table) {
                $table->id('id_lichsu');
                $table->foreignId('id_khachhang')->constrained('khachhang')->cascadeOnDelete();
                $table->integer('so_xu');
                $table->string('loai_giao_dich', 50);
                $table->foreignId('id_dathang')->nullable()->constrained('dathang', 'id_dathang')->nullOnDelete();
                $table->text('mo_ta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_su_xu');
        Schema::dropIfExists('cauhinh_xu');

        Schema::table('dathang', function (Blueprint $table) {
            if (Schema::hasColumn('dathang', 'xu_nhan')) {
                $table->dropColumn('xu_nhan');
            }

            if (Schema::hasColumn('dathang', 'xu_dung')) {
                $table->dropColumn('xu_dung');
            }
        });

        Schema::table('khachhang', function (Blueprint $table) {
            if (Schema::hasColumn('khachhang', 'xu')) {
                $table->dropColumn('xu');
            }
        });
    }
};
