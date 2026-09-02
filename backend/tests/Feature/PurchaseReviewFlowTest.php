<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateVideo;
use App\Models\BienThe;
use App\Models\DanhGia;
use App\Models\DanhMuc;
use App\Models\DatHang;
use App\Models\GioHang;
use App\Models\SanPham;
use App\Models\ThuongHieu;
use App\Models\User;
use App\Services\DemoShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private DanhMuc $category;

    private ThuongHieu $brand;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'null',
            'broadcasting.connections.null' => ['driver' => 'null'],
        ]);

        $this->customer = User::factory()->create([
            'name' => 'Le Ngoc Tai',
            'email' => 'lengoctai.flow@example.test',
        ]);

        $this->category = DanhMuc::create([
            'ten_danhmuc' => 'Laptop test',
            'trangthai' => 1,
        ]);

        $this->brand = ThuongHieu::create([
            'ten_thuonghieu' => 'NextGen Test',
            'trangthai' => 1,
        ]);

        Storage::put('admin/ai_status.json', 'true');
        Sanctum::actingAs($this->customer);
    }

    public function test_customer_can_checkout_successfully_then_review_with_negative_neutral_and_positive_feedback(): void
    {
        $cases = [
            [
                'sku' => 'FLOW-NEGATIVE',
                'product' => 'Laptop Gaming RTX 4060',
                'variant' => 'Laptop Gaming RTX 4060 16GB 512GB',
                'rating' => 1,
                'comment' => 'Hang vcl, may loi lien tuc va shop lua dao',
                'expected_status' => 'spam',
                'expects_ai_reply' => false,
            ],
            [
                'sku' => 'FLOW-NEUTRAL',
                'product' => 'MacBook Air M4',
                'variant' => 'MacBook Air M4 16GB 256GB',
                'rating' => 3,
                'comment' => 'May tam on, giao hang hoi lau va dong goi binh thuong.',
                'expected_status' => 'pending',
                'expects_ai_reply' => false,
            ],
            [
                'sku' => 'FLOW-POSITIVE',
                'product' => 'PC Workstation NextGen',
                'variant' => 'PC Workstation NextGen i7 32GB',
                'rating' => 5,
                'comment' => 'Tot ok muot nhanh.',
                'expected_status' => 'approved',
                'expects_ai_reply' => true,
            ],
        ];

        foreach ($cases as $case) {
            [$product, $variant] = $this->createSellableVariant(
                $case['product'],
                $case['variant'],
                $case['sku']
            );

            $order = $this->checkoutAndCompleteOrder($variant);

            $response = $this->postJson('/api/danh-gia', [
                'id_dathang' => $order->id_dathang,
                'id_bienthe' => $variant->id_bienthe,
                'danhgia' => $case['rating'],
                'binhluan' => $case['comment'],
            ]);

            $response->assertStatus(200)
                ->assertJson(['success' => true]);

            $review = DanhGia::where('id_dathang', $order->id_dathang)
                ->where('id_bienthe', $variant->id_bienthe)
                ->where('user_id', $this->customer->id)
                ->firstOrFail();

            $this->assertSame($case['expected_status'], $review->trangthai);
            $this->assertSame($case['rating'], (int) $review->danhgia);
            $this->assertStringContainsString($case['comment'], $review->binhluan);

            if ($case['expects_ai_reply']) {
                $this->assertStringContainsString('Cảm ơn', $review->binhluan);
                $this->assertStringNotContainsString('Trợ lý AI', $review->binhluan);

                $publicReviews = $this->getJson("/api/sanpham/{$product->id_sanpham}/reviews");
                $publicReviews->assertStatus(200)
                    ->assertJson(['success' => true])
                    ->assertJsonPath('reviews.0.id_danhgia', $review->id_danhgia);
            } else {
                $this->assertStringNotContainsString('Cảm ơn', $review->binhluan);

                $publicReviews = $this->getJson("/api/sanpham/{$product->id_sanpham}/reviews");
                $publicReviews->assertStatus(200)
                    ->assertJsonCount(0, 'reviews');
            }
        }
    }

    public function test_customer_cannot_review_before_order_is_completed(): void
    {
        [, $variant] = $this->createSellableVariant(
            'Laptop Office VinaBook',
            'Laptop Office VinaBook 8GB 256GB',
            'FLOW-PENDING'
        );

        $order = $this->checkoutOrder($variant);

        $response = $this->postJson('/api/danh-gia', [
            'id_dathang' => $order->id_dathang,
            'id_bienthe' => $variant->id_bienthe,
            'danhgia' => 4,
            'binhluan' => 'San pham on, se danh gia lai sau khi nhan hang.',
        ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('danhgia', [
            'id_dathang' => $order->id_dathang,
            'id_bienthe' => $variant->id_bienthe,
            'user_id' => $this->customer->id,
        ]);
    }

    public function test_checkout_rejects_unknown_payment_method(): void
    {
        $response = $this->postJson('/api/checkout', [
            'diachi' => '123 Nguyen Trai, Quan 1, TP HCM',
            'PTTT' => 'FREE_PAYMENT',
            'name' => 'Le Ngoc Tai',
            'phone' => '0909123456',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['PTTT']);
        $this->assertDatabaseCount('dathang', 0);
    }

    public function test_customer_cannot_create_multiple_active_cod_orders(): void
    {
        [, $firstVariant] = $this->createSellableVariant('Laptop COD 1', 'Laptop COD 1 16GB', 'COD-LIMIT-1');
        $this->checkoutOrder($firstVariant);

        [, $secondVariant] = $this->createSellableVariant('Laptop COD 2', 'Laptop COD 2 16GB', 'COD-LIMIT-2');
        $cartItem = GioHang::create([
            'id_khachhang' => $this->customer->id,
            'id_bienthe' => $secondVariant->id_bienthe,
            'soluong' => 1,
        ]);

        $response = $this->postJson('/api/checkout', [
            'diachi' => '123 Nguyen Trai, Quan 1, TP HCM',
            'PTTT' => 'COD',
            'name' => 'Le Ngoc Tai',
            'phone' => '0909123456',
            'selected_cart_items' => [$cartItem->id_giohang],
        ]);

        $response->assertStatus(409)->assertJson(['success' => false]);
        $this->assertSame(5, $secondVariant->fresh()->soluong);
    }

    public function test_order_history_still_loads_when_demo_shipment_sync_fails(): void
    {
        $shipmentService = \Mockery::mock(DemoShipmentService::class);
        $shipmentService->shouldReceive('syncDueShipments')
            ->with(false)
            ->once()
            ->andThrow(new \RuntimeException('Temporary demo shipment failure'));
        $this->app->instance(DemoShipmentService::class, $shipmentService);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'orders' => [],
            ]);
    }

    public function test_affiliate_video_checkout_creates_and_completes_commission(): void
    {
        $publisher = User::factory()->create();
        AffiliateProfile::create([
            'id_khachhang' => $publisher->id,
            'ma_affiliate' => 'VIDEOAFF',
            'ty_le_hoa_hong' => 5,
            'trangthai' => 'active',
        ]);

        [$product, $variant] = $this->createSellableVariant(
            'Laptop Affiliate', 'Laptop Affiliate 16GB', 'AFF-FLOW-1'
        );
        $video = AffiliateVideo::create([
            'id_affiliate_khachhang' => $publisher->id,
            'id_sanpham' => $product->id_sanpham,
            'tieu_de' => 'Video affiliate test',
            'video_url' => 'https://example.test/video.mp4',
            'trangthai' => 'approved',
            'duoc_duyet_luc' => now(),
        ]);

        Event::fake([OrderPlaced::class]);
        $cartItem = GioHang::create([
            'id_khachhang' => $this->customer->id,
            'id_bienthe' => $variant->id_bienthe,
            'soluong' => 1,
        ]);

        $response = $this->postJson('/api/checkout', [
            'diachi' => '123 Nguyen Trai, Quan 1, TP HCM',
            'PTTT' => 'COD',
            'name' => 'Le Ngoc Tai',
            'phone' => '0909123456',
            'selected_cart_items' => [$cartItem->id_giohang],
            'affiliate_video_id' => $video->id,
        ]);
        $this->assertSame(200, $response->status(), $response->getContent());
        $response->assertJson(['success' => true]);

        $order = DatHang::findOrFail($response->json('order.id_dathang'));
        $commission = AffiliateCommission::where('id_donhang', $order->id_dathang)->firstOrFail();
        $this->assertSame('pending', $commission->trangthai);
        $this->assertSame(1250000.0, (float) $commission->so_tien);

        $order->update(['trangthai' => 'done']);
        $this->assertSame('paid', $order->fresh()->trang_thai_thanh_toan);
        $this->assertSame('approved', $commission->fresh()->trangthai);

        $order->update(['trangthai' => 'refunded', 'trang_thai_thanh_toan' => 'refunded']);
        $this->assertSame('cancelled', $commission->fresh()->trangthai);
    }

    private function createSellableVariant(string $productName, string $variantName, string $sku): array
    {
        $product = new SanPham([
            'id_danhmuc' => $this->category->id_danhmuc,
            'id_thuonghieu' => $this->brand->id_thuonghieu,
            'tenSP' => $productName,
            'SKU' => $sku,
            'trangthai' => 'active',
        ]);
        $product->giaSP = 25000000;
        $product->save();

        $variant = BienThe::create([
            'id_sanpham' => $product->id_sanpham,
            'ten_bienthe' => $variantName,
            'gia' => 25000000,
            'soluong' => 5,
        ]);

        return [$product, $variant];
    }

    private function checkoutAndCompleteOrder(BienThe $variant): DatHang
    {
        $order = $this->checkoutOrder($variant);
        $order->update(['trangthai' => 'done']);

        return $order->fresh();
    }

    private function checkoutOrder(BienThe $variant): DatHang
    {
        Event::fake([OrderPlaced::class]);

        $cartItem = GioHang::create([
            'id_khachhang' => $this->customer->id,
            'id_bienthe' => $variant->id_bienthe,
            'soluong' => 1,
        ]);

        $response = $this->postJson('/api/checkout', [
            'diachi' => '123 Nguyen Trai, Quan 1, TP HCM',
            'PTTT' => 'COD',
            'name' => 'Le Ngoc Tai',
            'phone' => '0909123456',
            'selected_cart_items' => [$cartItem->id_giohang],
        ]);

        $this->assertSame(200, $response->status(), $response->getContent());

        $response->assertJson(['success' => true])
            ->assertJsonPath('order.trangthai', 'pending');

        $orderId = $response->json('order.id_dathang');

        $this->assertDatabaseHas('dathang', [
            'id_dathang' => $orderId,
            'id_khachhang' => $this->customer->id,
            'trangthai' => 'pending',
            'PTTT' => 'COD',
        ]);

        $this->assertDatabaseHas('dathang_chitiet', [
            'id_dathang' => $orderId,
            'id_bienthe' => $variant->id_bienthe,
            'soluong' => 1,
            'gia' => 25000000,
        ]);

        $this->assertDatabaseMissing('giohang', [
            'id_giohang' => $cartItem->id_giohang,
        ]);

        $this->assertDatabaseHas('bienthe', [
            'id_bienthe' => $variant->id_bienthe,
            'soluong' => 4,
        ]);

        return DatHang::findOrFail($orderId);
    }
}
