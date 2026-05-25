<?php

use App\Models\News;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Features;

Route::get('/sitemap.xml', function () {
    $baseUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/');
    $urls = [
        ['loc' => "{$baseUrl}/", 'priority' => '1.0', 'changefreq' => 'daily'],
        ['loc' => "{$baseUrl}/products", 'priority' => '0.9', 'changefreq' => 'daily'],
        ['loc' => "{$baseUrl}/news", 'priority' => '0.9', 'changefreq' => 'daily'],
        ['loc' => "{$baseUrl}/contact", 'priority' => '0.6', 'changefreq' => 'monthly'],
    ];

    News::where('status', 'published')
        ->where(function ($query) {
            $query->whereNull('published_at')->orWhere('published_at', '<=', now());
        })
        ->orderByDesc('published_at')
        ->get(['id', 'updated_at', 'published_at'])
        ->each(function (News $post) use (&$urls, $baseUrl) {
            $urls[] = [
                'loc' => "{$baseUrl}/news/{$post->id}",
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => optional($post->updated_at ?? $post->published_at)->toDateString(),
            ];
        });

    $xml = collect($urls)
        ->map(function ($url) {
            $lastmod = empty($url['lastmod']) ? '' : "\n    <lastmod>{$url['lastmod']}</lastmod>";
            return "  <url>\n    <loc>" . e($url['loc']) . "</loc>{$lastmod}\n    <changefreq>{$url['changefreq']}</changefreq>\n    <priority>{$url['priority']}</priority>\n  </url>";
        })
        ->prepend('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
        ->push('</urlset>')
        ->implode("\n");

    return response($xml, 200)->header('Content-Type', 'application/xml');
});

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