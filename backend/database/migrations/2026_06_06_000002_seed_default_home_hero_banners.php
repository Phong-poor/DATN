<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $findProductId = function (string $name): ?int {
            $id = DB::table('sanpham')
                ->where('tenSP', 'like', '%' . $name . '%')
                ->value('id_sanpham');

            return $id ? (int) $id : null;
        };

        $heroProducts = [
            'tuf' => $findProductId('ASUS TUF Gaming') ?: $findProductId('Laptop ASUS TUF') ?: $findProductId('Laptop Gaming'),
            'macbook' => $findProductId('MacBook Pro') ?: $findProductId('MacBook'),
            'lenovo' => $findProductId('Lenovo Gaming') ?: $findProductId('Laptop Gaming'),
            'vivobook' => $findProductId('Vivobook') ?: $findProductId('Laptop ASUS'),
        ];

        $defaults = [
            [
                'title' => 'Sức Mạnh Hội Tụ',
                'subtitle' => 'Sự Tinh Tế Chuyên Sâu',
                'eyebrow' => 'PREMIUM LAPTOP STORE 2026',
                'highlight' => 'Sự Tinh Tế Chuyên Sâu',
                'description' => 'Laptop cao cấp chế tác riêng cho nhà sáng tạo, game thủ chuyên nghiệp và kỹ sư công nghệ. Trải nghiệm hiệu năng vượt giới hạn vật lý với màn hình OLED đỉnh cao.',
                'image' => '/Gemini_Generated_Image_v5vppjv5vppjv5vp (1).png',
                'link_url' => '/products',
                'position' => 0,
                'primary_label' => 'Mua ngay',
                'secondary_label' => 'Xem bộ sưu tập',
                'product_badge' => 'TRENDING NOW',
                'product_feature' => 'RTX 40-Series',
                'product_id' => $heroProducts['tuf'],
            ],
            [
                'title' => 'Hiệu Năng Vượt Trội',
                'subtitle' => 'Kiến Trúc AI Thế Hệ Mới',
                'eyebrow' => 'NEW GENERATION CHIPS',
                'highlight' => 'Kiến Trúc AI Thế Hệ Mới',
                'description' => 'Sở hữu ngay các cỗ máy tối tân trang bị NPU tăng tốc AI cục bộ đến 45 TOPs. Đáp ứng hoàn hảo mọi tác vụ deep learning và dựng hình 3D real-time.',
                'image' => '/Gemini_Generated_Image_7xfvdr7xfvdr7xfv.png',
                'link_url' => '/products',
                'position' => 1,
                'primary_label' => 'Khám phá ngay',
                'secondary_label' => 'Tư vấn cấu hình',
                'product_badge' => 'AI READY',
                'product_feature' => 'NPU 45 TOPs',
                'product_id' => $heroProducts['macbook'],
            ],
            [
                'title' => 'Trải Nghiệm Đắm Chìm',
                'subtitle' => 'Nebula OLED 240Hz',
                'eyebrow' => 'NEBULA DISPLAY TECHNOLOGY',
                'highlight' => 'Nebula OLED 240Hz',
                'description' => 'Độ sâu màu 10-bit đích thực, độ tương phản tuyệt đối 1.000.000:1 cùng tần số quét 240Hz siêu mượt. Sắc sảo trong từng chuyển động game AAA.',
                'image' => '/Gemini_Generated_Image_j1cibhj1cibhj1ci.png',
                'link_url' => '/products',
                'position' => 2,
                'primary_label' => 'Xem ưu đãi',
                'secondary_label' => 'So sánh sản phẩm',
                'product_badge' => 'TRENDING NOW',
                'product_feature' => 'RTX 40-Series',
                'product_id' => $heroProducts['lenovo'],
            ],
            [
                'title' => 'Trải Nghiệm Đắm Chìm',
                'subtitle' => 'Không Gian Cao Cấp',
                'eyebrow' => 'PREDATOR SHOWROOM',
                'highlight' => 'Không Gian Cao Cấp',
                'description' => 'Khám phá không gian laptop hiện đại với các dòng máy cao cấp được trưng bày thực tế cho game, sáng tạo và công việc chuyên nghiệp.',
                'image' => '/Gemini_Generated_Image_dp15ytdp15ytdp15.png',
                'link_url' => '/contact',
                'position' => 3,
                'primary_label' => 'Xem showroom',
                'secondary_label' => 'Liên hệ tư vấn',
                'product_badge' => 'SHOWROOM',
                'product_feature' => 'Trải nghiệm trực tiếp',
                'product_id' => $heroProducts['vivobook'],
            ],
        ];

        foreach ($defaults as $banner) {
            $exists = DB::table('banners')
                ->where('image', $banner['image'])
                ->exists();

            if (!$exists) {
                $productId = $banner['product_id'] ?? null;

                DB::table('banners')->insert(array_merge($banner, [
                    'media_type' => 'image',
                    'mobile_image' => null,
                    'mobile_media_type' => null,
                    'product_id' => $productId,
                    'link_url' => $productId ? '/products/' . $productId : $banner['link_url'],
                    'is_active' => true,
                    'starts_at' => null,
                    'ends_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('banners')
            ->whereIn('image', [
                '/Gemini_Generated_Image_v5vppjv5vppjv5vp (1).png',
                '/Gemini_Generated_Image_7xfvdr7xfvdr7xfv.png',
                '/Gemini_Generated_Image_j1cibhj1cibhj1ci.png',
                '/Gemini_Generated_Image_dp15ytdp15ytdp15.png',
            ])
            ->delete();
    }
};
