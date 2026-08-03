<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers')) return;
        if (! Schema::hasColumn('vouchers', 'ngay_su_kien')) {
            Schema::table('vouchers', fn (Blueprint $table) => $table->string('ngay_su_kien', 5)->nullable()->after('code'));
        }
        DB::table('vouchers')->where('danhmuc', 'event')->orderBy('id')->get()->each(function ($event) {
            $oldCode = trim((string) $event->code);
            $date = preg_match('/^\d{2}-\d{2}$/', $oldCode) ? $oldCode : null;
            $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', Str::ascii((string) $event->ten)));
            $base = preg_replace('/\d{2}-\d{2}$/', '', $base) ?: 'SUKIEN'.$event->id;
            $code = $base;
            $suffix = 2;
            while (DB::table('vouchers')->where('code', $code)->where('id', '!=', $event->id)->exists()) $code = $base.$suffix++;
            DB::table('vouchers')->where('id', $event->id)->update(['code' => $code, 'ngay_su_kien' => $date]);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('vouchers') && Schema::hasColumn('vouchers', 'ngay_su_kien')) {
            DB::table('vouchers')->where('danhmuc', 'event')->whereNotNull('ngay_su_kien')->get()
                ->each(fn ($event) => DB::table('vouchers')->where('id', $event->id)->update(['code' => $event->ngay_su_kien]));
            Schema::table('vouchers', fn (Blueprint $table) => $table->dropColumn('ngay_su_kien'));
        }
    }
};
