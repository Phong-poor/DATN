<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckoutThrottle;
use App\Http\Middleware\UpdateAdminActivity;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            UpdateAdminActivity::class,
        ]);
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'update_admin_activity' => UpdateAdminActivity::class,
            'checkout.throttle' => CheckoutThrottle::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('birthdays:send-coupons')->everyMinute()->withoutOverlapping();
        $schedule->command('events:send-vouchers')
            ->everyMinute()
            ->timezone('Asia/Ho_Chi_Minh')
            ->withoutOverlapping();
        $schedule->command('orders:sync-demo-shipments')->everyMinute()->withoutOverlapping();
        $schedule->command('orders:expire-pending-payments')->everyMinute()->withoutOverlapping();
        $schedule->command('cart:clean-expired')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
