<?php

namespace App\Mail;

use App\Models\Promotion;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventVoucherMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $customer,
        public Promotion $promotion,
        public ?CarbonInterface $expiresAt = null,
    ) {}

    public function build(): self
    {
        $theme = $this->theme();

        return $this
            ->subject($theme['subject'])
            ->view('emails.event-voucher')
            ->with(['theme' => $theme]);
    }

    public function theme(): array
    {
        $themes = [
            'TETDUONGLICH' => ['icon' => '🎆', 'headline' => 'Chúc Mừng Năm Mới', 'message' => 'Khởi đầu năm mới với thật nhiều niềm vui, may mắn và những trải nghiệm công nghệ tuyệt vời.', 'primary' => '#2563eb', 'accent' => '#67e8f9', 'background' => '#07101f', 'card' => '#0d1930'],
            'TETNGUYENDAN' => ['icon' => '🧧', 'headline' => 'Tết Sum Vầy', 'message' => 'Kính chúc bạn và gia đình một năm mới an khang, thịnh vượng, vạn sự như ý.', 'primary' => '#dc2626', 'accent' => '#fbbf24', 'background' => '#2b0808', 'card' => '#5f1010'],
            'QUOCTEPHUNU' => ['icon' => '🌷', 'headline' => 'Rạng Rỡ Ngày 8/3', 'message' => 'Chúc những người phụ nữ tuyệt vời luôn tự tin, hạnh phúc và tỏa sáng theo cách riêng.', 'primary' => '#db2777', 'accent' => '#f9a8d4', 'background' => '#2a0a20', 'card' => '#52123d'],
            'GIOTOHUNGVUONG' => ['icon' => '🏛️', 'headline' => 'Hướng Về Cội Nguồn', 'message' => 'Cùng NextGen tri ân các Vua Hùng và gìn giữ niềm tự hào về nguồn cội dân tộc.', 'primary' => '#b45309', 'accent' => '#fde68a', 'background' => '#211507', 'card' => '#47300c'],
            'GIAIPHONGMIENNAM' => ['icon' => '🇻🇳', 'headline' => 'Mừng Ngày Thống Nhất', 'message' => 'Hòa chung niềm tự hào dân tộc trong ngày đất nước thống nhất và trọn niềm vui.', 'primary' => '#dc2626', 'accent' => '#facc15', 'background' => '#270707', 'card' => '#5a1010'],
            'QUOCTELAODONG' => ['icon' => '🛠️', 'headline' => 'Tôn Vinh Người Lao Động', 'message' => 'Chúc bạn luôn tràn đầy năng lượng, nhiệt huyết và gặt hái nhiều thành công trong công việc.', 'primary' => '#ea580c', 'accent' => '#fdba74', 'background' => '#261006', 'card' => '#512008'],
            'QUOCTETHIEUNHI' => ['icon' => '🎈', 'headline' => 'Ngày Hội Tuổi Thơ', 'message' => 'Chúc các bạn nhỏ luôn khỏe mạnh, vui tươi, ham học hỏi và có một tuổi thơ thật rực rỡ.', 'primary' => '#0891b2', 'accent' => '#fde047', 'background' => '#06232b', 'card' => '#0b4351'],
            'QUOCKHANH' => ['icon' => '🇻🇳', 'headline' => 'Tự Hào Việt Nam', 'message' => 'Mừng Quốc Khánh Việt Nam, NextGen gửi đến bạn món quà đặc biệt thay lời tri ân.', 'primary' => '#dc2626', 'accent' => '#facc15', 'background' => '#270707', 'card' => '#5a1010'],
            'TETTRUNGTHU' => ['icon' => '🥮', 'headline' => 'Trăng Rằm Sum Vầy', 'message' => 'Chúc bạn và gia đình một mùa Trung Thu đoàn viên, ấm áp và ngập tràn niềm vui.', 'primary' => '#7c3aed', 'accent' => '#fde68a', 'background' => '#130b2d', 'card' => '#2d1b59'],
            'PHUNUVIETNAM' => ['icon' => '🌹', 'headline' => 'Tôn Vinh Phụ Nữ Việt Nam', 'message' => 'Chúc một nửa yêu thương của Việt Nam luôn xinh đẹp, bản lĩnh, hạnh phúc và thành công.', 'primary' => '#be185d', 'accent' => '#fda4af', 'background' => '#29091c', 'card' => '#551239'],
            'NHAGIAOVIETNAM' => ['icon' => '📚', 'headline' => 'Tri Ân Người Lái Đò', 'message' => 'Kính chúc quý thầy cô luôn mạnh khỏe, hạnh phúc và giữ mãi ngọn lửa truyền cảm hứng.', 'primary' => '#1d4ed8', 'accent' => '#93c5fd', 'background' => '#07152e', 'card' => '#102d61'],
            'GIANGSINH' => ['icon' => '🎄', 'headline' => 'Giáng Sinh An Lành', 'message' => 'Chúc bạn một mùa Giáng Sinh ấm áp, an lành và ngập tràn những khoảnh khắc đáng nhớ.', 'primary' => '#15803d', 'accent' => '#f87171', 'background' => '#071d12', 'card' => '#103c24'],
        ];

        $theme = $themes[strtoupper((string) $this->promotion->code)] ?? [
            'icon' => '🎁', 'headline' => 'Quà Tặng Dành Cho Bạn',
            'message' => 'NextGen gửi đến bạn một voucher đặc biệt nhân dịp sự kiện này.',
            'primary' => '#2563eb', 'accent' => '#67e8f9', 'background' => '#07101f', 'card' => '#0d1930',
        ];
        $theme['subject'] = $theme['icon'].' '.$theme['headline'].' - Nhận voucher '.$this->promotion->code;

        return $theme;
    }
}
