<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vai_tro', function (Blueprint $table) {
            $table->id('id_vaitro');
            $table->string('ten_vaitro');
            $table->string('ma_vaitro')->unique();
            $table->text('mo_ta')->nullable();
            $table->json('quyen')->nullable();
            $table->timestamps();
        });

        // Định nghĩa các danh sách quyền cho các vai trò mặc định
        $quyenAdmin = [
            'san_pham_xem', 'san_pham_sua', 'nhap_xuat_kho', 
            'danh_muc_xem', 'danh_muc_sua', 
            'thuong_hieu_xem', 'thuong_hieu_sua', 
            'bien_the_xem', 'bien_the_sua', 
            'don_hang_xem', 'don_hang_sua', 'hoa_don_xem', 
            'marketing_quan_ly', 'affiliate_quan_ly', 
            'tin_tuc_quan_ly', 'binh_luan_quan_ly', 'banner_quan_ly', 
            'lien_he_quan_ly', 'tai_khoan_quan_ly', 'vai_tro_quan_ly', 'nhat_ky_quan_ly'
        ];

        $quyenInventory = [
            'san_pham_xem', 'nhap_xuat_kho', 'danh_muc_xem', 'thuong_hieu_xem', 'bien_the_xem'
        ];

        $quyenOrderManager = [
            'don_hang_xem', 'don_hang_sua'
        ];

        $quyenMarketing = [
            'marketing_quan_ly'
        ];

        $quyenAffiliate = [
            'affiliate_quan_ly'
        ];

        $quyenEditor = [
            'tin_tuc_quan_ly', 'binh_luan_quan_ly', 'banner_quan_ly'
        ];

        $quyenSupport = [
            'lien_he_quan_ly'
        ];

        $quyenAccountant = [
            'don_hang_xem', 'hoa_don_xem'
        ];

        // Seed default roles
        DB::table('vai_tro')->insert([
            [
                'ten_vaitro' => 'Quản trị viên',
                'ma_vaitro' => 'admin',
                'mo_ta' => 'Quản trị viên tối cao của hệ thống, toàn quyền truy cập.',
                'quyen' => json_encode($quyenAdmin),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_vaitro' => 'Thủ kho',
                'ma_vaitro' => 'inventory',
                'mo_ta' => 'Theo dõi danh mục, xem sản phẩm và thực hiện nhập/xuất kho.',
                'quyen' => json_encode($quyenInventory),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_vaitro' => 'Xử lý đơn hàng',
                'ma_vaitro' => 'order_manager',
                'mo_ta' => 'Xem danh sách đơn hàng và duyệt/cập nhật trạng thái đơn hàng.',
                'quyen' => json_encode($quyenOrderManager),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_vaitro' => 'Marketing',
                'ma_vaitro' => 'marketing',
                'mo_ta' => 'Quản lý các sự kiện khuyến mãi, combo sản phẩm, gửi mã coupon sinh nhật.',
                'quyen' => json_encode($quyenMarketing),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_vaitro' => 'Quản lý Affiliate',
                'ma_vaitro' => 'affiliate_manager',
                'mo_ta' => 'Quản lý tiếp thị liên kết, phê duyệt yêu cầu rút tiền của đối tác.',
                'quyen' => json_encode($quyenAffiliate),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_vaitro' => 'Biên tập viên',
                'ma_vaitro' => 'editor',
                'mo_ta' => 'Quản lý bài viết tin tức, kiểm duyệt bình luận và cập nhật banner quảng cáo.',
                'quyen' => json_encode($quyenEditor),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_vaitro' => 'Tư vấn viên',
                'ma_vaitro' => 'support',
                'mo_ta' => 'Tiếp nhận các thông tin liên hệ và hỗ trợ giải đáp thắc mắc khách hàng.',
                'quyen' => json_encode($quyenSupport),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ten_vaitro' => 'Kế toán',
                'ma_vaitro' => 'accountant',
                'mo_ta' => 'Kiểm tra hóa đơn và xem báo cáo thống kê doanh thu.',
                'quyen' => json_encode($quyenAccountant),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vai_tro');
    }
};
