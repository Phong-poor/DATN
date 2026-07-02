use App\Models\BienThe;

$variants = BienThe::all();
$count = 0;

foreach($variants as $v) {
    if (empty($v->thuoc_tinh_json) || $v->thuoc_tinh_json === 'null') {
        $v->thuoc_tinh_json = '[]';
        $v->save();
        $count++;
    }
}
echo "Fixed attributes for $count variants.\n";
