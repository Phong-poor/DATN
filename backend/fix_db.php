<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = DB::select('SHOW COLUMNS FROM dathang_chitiet');
$hasRefund = false;
foreach($columns as $col) {
    if($col->Field == 'is_refund') $hasRefund = true;
}

if (!$hasRefund) {
    echo "Adding is_refund column...\n";
    DB::statement('ALTER TABLE dathang_chitiet ADD COLUMN is_refund TINYINT(1) DEFAULT 0');
} else {
    echo "is_refund already exists.\n";
}
