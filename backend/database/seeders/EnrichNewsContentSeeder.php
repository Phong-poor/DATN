<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class EnrichNewsContentSeeder extends Seeder
{
    public function run(): void
    {
        $updated = 0;

        foreach ($this->extensions() as $slug => $extension) {
            $article = News::where('slug', $slug)->first();
            if (! $article) {
                continue;
            }

            if (! str_contains((string) $article->noidung, $extension['marker'])) {
                $article->noidung = rtrim((string) $article->noidung)."\n\n".$extension['content'];
                $article->reading_time = max(1, (int) ceil($this->wordCount($article->noidung) / 200));
                $article->save();
                $updated++;
            }

            $wordCount = $this->wordCount($article->noidung);
            $imageCount = $this->imageCount($article->noidung);
            if ($wordCount < 600 || $imageCount < 2) {
                throw new \RuntimeException("Bài {$slug} chưa đạt chuẩn: {$wordCount} từ, {$imageCount} ảnh.");
            }
        }

        Cache::forever('news:version', (string) microtime(true));
        $this->command?->info("Đã bổ sung nội dung và ảnh cho {$updated} bài viết, không xóa dữ liệu cũ.");
    }

    private function wordCount(?string $content): int
    {
        $plainText = preg_replace('/!\[[^\]]*\]\([^)]+\)/u', ' ', (string) $content);
        preg_match_all('/[\p{L}\p{N}]+/u', $plainText ?: '', $matches);

        return count($matches[0]);
    }

    private function imageCount(?string $content): int
    {
        preg_match_all('/!\[[^\]]*\]\([^)]+\)/u', (string) $content, $matches);

        return count($matches[0]);
    }

    private function extensions(): array
    {
        return [
            'laptop-cho-sinh-vien-2026-chon-cau-hinh-hoc-tot-4-nam' => [
                'marker' => '## Ba kịch bản cấu hình theo nhu cầu học tập',
                'content' => <<<'MARKDOWN'
![Không gian học tập với laptop mỏng nhẹ dành cho sinh viên](uploads/news/covers/laptop-van-phong-mong-nhe.png)

## Ba kịch bản cấu hình theo nhu cầu học tập
Với nhóm ngành kinh tế, ngôn ngữ, sư phạm và luật, cấu hình Core i5 hoặc Ryzen 5, RAM 16GB, SSD 512GB thường đủ để xử lý tài liệu, bảng tính, học trực tuyến và hàng chục tab trình duyệt. Màn hình 14 inch tỷ lệ 16:10 giúp đọc tài liệu thoải mái, trong khi khối lượng dưới 1,5 kg giảm đáng kể áp lực khi phải mang máy cả ngày. Một GPU rời đắt tiền không tạo ra khác biệt tương xứng nếu phần lớn thời gian chỉ dùng bộ ứng dụng văn phòng.

Sinh viên công nghệ thông tin nên chú ý khả năng nâng RAM, hiệu năng đa nhân và hệ thống tản nhiệt. Các môn lập trình cơ bản không quá nặng, nhưng máy ảo, Docker, Android Studio hoặc dữ liệu lớn có thể dùng hết 16GB RAM khá nhanh. Nếu ngân sách cho phép, RAM 24–32GB và SSD 1TB đem lại khoảng trống tốt hơn cho đồ án năm cuối. Với kiến trúc, thiết kế và truyền thông đa phương tiện, GPU rời, màn hình có màu chính xác và khả năng duy trì hiệu năng lâu dài cần được đặt cao hơn độ mỏng.

## Cách kiểm tra máy trước khi thanh toán
Hãy mở đồng thời trình duyệt, một tệp bảng tính lớn, cuộc gọi video và phần mềm đúng với ngành học để quan sát độ phản hồi. Kiểm tra điểm chết màn hình, tiếng quạt, nhiệt độ khu vực kê tay, độ chắc của bản lề và tốc độ kết nối Wi-Fi. Gõ thử ít nhất một đoạn văn dài để biết layout bàn phím có phù hợp hay không. Những thao tác ngắn này phản ánh trải nghiệm bốn năm tốt hơn việc chỉ so sánh hai dòng CPU trên bảng thông số.

Khi mua trực tuyến, người dùng cần đọc rõ chính sách đổi máy lỗi, thời gian bảo hành, điều kiện nâng cấp RAM hoặc SSD và phạm vi bảo hành pin. Nên lưu hóa đơn điện tử, số serial và quay video khi mở hộp. Chi phí sở hữu thực tế còn bao gồm túi chống sốc, chuột, bộ chuyển cổng và phần mềm bản quyền; vì vậy hãy để lại một phần ngân sách thay vì dồn toàn bộ vào thân máy.

![Minh họa khả năng xử lý AI và phần mềm mới trên laptop hiện đại](uploads/news/covers/laptop-ai-npu-2026.png)

## Mua để dùng bốn năm, không chạy theo cấu hình cao nhất
Một chiếc laptop phù hợp cần đủ nhanh ở năm đầu, có dư địa cho đồ án ở năm cuối và vẫn thuận tiện để mang đi mỗi ngày. Ưu tiên cấu hình cân bằng, linh kiện có thể nâng cấp và dịch vụ sau bán hàng rõ ràng. Nếu phải lựa chọn, RAM đủ dùng, SSD rộng, màn hình tốt và pin ổn định thường mang lại giá trị lâu dài hơn một CPU cao cấp nhưng đi kèm màn hình kém hoặc thân máy quá nặng.

Trước khi quyết định, hãy viết ra ba ứng dụng nặng nhất sẽ dùng, số buổi phải mang máy mỗi tuần và giới hạn ngân sách tổng. Danh sách ngắn này giúp loại bỏ các lựa chọn hấp dẫn về quảng cáo nhưng không phù hợp với nhu cầu thật. Máy tốt nhất cho sinh viên không phải mẫu mạnh nhất trong cửa hàng, mà là mẫu hoàn thành công việc ổn định với ít bất tiện nhất trong suốt khóa học.
MARKDOWN,
            ],
            'laptop-gaming-2026-dung-chi-nhin-ten-card-do-hoa' => [
                'marker' => '## Đọc đúng bảng thông số hiệu năng',
                'content' => <<<'MARKDOWN'
![Không gian laptop hiệu năng cao dành cho chơi game](uploads/news/covers/laptop-sang-tao-noi-dung.png)

## Đọc đúng bảng thông số hiệu năng
Tên GPU chỉ là điểm bắt đầu. Người mua nên tìm mức công suất đồ họa tối đa, khả năng Dynamic Boost, dung lượng VRAM và chế độ kết nối màn hình. Cùng một GPU nhưng bản công suất thấp trong thân máy mỏng có thể chậm hơn rõ rệt so với bản công suất cao được làm mát tốt. Kết quả benchmark cũng cần được đọc trong cùng độ phân giải, cùng mức thiết lập và sau ít nhất mười lăm phút tải liên tục để tránh nhầm hiệu năng bùng nổ ngắn hạn với hiệu năng ổn định.

CPU ảnh hưởng đến game thể thao điện tử, mô phỏng và những tựa game có nhiều nhân vật. Tuy nhiên, nâng CPU lên cấp cao nhất trong khi giữ GPU tầm trung thường không phải cách phân bổ ngân sách tối ưu cho game AAA. RAM nên chạy hai kênh, SSD cần đủ chỗ vì nhiều trò chơi hiện vượt 100GB. Kiểm tra máy có khe SSD thứ hai hay không sẽ giúp việc nâng cấp sau này dễ và rẻ hơn.

## Màn hình, bàn phím và độ ồn mới là thứ dùng mỗi ngày
Tần số quét 144Hz chỉ có ý nghĩa khi tấm nền phản hồi đủ nhanh. Hãy xem thêm độ sáng, độ phủ màu, hiện tượng bóng mờ và hỗ trợ đồng bộ khung hình. Nếu thường sáng tạo nội dung, màn hình 100% sRGB là mốc tham khảo thực tế. Với người dùng cắm màn hình ngoài, cần kiểm tra cổng nào nối trực tiếp với GPU rời và chuẩn xuất hình tối đa của cổng đó.

Bàn phím không nên quá nóng ở vùng WASD sau một phiên chơi dài. Tiếng quạt cần được đánh giá ở chế độ hiệu năng, không phải lúc máy đang nhàn rỗi trong cửa hàng. Phần mềm điều khiển nên cho phép đổi nhanh giữa chế độ yên tĩnh, cân bằng và hiệu năng cao. Một máy nhanh hơn vài phần trăm nhưng luôn ồn và nóng có thể mang lại trải nghiệm kém hơn trong thời gian dài.

![Hệ thống phụ kiện và không gian gaming hoàn chỉnh](uploads/news/covers/bao-duong-laptop.png)

## Bài kiểm tra 30 phút trước khi quyết định
Chạy một game quen thuộc khoảng hai mươi phút, ghi lại FPS trung bình, mức tụt FPS, nhiệt độ và tiếng quạt. Sau đó thoát game và kiểm tra thời gian máy trở về trạng thái mát, yên tĩnh. Thử rút sạc để xem chế độ pin có đáp ứng nhu cầu học tập hoặc làm việc cơ bản hay không. Đừng bỏ qua khối lượng bộ sạc vì nhiều mẫu máy gaming trở nên bất tiện khi tổng hành lý vượt quá ba kilogram.

Cuối cùng, so sánh giá trong cùng tổng thể RAM, SSD, màn hình và thời hạn bảo hành. Một mẫu rẻ hơn nhưng phải nâng RAM, mua thêm SSD và thay màn hình ngoài có thể có tổng chi phí cao hơn. Quyết định tốt là quyết định dựa trên FPS ổn định, nhiệt độ kiểm soát được và trải nghiệm sử dụng thật, không dựa vào tên GPU được in lớn nhất trên trang quảng cáo.
MARKDOWN,
            ],
            '7-tieu-chi-chon-laptop-van-phong-mong-nhe-ben-pin' => [
                'marker' => '## Một ngày làm việc thực tế cần những gì?',
                'content' => <<<'MARKDOWN'
![Người dùng làm việc và học tập với laptop cơ động](uploads/news/covers/laptop-sinh-vien-2026.png)

## Một ngày làm việc thực tế cần những gì?
Laptop văn phòng thường phải duy trì trình duyệt nhiều tab, ứng dụng nhắn tin, bảng tính, email và họp video cùng lúc. Vì vậy, RAM 16GB là mức khởi đầu hợp lý hơn 8GB, kể cả khi công việc hiện tại có vẻ nhẹ. SSD 512GB đủ cho tài liệu và ứng dụng phổ biến; người làm dữ liệu, chỉnh ảnh hoặc lưu nhiều video nên cân nhắc 1TB. CPU tầm trung thế hệ mới thường đem lại cân bằng tốt giữa tốc độ, nhiệt độ và thời lượng pin.

Thời lượng pin quảng cáo không phản ánh hoàn toàn một ngày làm việc. Độ sáng màn hình, cuộc gọi video, Wi-Fi và số ứng dụng nền có thể làm thời gian sử dụng giảm mạnh. Hãy tìm bài đo pin ở mức sáng khoảng 200 nit và tác vụ gần với nhu cầu của mình. Sạc USB-C nhỏ gọn là lợi thế lớn vì có thể dùng chung với màn hình, điện thoại hoặc pin dự phòng tương thích.

## Công thái học quan trọng hơn vài trăm gram
Máy nhẹ nhưng bàn phím nông, màn hình tối hoặc bản lề rung vẫn gây khó chịu sau nhiều giờ. Màn hình tỷ lệ 16:10 hiển thị thêm nội dung theo chiều dọc, hữu ích cho tài liệu và bảng tính. Lớp chống chói giúp giảm phản xạ trong văn phòng nhiều đèn. Webcam Full HD, micro lọc ồn và phím tắt tắt micro là các chi tiết nhỏ nhưng tạo khác biệt lớn cho người thường xuyên họp trực tuyến.

Hãy thử mở máy bằng một tay, gõ một đoạn dài và kéo thả tệp trên touchpad. Kiểm tra cạnh máy có gây cấn cổ tay hay không, luồng khí nóng có thổi vào tay cầm chuột hay không và màn hình có đủ sáng gần cửa sổ hay không. Đây là các yếu tố khó nhận biết trên bảng cấu hình nhưng ảnh hưởng trực tiếp đến hiệu suất làm việc mỗi ngày.

![Laptop AI hỗ trợ cuộc họp và tác vụ văn phòng hiện đại](uploads/news/covers/laptop-ai-npu-2026.png)

## Kết nối và khả năng làm việc lâu dài
Một cổng USB-C hỗ trợ sạc và xuất hình, ít nhất một USB-A, HDMI và jack tai nghe giúp giảm phụ thuộc vào hub. Nếu làm việc với mạng nội bộ hoặc thẻ nhớ, cần kiểm tra cổng tương ứng ngay từ đầu. Wi-Fi thế hệ mới hữu ích trong môi trường đông thiết bị, nhưng độ ổn định của driver và chất lượng ăng-ten cũng quan trọng không kém tên chuẩn kết nối.

Đối với doanh nghiệp, bảo mật sinh trắc học, chip TPM, khả năng quản lý thiết bị và dịch vụ bảo hành tại nơi sử dụng có thể đáng giá hơn hiệu năng tăng nhẹ. Với người dùng cá nhân, nên ưu tiên mẫu có linh kiện thay thế phổ biến và chính sách bảo hành minh bạch. Một chiếc laptop văn phòng tốt là công cụ gần như “biến mất” trong quá trình làm việc: bật nhanh, pin đủ lâu, họp rõ và không khiến người dùng phải liên tục xử lý vấn đề kỹ thuật.
MARKDOWN,
            ],
            'laptop-ai-npu-ai-can-nang-cap-2026' => [
                'marker' => '## Những tác vụ NPU đang làm tốt',
                'content' => <<<'MARKDOWN'
![Laptop hiện đại trong quy trình làm việc sáng tạo](uploads/news/covers/laptop-sang-tao-noi-dung.png)

## Những tác vụ NPU đang làm tốt
NPU phát huy hiệu quả với các tác vụ diễn ra liên tục nhưng không cần sức mạnh đồ họa lớn: làm mờ nền, giữ khuôn mặt ở giữa khung hình, lọc tiếng ồn, nhận diện giọng nói và xử lý một số mô hình ngôn ngữ nhỏ. Khi tác vụ được chuyển khỏi CPU hoặc GPU, máy có thể tiết kiệm điện và giữ hiệu năng cho ứng dụng chính. Lợi ích này rõ nhất trên laptop mỏng nhẹ dùng pin, đặc biệt trong những buổi họp hoặc ghi chép kéo dài.

Tuy nhiên, không phải phần mềm nào cũng tự động dùng NPU. Ứng dụng cần được nhà phát triển hỗ trợ đúng nền tảng và đúng API. Người mua nên kiểm tra danh sách tính năng đang hoạt động thay vì chỉ nhìn con số TOPS. Một NPU mạnh nhưng thiếu ứng dụng phù hợp chưa chắc tạo ra khác biệt ngay trong ngày đầu sử dụng.

## Phân biệt AI cục bộ và AI trên đám mây
AI cục bộ xử lý dữ liệu ngay trên máy, có lợi về độ trễ, khả năng hoạt động ngoại tuyến và quyền riêng tư. AI đám mây có thể dùng mô hình lớn hơn nhưng phụ thuộc kết nối Internet và chính sách lưu trữ dữ liệu của dịch vụ. Nhiều ứng dụng kết hợp cả hai: tác vụ nhẹ chạy tại máy, phần phức tạp gửi lên máy chủ. Người làm việc với tài liệu nhạy cảm cần tìm hiểu rõ dữ liệu nào rời khỏi thiết bị.

Dung lượng RAM vẫn là yếu tố quan trọng khi chạy mô hình cục bộ. Với nhu cầu AI cơ bản, 16GB có thể đủ; người phát triển phần mềm, xử lý dữ liệu hoặc chạy nhiều mô hình nên cân nhắc 32GB trở lên. Băng thông bộ nhớ và khả năng làm mát cũng ảnh hưởng đến trải nghiệm, vì vậy không nên tách NPU khỏi tổng thể cấu hình.

![Laptop văn phòng mỏng nhẹ ứng dụng AI trong công việc hàng ngày](uploads/news/covers/laptop-van-phong-mong-nhe.png)

## Checklist trước khi trả tiền cho nhãn AI PC
Liệt kê ba tính năng AI bạn thực sự dùng, kiểm tra chúng chạy bằng NPU hay vẫn dựa vào Internet, và đo ảnh hưởng đến pin. Xem thêm RAM có nâng cấp được không, SSD có đủ rộng không và phần mềm có hỗ trợ hệ điều hành bạn chọn hay không. Nếu câu trả lời còn mơ hồ, hãy ưu tiên chất lượng màn hình, pin, bàn phím và hiệu năng CPU thay vì trả thêm chỉ cho nhãn AI.

Người đang dùng laptop hai hoặc ba năm tuổi vẫn đáp ứng tốt công việc không cần nâng cấp vội. Nhóm mua máy mới để dùng dài hạn có thể chọn nền tảng tích hợp NPU như một lớp dự phòng cho phần mềm tương lai. Giá trị thật của laptop AI nằm ở những phút pin tiết kiệm được, tác vụ hoàn thành nhanh hơn và dữ liệu được xử lý riêng tư hơn, chứ không nằm ở số lượng biểu tượng AI trên vỏ hộp.
MARKDOWN,
            ],
            've-sinh-bao-duong-laptop-dung-cach-8-viec-dinh-ky' => [
                'marker' => '## Lịch bảo dưỡng theo từng mốc thời gian',
                'content' => <<<'MARKDOWN'
![Laptop hiệu năng cao cần được kiểm tra nhiệt độ định kỳ](uploads/news/covers/laptop-gaming-2026.png)

## Lịch bảo dưỡng theo từng mốc thời gian
Mỗi tuần, người dùng nên lau bề mặt máy, kiểm tra khe thoát gió và xóa các tệp tải xuống không còn cần thiết. Mỗi tháng, hãy cập nhật hệ điều hành, trình duyệt, phần mềm bảo mật và kiểm tra dung lượng SSD còn trống. Ba đến sáu tháng một lần là thời điểm phù hợp để xem báo cáo sức khỏe pin, rà soát ứng dụng khởi động cùng hệ thống và sao lưu dữ liệu quan trọng sang một vị trí độc lập.

Khoảng thời gian vệ sinh bên trong phụ thuộc môi trường sử dụng. Nhà có thú cưng, phòng nhiều bụi hoặc thói quen đặt máy trên chăn khiến quạt bám bụi nhanh hơn. Dấu hiệu cần kiểm tra sớm gồm quạt quay lớn khi tải nhẹ, nhiệt độ tăng bất thường, hiệu năng giảm sau vài phút hoặc máy tự tắt. Không nên chờ đến đúng một năm nếu thiết bị đã phát ra các tín hiệu này.

## Những thao tác người dùng có thể tự làm an toàn
Tắt máy hoàn toàn và rút sạc trước khi lau. Dùng khăn microfiber sạch, chỉ làm ẩm nhẹ và không để chất lỏng chảy vào khe máy. Khi dùng khí nén, giữ bình thẳng và thổi từng nhịp ngắn; không để luồng khí làm quạt quay quá nhanh. Bàn chải mềm phù hợp với khe bàn phím, nhưng vật kim loại hoặc hóa chất tẩy mạnh có thể gây xước và hỏng lớp phủ.

Việc tháo nắp đáy có thể ảnh hưởng điều kiện bảo hành tùy hãng. Nếu không biết vị trí cáp pin, loại ốc hoặc lực siết phù hợp, nên giao cho kỹ thuật viên. Thay keo tản nhiệt không phải lúc nào cũng cần thiết và loại keo không phù hợp có thể làm nhiệt độ xấu hơn. Hãy yêu cầu ghi nhận tình trạng máy, linh kiện và nhiệt độ trước–sau khi bảo dưỡng.

![Không gian sử dụng laptop gọn gàng giúp máy bền hơn](uploads/news/covers/laptop-sinh-vien-2026.png)

## Bảo vệ dữ liệu là một phần của bảo dưỡng
Ổ cứng có thể hỏng dù máy bên ngoài vẫn sạch. Quy tắc sao lưu 3-2-1 là một tham khảo tốt: ba bản dữ liệu, trên hai loại thiết bị và một bản ở vị trí khác. Với tài liệu công việc, nên bật lịch sử phiên bản hoặc đồng bộ đám mây có xác thực hai bước. Thỉnh thoảng hãy thử khôi phục một tệp để chắc chắn bản sao lưu thực sự dùng được.

Sau mỗi lần bảo dưỡng, theo dõi nhiệt độ, tiếng quạt, tốc độ khởi động và thời lượng pin trong vài ngày. Ghi lại thay đổi giúp phát hiện lỗi mới và xác định thao tác nào mang lại hiệu quả. Mục tiêu không phải làm máy trông mới trong một buổi, mà là duy trì trạng thái ổn định, an toàn dữ liệu và kéo dài thời gian sử dụng với chi phí hợp lý.
MARKDOWN,
            ],
        ];
    }
}
