<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    /**
     * POST /api/news-subscribe
     * Đăng ký nhận bản tin newsletter.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($request->email));

        $existing = NewsletterSubscriber::where('email', $email)->first();

        if ($existing) {
            if ($existing->active) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email này đã được đăng ký nhận bản tin.',
                ]);
            }

            // Đã unsubscribe trước đó → cho phép re-subscribe
            $existing->update([
                'active'           => true,
                'subscribed_at'    => now(),
                'unsubscribed_at'  => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bạn đã đăng ký lại nhận bản tin thành công!',
            ]);
        }

        NewsletterSubscriber::create([
            'email'         => $email,
            'active'        => true,
            'token'         => hash('sha256', $email),
            'subscribed_at' => now(),
        ]);

        // Gửi email chào mừng (queue hoặc sync)
        try {
            Mail::to($email)->send(new NewsletterWelcomeMail($email));
        } catch (\Throwable $e) {
            // Không chặn response nếu mail lỗi
            \Log::warning('Newsletter welcome mail failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => '🎉 Đăng ký thành công! Chúng tôi sẽ gửi thông tin mới nhất đến bạn.',
        ]);
    }

    /**
     * POST /api/news-unsubscribe
     * Hủy đăng ký bản tin.
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($request->email));
        $sub = NewsletterSubscriber::where('email', $email)->first();

        if (! $sub || ! $sub->active) {
            return response()->json([
                'success' => false,
                'message' => 'Email này không có trong danh sách nhận bản tin.',
            ], 404);
        }

        $sub->update([
            'active'           => false,
            'unsubscribed_at'  => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bạn đã hủy đăng ký nhận bản tin thành công.',
        ]);
    }

    /**
     * GET /api/admin/newsletter/subscribers
     * Admin xem danh sách subscriber.
     */
    public function list(Request $request)
    {
        $query = NewsletterSubscriber::orderByDesc('subscribed_at');

        if ($request->filled('status')) {
            $query->where('active', $request->status === 'active');
        }

        $subscribers = $query->paginate(50);

        return response()->json($subscribers);
    }
}
