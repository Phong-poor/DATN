<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DIAGNOSTIC: DATABASE CHECK ===\n\n";

// Check database connection
try {
    $pdo = DB::connection()->getPdo();
    echo "✅ Database connected: " . DB::connection()->getDatabaseName() . "\n\n";
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Count products
$productCount = DB::table('sanpham')->count();
echo "📦 Total products in sanpham table: $productCount\n";

// Count categories  
$categoryCount = DB::table('danhmuc')->count();
echo "📁 Total categories in danhmuc table: $categoryCount\n\n";

// Show first 3 products
echo "=== First 3 products ===\n";
$products = DB::table('sanpham')->limit(3)->get(['id_sanpham', 'tenSP', 'trangthai']);
foreach ($products as $product) {
    echo "- ID: {$product->id_sanpham}, Name: {$product->tenSP}, Status: {$product->trangthai}\n";
}

echo "\n=== First 3 categories ===\n";
$categories = DB::table('danhmuc')->limit(3)->get(['id_danhmuc', 'ten_danhmuc', 'trangthai']);
foreach ($categories as $cat) {
    echo "- ID: {$cat->id_danhmuc}, Name: {$cat->ten_danhmuc}, Status: {$cat->trangthai}\n";
}

echo "\n=== Test mobileHome query ===\n";

// Test the exact query from mobileHome method
$categories = \App\Models\DanhMuc::query()
    ->select('id_danhmuc', 'ten_danhmuc', 'trangthai', 'id_danhmuc_cha')
    ->orderBy('id_danhmuc')
    ->get();
    
echo "Categories from model: " . $categories->count() . "\n";

$products = \App\Models\SanPham::query()
    ->select(
        'id_sanpham',
        'tenSP',
        'SKU',
        'hinhanh',
        'trangthai',
        'id_danhmuc',
        'id_thuonghieu',
    )
    ->with([
        'danhMuc:id_danhmuc,ten_danhmuc,trangthai,id_danhmuc_cha',
        'thuongHieu:id_thuonghieu,ten_thuonghieu',
        'bienThes:id_bienthe,id_sanpham,ten_bienthe,gia,soluong,thuoc_tinh_json',
    ])
    ->orderByDesc('id_sanpham')
    ->limit(12)
    ->get();

echo "Products from model: " . $products->count() . "\n";

if ($products->count() === 0) {
    echo "\n⚠️  WARNING: Model query returns 0 products!\n";
    echo "Possible causes:\n";
    echo "- Table name mismatch\n";
    echo "- Model configuration issue\n";
    echo "- Relationship errors\n";
}

echo "\n=== Cache check ===\n";
$cacheKey = 'mobile_home_v2_';
echo "Checking for mobile_home cache keys...\n";

// Try to clear the specific cache
Cache::flush();
echo "✅ All cache cleared\n";

echo "\n=== DONE ===\n";
