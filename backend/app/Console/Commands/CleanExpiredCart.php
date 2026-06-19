<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GioHang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanExpiredCart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cart:clean-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean cart items older than 24 hours and return their quantity to stock.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting clean expired cart command...');
        
        // Find cart items older than 24 hours
        $expiredTime = now()->subHours(24);
        
        $expiredItems = GioHang::with('bienThe')
            ->where('created_at', '<', $expiredTime)
            ->get();

        if ($expiredItems->isEmpty()) {
            $this->info('No expired cart items found.');
            return self::SUCCESS;
        }

        $this->info('Found ' . $expiredItems->count() . ' expired cart items. Restoring stock...');

        DB::beginTransaction();
        try {
            $restoredCount = 0;
            foreach ($expiredItems as $item) {
                if ($item->bienThe) {
                    $item->bienThe->increment('soluong', $item->soluong);
                    $restoredCount++;
                }
                $item->delete();
            }

            DB::commit();
            $this->info("Successfully cleaned expired cart items. Restored stock for {$restoredCount} items.");
            Log::info("CleanExpiredCart command run successfully: cleaned {$expiredItems->count()} items, restored {$restoredCount} variant stocks.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error running CleanExpiredCart command: ' . $e->getMessage());
            Log::error('Error in CleanExpiredCart command: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
