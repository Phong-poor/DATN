<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\NewsTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FreshNewsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            News::query()->delete();
            NewsTag::query()->delete();

            foreach ($this->articles() as $index => $data) {
                $tags = $data['tags'];
                unset($data['tags']);
                $news = News::create($data);
                $tagIds = collect($tags)->map(fn ($tag) => NewsTag::create(['name' => $tag, 'slug' => Str::slug($tag)])->id);
                $news->tags()->sync($tagIds);
            }
        });

        Cache::forever('news:version', (string) microtime(true));

        $this->command?->info(sprintf(
            'Đã tạo %d bài viết: %d đã xuất bản, %d bản nháp.',
            News::count(),
            News::where('trangthai', 'published')->count(),
            News::where('trangthai', 'draft')->count(),
        ));
    }

    private function articles(): array
    {
        $common = ['tacgia' => 'VinaTech Editorial', 'workflow_status' => 'published', 'no_index' => false, 'share_count' => 0, 'luotxem' => 0];

        return [
            array_merge($common, [
                'tieude' => 'Laptop cho sinh viên 2026: Chọn cấu hình nào để học tốt trong 4 năm?',
                'slug' => 'laptop-cho-sinh-vien-2026-chon-cau-hinh-hoc-tot-4-nam', 'danhmuc' => 'Tư vấn',
                'hinhanh' => 'uploads/news/covers/laptop-sinh-vien-2026.png', 'mota_hinhanh' => 'Sinh viên sử dụng laptop mỏng nhẹ trong thư viện',
                'trangthai' => 'published', 'dang_luc' => now()->subHours(2), 'noi_bat' => true, 'ghim' => true, 'reading_time' => 6,
                'tomtat' => 'Hướng dẫn chọn laptop cho sinh viên theo ngành học, ngân sách, thời lượng pin và khả năng sử dụng ổn định trong suốt bốn năm đại học.',
                'seo_title' => 'Laptop cho sinh viên 2026: Cấu hình dùng tốt 4 năm', 'seo_description' => 'Cách chọn laptop cho sinh viên 2026 theo ngành học, ngân sách, pin, RAM và khả năng nâng cấp để sử dụng ổn định trong 4 năm.',
                'seo_keywords' => 'laptop sinh viên 2026, laptop học tập, tư vấn laptop',
                'noidung' => "## Bắt đầu từ ngành học, không bắt đầu từ thương hiệu\nSinh viên kinh tế, ngôn ngữ hoặc luật chủ yếu cần trình duyệt, bộ công cụ văn phòng và họp trực tuyến. CPU Core i5 hoặc Ryzen 5, RAM 16GB và SSD 512GB là mức cân bằng. Sinh viên thiết kế, kiến trúc hay kỹ thuật nên ưu tiên CPU mạnh hơn, màn hình màu tốt và GPU rời phù hợp phần mềm chuyên ngành.\n\n## RAM 16GB nên là tiêu chuẩn\nKhối lượng tab trình duyệt, tài liệu và ứng dụng chạy đồng thời ngày càng lớn. RAM 16GB giúp máy ít bị chậm và đủ dư địa cho vài năm tiếp theo. Nếu máy hàn RAM, hãy chọn đúng dung lượng ngay từ đầu; nếu có khe nâng cấp, bạn có thể đầu tư theo từng giai đoạn.\n\n## Pin, trọng lượng và bàn phím quan trọng mỗi ngày\nMột chiếc máy mạnh nhưng nặng và nhanh hết pin sẽ gây bất tiện khi di chuyển giữa các lớp. Mức 1,2–1,6 kg, pin thực tế từ 7 giờ và sạc USB-C là lựa chọn dễ sống chung. Hãy thử bàn phím, touchpad và webcam vì đây là các bộ phận bạn sử dụng liên tục.\n\n## Ngân sách nên phân bổ thế nào?\nĐừng chi hết tiền cho CPU cao cấp rồi chấp nhận màn hình kém hoặc SSD quá nhỏ. Hãy dành ngân sách cho RAM 16GB, SSD 512GB, màn hình IPS dễ nhìn và chế độ bảo hành rõ ràng. Một cấu hình cân bằng thường bền giá trị hơn cấu hình lệch.\n\n## Checklist trước khi mua\nKiểm tra phần mềm ngành học, cân nặng cả bộ sạc, số cổng kết nối, khả năng nâng cấp và chính sách bảo hành. Nếu có thể, hãy dùng thử máy thật trước khi quyết định.",
                'tags' => ['Laptop sinh viên', 'Tư vấn mua laptop', 'Học tập'],
            ]),
            array_merge($common, [
                'tieude' => 'Laptop gaming 2026: Đừng chỉ nhìn tên card đồ họa',
                'slug' => 'laptop-gaming-2026-dung-chi-nhin-ten-card-do-hoa', 'danhmuc' => 'Gaming',
                'hinhanh' => 'uploads/news/covers/laptop-gaming-2026.png', 'mota_hinhanh' => 'Laptop gaming hiệu năng cao với bàn phím RGB trong không gian chơi game',
                'trangthai' => 'published', 'dang_luc' => now()->subDay(), 'noi_bat' => true, 'ghim' => false, 'reading_time' => 7,
                'tomtat' => 'TGP, tản nhiệt, MUX Switch và chất lượng màn hình mới là các thông số quyết định trải nghiệm gaming thực tế.',
                'seo_title' => 'Cách chọn laptop gaming 2026 đúng hiệu năng', 'seo_description' => 'Phân tích TGP, GPU, CPU, tản nhiệt, MUX Switch và màn hình khi chọn laptop gaming năm 2026.',
                'seo_keywords' => 'laptop gaming 2026, TGP laptop, GPU laptop',
                'noidung' => "## Cùng tên GPU chưa chắc cùng hiệu năng\nHai mẫu laptop dùng cùng GPU có thể chênh lệch hiệu năng vì mức điện TGP và khả năng tản nhiệt khác nhau. Hãy xem công suất GPU được nhà sản xuất công bố và kết quả benchmark dài hạn thay vì chỉ nhìn tên card.\n\n## Tản nhiệt quyết định hiệu năng ổn định\nGame nặng khiến CPU và GPU hoạt động liên tục. Hệ thống nhiều ống đồng, khe hút gió rộng và phần mềm điều khiển quạt tốt giúp máy duy trì xung nhịp. Một thân máy quá mỏng có thể phải đánh đổi nhiệt độ hoặc tiếng ồn.\n\n## MUX Switch và màn hình\nMUX Switch cho phép GPU xuất hình trực tiếp, cải thiện hiệu năng trong nhiều trò chơi. Màn hình nên có tối thiểu 144Hz, phản hồi tốt và độ phủ màu phù hợp. Người làm nội dung nên ưu tiên thêm độ chính xác màu.\n\n## Cấu hình cân bằng\nVới game phổ biến, CPU tầm trung hiện đại, RAM 16GB dual-channel và SSD 1TB là điểm khởi đầu hợp lý. Hãy ưu tiên GPU và tản nhiệt trước khi nâng CPU lên phiên bản đắt nhất.\n\n## Trải nghiệm trước khi mua\nKiểm tra độ ồn quạt, nhiệt độ khu vực bàn phím, chất lượng loa và trọng lượng bộ sạc. Đây là các yếu tố bảng thông số thường không thể hiện đầy đủ.",
                'tags' => ['Laptop gaming', 'GPU laptop', 'Tản nhiệt'],
            ]),
            array_merge($common, [
                'tieude' => '7 tiêu chí chọn laptop văn phòng mỏng nhẹ và bền pin',
                'slug' => '7-tieu-chi-chon-laptop-van-phong-mong-nhe-ben-pin', 'danhmuc' => 'Tư vấn',
                'hinhanh' => 'uploads/news/covers/laptop-van-phong-mong-nhe.png', 'mota_hinhanh' => 'Laptop văn phòng mỏng nhẹ trên bàn làm việc tối giản',
                'trangthai' => 'published', 'dang_luc' => now()->subDays(2), 'noi_bat' => false, 'ghim' => false, 'reading_time' => 5,
                'tomtat' => 'Từ pin, màn hình, bàn phím đến cổng kết nối: bảy tiêu chí thực tế giúp chọn laptop làm việc lâu dài.',
                'seo_title' => '7 tiêu chí chọn laptop văn phòng mỏng nhẹ', 'seo_description' => 'Kinh nghiệm chọn laptop văn phòng mỏng nhẹ, bền pin, bàn phím tốt và đủ cổng kết nối cho công việc hằng ngày.',
                'seo_keywords' => 'laptop văn phòng, laptop mỏng nhẹ, laptop pin lâu',
                'noidung' => "## 1. Trọng lượng thực tế\nMáy từ 1,2 đến 1,5 kg phù hợp với người di chuyển hằng ngày. Đừng quên cộng thêm trọng lượng bộ sạc và phụ kiện.\n\n## 2. Thời lượng pin\nHãy tham khảo bài đo pin thực tế với trình duyệt và họp trực tuyến. Con số quảng cáo thường được đo trong điều kiện rất nhẹ.\n\n## 3. Màn hình dễ nhìn\nTấm nền IPS hoặc OLED chất lượng tốt, độ sáng từ 300 nit và tỷ lệ 16:10 giúp đọc tài liệu thoải mái hơn. Lớp chống chói hữu ích trong văn phòng nhiều đèn.\n\n## 4. Bàn phím và touchpad\nHành trình phím hợp lý, layout rõ ràng và touchpad chính xác ảnh hưởng trực tiếp đến năng suất. Người nhập liệu nhiều nên trải nghiệm trước.\n\n## 5. Webcam và micro\nHọp trực tuyến đã trở thành tác vụ thường xuyên. Webcam rõ, micro lọc tiếng ồn và tính năng che camera là những điểm cộng thực dụng.\n\n## 6. Cổng kết nối\nUSB-C hỗ trợ sạc và xuất hình, ít nhất một USB-A và HDMI sẽ giảm nhu cầu mang hub chuyển đổi.\n\n## 7. Bảo hành và độ bền\nƯu tiên máy có chính sách bảo hành rõ ràng, linh kiện dễ thay và trung tâm dịch vụ thuận tiện. Đây là khoản bảo hiểm cho công việc dài hạn.",
                'tags' => ['Laptop văn phòng', 'Laptop mỏng nhẹ', 'Pin laptop'],
            ]),
            array_merge($common, [
                'tieude' => 'Laptop AI và NPU: Ai thực sự cần nâng cấp trong năm 2026?',
                'slug' => 'laptop-ai-npu-ai-can-nang-cap-2026', 'danhmuc' => 'Công nghệ',
                'hinhanh' => 'uploads/news/covers/laptop-ai-npu-2026.png', 'mota_hinhanh' => 'Laptop AI hiển thị mô hình mạng nơ-ron trong phòng nghiên cứu',
                'trangthai' => 'published', 'dang_luc' => now()->subDays(3), 'noi_bat' => true, 'ghim' => false, 'reading_time' => 6,
                'tomtat' => 'NPU giúp xử lý tác vụ AI tiết kiệm điện, nhưng không phải người dùng nào cũng cần đổi máy ngay.',
                'seo_title' => 'Laptop AI và NPU: Có nên nâng cấp năm 2026?', 'seo_description' => 'Giải thích NPU trên laptop AI, lợi ích thực tế, giới hạn và nhóm người dùng nên nâng cấp trong năm 2026.',
                'seo_keywords' => 'laptop AI, NPU laptop, AI PC 2026',
                'noidung' => "## NPU là gì?\nNPU là bộ xử lý được tối ưu cho các phép tính AI lặp lại với mức điện thấp. Nó có thể đảm nhiệm lọc tiếng ồn, làm mờ nền, nhận diện hình ảnh và một số tính năng trợ lý mà không cần đánh thức GPU mạnh.\n\n## Lợi ích rõ nhất nằm ở hiệu quả năng lượng\nCPU và GPU vẫn xử lý được AI, nhưng NPU phù hợp với tác vụ chạy liên tục. Khi phần mềm hỗ trợ đúng cách, webcam thông minh hoặc phiên âm có thể hoạt động mà ít ảnh hưởng đến pin.\n\n## NPU không thay thế GPU\nDựng 3D, huấn luyện mô hình hoặc chạy AI tạo sinh lớn vẫn cần GPU và bộ nhớ phù hợp. Không nên đánh giá sức mạnh toàn bộ máy chỉ bằng thông số TOPS của NPU.\n\n## Ai nên nâng cấp?\nNgười mua máy mới để dùng nhiều năm, thường xuyên họp trực tuyến hoặc muốn khai thác tính năng AI cục bộ nên cân nhắc. Nếu laptop hiện tại vẫn đáp ứng tốt công việc, chưa có ứng dụng AI cụ thể thì chưa cần đổi chỉ vì nhãn AI PC.\n\n## Cần xem gì ngoài NPU?\nRAM, SSD, màn hình, bàn phím và pin vẫn quyết định trải nghiệm mỗi ngày. Hãy xem NPU là một phần của cấu hình cân bằng, không phải lý do duy nhất để mua máy.",
                'tags' => ['Laptop AI', 'NPU', 'Công nghệ 2026'],
            ]),
            array_merge($common, [
                'tieude' => 'Vệ sinh và bảo dưỡng laptop đúng cách: 8 việc nên làm định kỳ',
                'slug' => 've-sinh-bao-duong-laptop-dung-cach-8-viec-dinh-ky', 'danhmuc' => 'Thủ thuật',
                'hinhanh' => 'uploads/news/covers/bao-duong-laptop.png', 'mota_hinhanh' => 'Kỹ thuật viên vệ sinh hệ thống tản nhiệt bên trong laptop',
                'trangthai' => 'published', 'dang_luc' => now()->subDays(4), 'noi_bat' => false, 'ghim' => false, 'reading_time' => 6,
                'tomtat' => 'Lịch vệ sinh, cập nhật phần mềm và chăm sóc pin giúp laptop ổn định, mát hơn và kéo dài tuổi thọ.',
                'seo_title' => '8 bước vệ sinh và bảo dưỡng laptop đúng cách', 'seo_description' => 'Hướng dẫn vệ sinh màn hình, bàn phím, khe tản nhiệt, chăm sóc pin và cập nhật laptop an toàn theo định kỳ.',
                'seo_keywords' => 'vệ sinh laptop, bảo dưỡng laptop, chăm sóc pin laptop',
                'noidung' => "## 1. Sao lưu dữ liệu quan trọng\nDuy trì ít nhất một bản sao ngoài máy hoặc trên dịch vụ đám mây. Sao lưu là bước bảo dưỡng quan trọng nhất nhưng thường bị bỏ quên.\n\n## 2. Cập nhật có kiểm soát\nCập nhật hệ điều hành, trình duyệt và phần mềm bảo mật. Với driver, ưu tiên nguồn chính thức và tránh công cụ cập nhật không rõ nguồn gốc.\n\n## 3. Làm sạch màn hình\nTắt máy, dùng khăn microfiber hơi ẩm và lau nhẹ. Không xịt dung dịch trực tiếp lên màn hình hoặc dùng chất tẩy mạnh.\n\n## 4. Vệ sinh bàn phím và cổng kết nối\nDùng chổi mềm hoặc khí nén ở khoảng cách phù hợp. Không đưa vật kim loại vào cổng kết nối.\n\n## 5. Giữ khe tản nhiệt thông thoáng\nKhông đặt laptop trên chăn, đệm hoặc bề mặt bịt khe hút gió. Nếu máy nóng và quạt ồn bất thường, hãy mang đến kỹ thuật viên.\n\n## 6. Chăm sóc pin\nTránh để máy ở nhiệt độ cao và hạn chế xả pin về 0% thường xuyên. Nếu phần mềm hãng có chế độ giới hạn sạc, hãy bật khi máy thường xuyên cắm điện.\n\n## 7. Dọn dung lượng SSD\nGiữ một phần dung lượng trống để hệ thống cập nhật và vận hành ổn định. Xóa file tạm nhưng kiểm tra kỹ trước khi xóa dữ liệu cá nhân.\n\n## 8. Bảo dưỡng chuyên sâu\nViệc tháo máy, vệ sinh quạt và thay keo tản nhiệt nên do người có kinh nghiệm thực hiện, đặc biệt khi thiết bị còn bảo hành.",
                'tags' => ['Vệ sinh laptop', 'Bảo dưỡng', 'Thủ thuật'],
            ]),
            array_merge($common, [
                'tieude' => 'Laptop sáng tạo nội dung 2026: Cấu hình cho dựng video và thiết kế',
                'slug' => 'laptop-sang-tao-noi-dung-2026-dung-video-thiet-ke', 'danhmuc' => 'Sản phẩm',
                'hinhanh' => 'uploads/news/covers/laptop-sang-tao-noi-dung.png', 'mota_hinhanh' => 'Nhà sáng tạo nội dung dựng video trên laptop và màn hình lớn',
                'trangthai' => 'draft', 'workflow_status' => 'draft', 'dang_luc' => null, 'noi_bat' => false, 'ghim' => false, 'reading_time' => 5,
                'tomtat' => 'Bản nháp phân tích CPU, GPU, RAM và màn hình phù hợp cho dựng video, chỉnh ảnh và thiết kế đồ họa.',
                'seo_title' => 'Laptop sáng tạo nội dung 2026: Cấu hình đề xuất', 'seo_description' => 'Cấu hình laptop cho dựng video và thiết kế năm 2026 với CPU, GPU, RAM, SSD và màn hình màu chính xác.',
                'seo_keywords' => 'laptop dựng video, laptop thiết kế, laptop creator',
                'noidung' => "## Xác định phần mềm và độ phân giải dự án\nDựng video Full HD, 4K hay thiết kế 3D có yêu cầu phần cứng rất khác nhau. Cần kiểm tra phần mềm chính hỗ trợ GPU nào trước khi chọn máy.\n\n## CPU, GPU và RAM\nCPU nhiều nhân giúp xuất video nhanh, GPU tăng tốc hiệu ứng và RAM 32GB phù hợp dự án 4K nhiều lớp. Nội dung phần này cần bổ sung benchmark thực tế trước khi xuất bản.\n\n## Màn hình và lưu trữ\nMàn hình cần độ phủ màu tốt, còn SSD nên có dung lượng tối thiểu 1TB. Cần bổ sung bảng so sánh các cấu hình theo ngân sách.",
                'tags' => ['Laptop creator', 'Dựng video', 'Thiết kế đồ họa'],
            ]),
        ];
    }
}
