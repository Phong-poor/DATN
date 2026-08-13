use App\Models\SanPham;

$products = SanPham::all();
$count = 0;

foreach($products as $p) {
    if (str_starts_with($p->hinhanh, '["')) {
        $decoded = json_decode($p->hinhanh, true);
        if (is_array($decoded) && count($decoded) > 0) {
            $p->hinhanh = $decoded[0];
            $p->save();
            $count++;
        }
    }
}
echo "Fixed images for $count products.\n";
