<?php

namespace App\Console\Commands;

use App\Models\BienThe;
use App\Models\SanPham;
use Illuminate\Console\Command;

class FixImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix product images encoded as JSON array in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = SanPham::all();
        $count = 0;

        foreach ($products as $p) {
            if (str_starts_with($p->hinhanh, '["')) {
                $decoded = json_decode($p->hinhanh, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $p->hinhanh = $decoded[0];
                    $p->save();
                    $count++;
                }
            }
        }
        $this->info("Fixed images for $count products.");

        $variants = BienThe::all();
        $vCount = 0;
        foreach ($variants as $v) {
            if (empty($v->thuoc_tinh_json) || $v->thuoc_tinh_json === 'null' || $v->thuoc_tinh_json === '""') {
                $v->thuoc_tinh_json = '[]';
                $v->save();
                $vCount++;
            }
        }
        $this->info("Fixed attributes for $vCount variants.");
    }
}
