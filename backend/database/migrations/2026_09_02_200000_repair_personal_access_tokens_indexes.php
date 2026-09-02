<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            return;
        }

        // Bảng do migration gốc tạo trên SQLite đã đúng cấu trúc; các câu lệnh
        // SHOW INDEX/ALTER bên dưới chỉ dành cho việc sửa schema MariaDB cũ.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM personal_access_tokens'));

        if (! $indexes->contains(fn ($index) => $index->Key_name === 'PRIMARY')) {
            DB::statement(
                'ALTER TABLE personal_access_tokens '
                .'MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, '
                .'ADD PRIMARY KEY (id)'
            );
        } else {
            DB::statement(
                'ALTER TABLE personal_access_tokens '
                .'MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        }

        $indexes = collect(DB::select('SHOW INDEX FROM personal_access_tokens'));

        if (! $indexes->contains(fn ($index) => $index->Key_name === 'personal_access_tokens_token_unique')) {
            DB::statement(
                'ALTER TABLE personal_access_tokens '
                .'ADD UNIQUE INDEX personal_access_tokens_token_unique (token)'
            );
        }

        if (! $indexes->contains(fn ($index) => $index->Key_name === 'personal_access_tokens_tokenable_type_tokenable_id_index')) {
            DB::statement(
                'ALTER TABLE personal_access_tokens '
                .'ADD INDEX personal_access_tokens_tokenable_type_tokenable_id_index '
                .'(tokenable_type, tokenable_id)'
            );
        }

        if (! $indexes->contains(fn ($index) => $index->Key_name === 'personal_access_tokens_expires_at_index')) {
            DB::statement(
                'ALTER TABLE personal_access_tokens '
                .'ADD INDEX personal_access_tokens_expires_at_index (expires_at)'
            );
        }
    }

    public function down(): void
    {
        // Giữ nguyên khóa và dữ liệu token vì rollback không nên làm hỏng đăng nhập.
    }
};
