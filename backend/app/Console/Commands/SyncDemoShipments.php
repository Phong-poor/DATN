<?php

namespace App\Console\Commands;

use App\Services\DemoShipmentService;
use Illuminate\Console\Command;

class SyncDemoShipments extends Command
{
    protected $signature = 'orders:sync-demo-shipments';

    protected $description = 'Automatically advance demo order shipment statuses when their planned time is due.';

    public function handle(DemoShipmentService $service): int
    {
        $result = $service->syncDueShipments();

        $this->info(
            'Demo shipment sync completed. Checked: '
            .($result['checked'] ?? 0)
            .', Created: '
            .($result['created'] ?? 0)
            .', Updated: '
            .($result['updated'] ?? 0)
        );

        return self::SUCCESS;
    }
}
