<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
DB::statement("ALTER TABLE vouchers MODIFY COLUMN danhmuc ENUM('product', 'birthday', 'freeship', 'event') NOT NULL DEFAULT 'product'");
echo 'Done';
