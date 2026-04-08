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
                    ->subject('💻 NextGen Laptop | Phản hồi liên hệ #' . $contact->id)
                    ->html("
            <!DOCTYPE html>
            <html>
            <head>
            <meta charset='UTF-8'>
            </head>

            <body style='margin:0;padding:0;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif'>

            <div style='max-width:640px;margin:30px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.08)'>

                <!-- HEADER -->
                <div style='background:linear-gradient(135deg,#6366f1,#2563eb,#06b6d4);padding:30px;text-align:center;color:white'>
                    <h1 style='margin:0;font-size:26px'>💻 NextGen Laptop</h1>
                    <p style='margin-top:8px;font-size:13px;opacity:0.9'>
                        Công nghệ dẫn đầu – Phục vụ tận tâm
                    </p>
                </div>

                <!-- BODY -->
                <div style='padding:28px'>

                    <h2 style='margin:0 0 10px;color:#111827;font-size:18px'>
                        Xin chào {$contact->name} 👋
                    </h2>

                    <p style='color:#374151;font-size:14px;line-height:1.6'>
                        Cảm ơn bạn đã liên hệ với <strong>NextGen Laptop</strong>. 
                        Đội ngũ của chúng tôi đã tiếp nhận và phản hồi như sau:
                    </p>

                    <!-- RESPONSE BOX -->
                    <div style='margin:20px 0;padding:18px;border-radius:12px;
                                background:linear-gradient(135deg,#eef2ff,#f0f9ff);
                                border:1px solid #e0e7ff'>

                        <p style='margin:0;color:#111827;font-size:14px;line-height:1.6'>
                            " . nl2br(e($request->reply)) . "
                        </p>
                    </div>

                    <!-- CTA -->
                    <div style='text-align:center;margin:30px 0'>
                        <a href='#'
                        style='display:inline-block;
                        padding:12px 22px;
                        background:linear-gradient(135deg,#2563eb,#4f46e5);
                        color:#fff;
                        border-radius:999px;
                        text-decoration:none;
                        font-size:14px;
                        font-weight:600;
                        box-shadow:0 6px 18px rgba(37,99,235,0.4)'>
                        🚀 Truy cập NextGen Laptop
                        </a>
                    </div>

                    <p style='font-size:13px;color:#6b7280;text-align:center'>
                        Nếu bạn cần hỗ trợ thêm, hãy phản hồi lại email này nhé!
                    </p>

                </div>

                <!-- FOOTER -->
                <div style='background:#0f172a;color:#94a3b8;text-align:center;padding:18px;font-size:12px'>
                    <p style='margin:0'>© 2026 NextGen Laptop</p>
                    <p style='margin:5px 0'>📞 Hotline: 1900 8888</p>
                    <p style='margin:0'>📧 support@nextgen.vn</p>
                </div>

            </div>

            </body>
            </html>
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