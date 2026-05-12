<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::get('/test-mail', function () {
    Mail::raw('Test gửi mail từ Laravel', function ($message) {
        $message->to('tantaile175@gmail.com')
                ->subject('Test Gmail SMTP');
    });

    return 'Gửi mail thành công';
});

require __DIR__.'/settings.php';