<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LienHe;
use Illuminate\Support\Facades\Mail;

/**
 * Tiếp nhận liên hệ, yêu cầu tư vấn, lịch hẹn showroom và phản hồi khách hàng.
 */
class LienHeController extends Controller
{
    public function storeConsultation(Request $request)
    {
        $validated = $request->validate([
            'sodienthoai' => ['required', 'string', 'max:20', 'regex:/^(0|\+84)[0-9\s.-]{8,14}$/'],
        ], [
            'sodienthoai.required' => 'Vui lòng nhập số điện thoại.',
            'sodienthoai.regex' => 'Số điện thoại không đúng định dạng.',
        ]);

        $consultation = LienHe::create([
            'hoten' => 'Khách hàng đăng ký tư vấn',
            'email' => 'support@nextgenlaptop.vn',
            'sodienthoai' => $validated['sodienthoai'],
            'noidung' => 'Khách hàng yêu cầu chuyên viên gọi lại để tư vấn.',
            'danhmuc' => 'Đặt lịch tư vấn',
            'trangthai' => 'new',
            'loai_yeu_cau' => 'consultation_callback',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đăng ký tư vấn thành công.',
            'data' => $consultation,
        ], 201);
    }

    public function storeAppointment(Request $request)
    {
        $validated = $request->validate([
            'hoten' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'sodienthoai' => ['required', 'string', 'max:20', 'regex:/^(0|\\+84)[0-9\\s.-]{8,14}$/'],
            'showroom_id' => 'required|integer|min:1',
            'showroom_ten' => 'required|string|max:150',
            'showroom_diachi' => 'required|string|max:255',
            'ngay_hen' => 'required|date|after_or_equal:today',
            'khung_gio' => 'required|in:08:00 - 10:00,10:00 - 12:00,13:30 - 15:30,15:30 - 17:30',
            'ghi_chu' => 'nullable|string|max:1000',
        ], [
            'sodienthoai.regex' => 'Số điện thoại không đúng định dạng.',
            'ngay_hen.after_or_equal' => 'Ngày trải nghiệm không được ở trong quá khứ.',
            'khung_gio.in' => 'Khung giờ trải nghiệm không hợp lệ.',
        ]);

        $content = implode("\n", array_filter([
            "Showroom: {$validated['showroom_ten']}",
            "Địa chỉ: {$validated['showroom_diachi']}",
            "Ngày hẹn: {$validated['ngay_hen']}",
            "Khung giờ: {$validated['khung_gio']}",
            !empty($validated['ghi_chu']) ? "Nhu cầu: {$validated['ghi_chu']}" : null,
        ]));

        $appointment = LienHe::create([
            'hoten' => $validated['hoten'],
            'email' => $validated['email'],
            'sodienthoai' => $validated['sodienthoai'],
            'noidung' => $content,
            'danhmuc' => 'Đặt lịch trải nghiệm showroom',
            'trangthai' => 'new',
            'loai_yeu_cau' => 'showroom_appointment',
            'showroom_id' => $validated['showroom_id'],
            'showroom_ten' => $validated['showroom_ten'],
            'showroom_diachi' => $validated['showroom_diachi'],
            'ngay_hen' => $validated['ngay_hen'],
            'khung_gio' => $validated['khung_gio'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đăng ký lịch trải nghiệm thành công.',
            'data' => $appointment,
        ], 201);
    }

    public function store(Request $request)
    {
        $payload = [
            'hoten' => $request->input('hoten', $request->input('name')),
            'email' => $request->input('email'),
            'sodienthoai' => $request->input('sodienthoai', $request->input('phone')),
            'noidung' => $request->input('noidung', $request->input('message')),
            'danhmuc' => $request->input('danhmuc', $request->input('subject')),
        ];

        validator($payload, [
            'hoten' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'sodienthoai' => 'nullable|string|max:20',
            'noidung' => 'required|string|max:2000',
            'danhmuc' => 'nullable|string'
        ])->validate();

        $contact = LienHe::create([
            'hoten' => $payload['hoten'],
            'email' => $payload['email'],
            'sodienthoai' => $payload['sodienthoai'],
            'noidung' => $payload['noidung'],
            'danhmuc' => $request->danhmuc ?? 'Tư vấn',
            'trangthai' => 'new'
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
            'phanhoi' => 'required|string|max:5000'
        ]);

        $contact = LienHe::findOrFail($id);

            Mail::send([], [], function ($mail) use ($contact, $request) {
                $mail->to($contact->email, $contact->hoten)
                    ->subject('💻 Predator | Phản hồi liên hệ #' . $contact->id)
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
                    <h1 style='margin:0;font-size:26px'>💻 Predator</h1>
                    <p style='margin-top:8px;font-size:13px;opacity:0.9'>
                        Công nghệ dẫn đầu – Phục vụ tận tâm
                    </p>
                </div>

                <!-- BODY -->
                <div style='padding:28px'>

                    <h2 style='margin:0 0 10px;color:#111827;font-size:18px'>
                        Xin chào {$contact->hoten} 👋
                    </h2>

                    <p style='color:#374151;font-size:14px;line-height:1.6'>
                        Cảm ơn bạn đã liên hệ với <strong>Predator</strong>.
                        Đội ngũ của chúng tôi đã tiếp nhận và phản hồi như sau:
                    </p>

                    <!-- RESPONSE BOX -->
                    <div style='margin:20px 0;padding:18px;border-radius:12px;
                                background:linear-gradient(135deg,#eef2ff,#f0f9ff);
                                border:1px solid #e0e7ff'>

                        <p style='margin:0;color:#111827;font-size:14px;line-height:1.6'>
                            " . nl2br(e($request->phanhoi)) . "
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
                        🚀 Truy cập Predator
                        </a>
                    </div>

                    <p style='font-size:13px;color:#6b7280;text-align:center'>
                        Nếu bạn cần hỗ trợ thêm, hãy phản hồi lại email này nhé!
                    </p>

                </div>

                <!-- FOOTER -->
                <div style='background:#0f172a;color:#94a3b8;text-align:center;padding:18px;font-size:12px'>
                    <p style='margin:0'>© 2026 Predator</p>
                    <p style='margin:5px 0'>📞 Hotline: 1900 8888</p>
                    <p style='margin:0'>📧 support@predator.vn</p>
                </div>

            </div>

            </body>
            </html>
            ");
            });

        $contact->update([
            'phanhoi'      => $request->phanhoi,
            'trangthai'     => 'replied',
            'phan_hoi_luc' => now()
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
