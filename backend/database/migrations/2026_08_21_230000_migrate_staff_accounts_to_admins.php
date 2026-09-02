<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<string, string>> */
    private array $staffForeignKeys = [
        'nhat_ky_admin' => ['id_khachhang' => 'cascade'],
        'cham_cong' => ['id_nhanvien' => 'cascade', 'dieu_chinh_boi' => 'set null'],
        'lich_lam_nhan_vien' => ['id_nhanvien' => 'cascade'],
        'don_xin_nghi' => ['id_nhanvien' => 'cascade', 'xu_ly_boi' => 'set null'],
    ];

    public function up(): void
    {
        $this->copyLegacyStaffAccounts();

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->assertNoOrphanedStaffReferences();

        foreach ($this->staffForeignKeys as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => $onDelete) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $this->replaceForeignKey($table, $column, 'admins', $onDelete);
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->staffForeignKeys as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column => $onDelete) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $this->replaceForeignKey($table, $column, 'khachhang', $onDelete);
            }
        }
    }

    private function copyLegacyStaffAccounts(): void
    {
        if (! Schema::hasTable('admins') || ! Schema::hasTable('khachhang')) {
            return;
        }

        $columns = array_values(array_intersect(
            Schema::getColumnListing('admins'),
            Schema::getColumnListing('khachhang'),
        ));

        DB::table('khachhang')
            ->whereNotIn('vaitro', ['user'])
            ->orderBy('id')
            ->chunkById(100, function ($staff) use ($columns): void {
                foreach ($staff as $account) {
                    $row = [];
                    foreach ($columns as $column) {
                        $row[$column] = $account->{$column};
                    }

                    DB::table('admins')->insertOrIgnore($row);
                }
            });
    }

    private function assertNoOrphanedStaffReferences(): void
    {
        foreach ($this->staffForeignKeys as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($columns) as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $orphanCount = DB::table($table)
                    ->whereNotNull($column)
                    ->whereNotExists(function ($query) use ($table, $column): void {
                        $query->selectRaw('1')
                            ->from('admins')
                            ->whereColumn('admins.id', $table.'.'.$column);
                    })
                    ->count();

                if ($orphanCount > 0) {
                    throw new \RuntimeException(
                        "Cannot secure {$table}.{$column}: {$orphanCount} row(s) do not reference an admins record."
                    );
                }
            }
        }
    }

    private function replaceForeignKey(string $table, string $column, string $referencedTable, string $onDelete): void
    {
        $database = DB::getDatabaseName();
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        Schema::table($table, function (Blueprint $blueprint) use ($constraint, $column, $referencedTable, $onDelete): void {
            if ($constraint) {
                $blueprint->dropForeign($constraint);
            }

            $foreign = $blueprint->foreign($column)->references('id')->on($referencedTable);
            match ($onDelete) {
                'cascade' => $foreign->cascadeOnDelete(),
                'set null' => $foreign->nullOnDelete(),
                default => null,
            };
        });
    }
};
