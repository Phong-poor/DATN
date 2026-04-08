<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LienHe;
use Illuminate\Support\Facades\Mail;

class LienHeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:2000',
            'category' => 'nullable|string'
        ]);

        $contact = LienHe::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message,
            'category' => $request->category ?? 'Tư vấn',
            'status' => 'new'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Gửi liên hệ thành công',
            'data' => $contact
        ]);
    }

    public function index()
    {
        return response()->json(
            LienHe::latest()->get()
        );
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string|max:5000'
        ]);

        $contact = LienHe::findOrFail($id);

        Mail::send([], [], function ($mail) use ($contact, $request) {
            $mail->to($contact->email, $contact->name)
                ->subject('Phản hồi từ VinaTech - ' . $contact->name)
                ->html("
                    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
                        <div style='background:#4f46e5;padding:24px;border-radius:12px 12px 0 0'>
                            <h2 style='color:#fff;margin:0'>VinaTech Support</h2>
                        </div>
                        <div style='background:#f8faff;padding:24px;border:1px solid #e2e8f0'>
                            <p>Xin chào <strong>{$contact->name}</strong>,</p>
                            <p>Dưới đây là phản hồi:</p>
                            <div style='background:#fff;padding:16px;margin:16px 0'>
                                " . nl2br(e($request->reply)) . "
                            </div>
                        </div>
                    </div>
                ");
        });

        $contact->update([
            'reply'      => $request->reply,
            'status'     => 'replied',
            'replied_at' => now()
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Đã gửi phản hồi thành công'
        ]);
    }

    // ✅ HÀM DELETE ĐÚNG CHỖ
    public function destroy($id)
    {
        $contact = LienHe::find($id);

        if (!$contact) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy liên hệ'
            ], 404);
        }

        $contact->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa thành công'
        ]);
    }
}