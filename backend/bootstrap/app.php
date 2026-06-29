<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

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
            \App\Http\Middleware\UpdateAdminActivity::class,
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'update_admin_activity' => \App\Http\Middleware\UpdateAdminActivity::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('birthdays:send-coupons')->everyMinute();
        $schedule->command('cart:clean-expired')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();