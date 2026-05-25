<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DanhMuc;
use App\Models\ThuongHieu;
use App\Models\SanPham;
use App\Models\BienThe;
use App\Models\DatHang;
use App\Models\DatHangChiTiet;
use App\Models\DanhGia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DanhGiaTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $danhmuc;
    private $thuonghieu;
    private $sanpham;
    private $bienthe;
    private $order;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Tạo dữ liệu nền
        $this->user = User::factory()->create();

        $this->danhmuc = DanhMuc::create([
            'ten_danhmuc' => 'Điện thoại',
            'trangthai' => 1
        ]);

        $this->thuonghieu = ThuongHieu::create([
            'ten_thuonghieu' => 'Apple',
            'trangthai' => 1
        ]);

        $this->sanpham = new SanPham([
            'id_danhmuc' => $this->danhmuc->id_danhmuc,
            'id_thuonghieu' => $this->thuonghieu->id_thuonghieu,
            'tenSP' => 'iPhone 15 Pro Max',
            'trangthai' => 'active',
            'SKU' => 'IPHONE15PM'
        ]);
        $this->sanpham->giaSP = 30000000;
        $this->sanpham->save();

        $this->bienthe = BienThe::create([
            'id_sanpham' => $this->sanpham->id_sanpham,
            'ten_bienthe' => 'iPhone 15 Pro Max 256GB Gold',
            'gia' => 30000000,
            'soluong' => 10
        ]);

        // 2. Tạo đơn hàng hoàn thành (đã hoàn tất)
        $this->order = DatHang::create([
            'user_id' => $this->user->id,
            'tongtien' => 30000000,
            'trangthai' => 'done',
            'diachi' => 'Hà Nội, Việt Nam',
            'PTTT' => 'COD'
        ]);

        // Chi tiết đơn hàng chứa biến thể
        DatHangChiTiet::create([
            'id_dathang' => $this->order->id_dathang,
            'id_bienthe' => $this->bienthe->id_bienthe,
            'soluong' => 1,
            'gia' => 30000000
        ]);

        // Kích hoạt AI Smart Reply cho bộ test mặc định
        Storage::put('admin/ai_status.json', 'true');
    }

    /**
     * Test case A: Đánh giá tích cực (rating >= 4 hoặc chứa từ khóa tích cực)
     * Hệ thống tự động duyệt (approved) và trợ lý AI trả lời cảm ơn.
     */
    public function test_positive_comment_is_automatically_approved_and_replied_by_ai()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/danh-gia', [
            'id_dathang' => $this->order->id_dathang,
            'id_bienthe' => $this->bienthe->id_bienthe,
            'danhgia' => 5,
            'binhluan' => 'Sản phẩm dùng cực kỳ tuyệt vời và mượt mà, dịch vụ rất nhiệt tình!'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true
                 ]);

        // Kiểm tra db xem đánh giá có được tạo với trạng thái approved không
        $danhGia = DanhGia::first();
        $this->assertNotNull($danhGia);
        $this->assertEquals('approved', $danhGia->trangthai);
        $this->assertEquals(5, $danhGia->danhgia);
        
        // Kiểm tra xem bình luận đã được đính kèm câu trả lời của Trợ lý AI
        $this->assertStringContainsString('Trợ lý AI VinaTech', $danhGia->binhluan);
        $this->assertStringContainsString('Sản phẩm dùng cực kỳ tuyệt vời và mượt mà', $danhGia->binhluan);
    }

    /**
     * Test case B: Đánh giá chứa ngôn từ chửi thục, thô tục mất kiểm soát
     * Hệ thống tự động lọc, ẩn (trangthai = spam) để không hiện lên giao diện.
     */
    public function test_profane_comment_is_automatically_marked_as_spam_and_hidden()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/danh-gia', [
            'id_dathang' => $this->order->id_dathang,
            'id_bienthe' => $this->bienthe->id_bienthe,
            'danhgia' => 1,
            'binhluan' => 'Hàng vcl đéo chạy được, làm ăn như lồn'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true
                 ]);

        $danhGia = DanhGia::first();
        $this->assertNotNull($danhGia);
        // Trạng thái tự động ẩn (spam)
        $this->assertEquals('spam', $danhGia->trangthai);
        
        // Không được đính kèm phản hồi cảm ơn của Trợ lý AI
        $this->assertStringNotContainsString('Trợ lý AI VinaTech', $danhGia->binhluan);
    }

    /**
     * Test case C: Đánh giá trung tính/bình thường (rating thấp nhưng không chửi bậy)
     * Trạng thái mặc định chờ duyệt (pending) của admin, không tự động duyệt/phản hồi.
     */
    public function test_neutral_comment_remains_pending_without_ai_reply()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/danh-gia', [
            'id_dathang' => $this->order->id_dathang,
            'id_bienthe' => $this->bienthe->id_bienthe,
            'danhgia' => 3,
            'binhluan' => 'Sản phẩm tạm ổn, thời gian giao hàng hơi lâu một chút.'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true
                 ]);

        $danhGia = DanhGia::first();
        $this->assertNotNull($danhGia);
        $this->assertEquals('pending', $danhGia->trangthai);
        
        // Không được đính kèm phản hồi cảm ơn của Trợ lý AI
        $this->assertStringNotContainsString('Trợ lý AI VinaTech', $danhGia->binhluan);
    }

    /**
     * Test case D: Đánh giá tích cực nhưng Trợ lý AI Smart Reply bị TẮT (disabled).
     * Bình luận không được tự động duyệt (trangthai = pending) và không có phản hồi AI.
     */
    public function test_positive_comment_remains_pending_when_ai_smart_reply_is_disabled()
    {
        // Tắt Trợ lý AI
        Storage::put('admin/ai_status.json', 'false');

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/danh-gia', [
            'id_dathang' => $this->order->id_dathang,
            'id_bienthe' => $this->bienthe->id_bienthe,
            'danhgia' => 5,
            'binhluan' => 'Sản phẩm dùng cực kỳ tuyệt vời và mượt mà, dịch vụ rất nhiệt tình!'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true
                 ]);

        $danhGia = DanhGia::first();
        $this->assertNotNull($danhGia);
        // Trạng thái giữ nguyên là pending (chờ duyệt)
        $this->assertEquals('pending', $danhGia->trangthai);
        
        // Không được đính kèm phản hồi cảm ơn của Trợ lý AI
        $this->assertStringNotContainsString('Trợ lý AI VinaTech', $danhGia->binhluan);
    }

    /**
     * Test case E: Kiểm tra endpoints lấy và cập nhật trạng thái kích hoạt AI.
     */
    public function test_toggle_ai_status_endpoints()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // 1. Tải trạng thái ban đầu
        $response = $this->getJson('/api/admin/reviews/ai-status');
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true
                 ]);

        // 2. Tắt trạng thái AI
        $response = $this->postJson('/api/admin/reviews/ai-status', ['active' => false]);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'active' => false
                 ]);
        $this->assertEquals('false', Storage::get('admin/ai_status.json'));

        // 3. Bật lại trạng thái AI
        $response = $this->postJson('/api/admin/reviews/ai-status', ['active' => true]);
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'active' => true
                 ]);
        $this->assertEquals('true', Storage::get('admin/ai_status.json'));
    }
}
