<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_list_only_returns_published_articles_that_are_due(): void
    {
        News::create($this->article(['tieude' => 'Đã đăng', 'slug' => 'da-dang', 'trangthai' => 'published', 'dang_luc' => now()->subMinute()]));
        News::create($this->article(['tieude' => 'Bản nháp', 'slug' => 'ban-nhap', 'trangthai' => 'draft']));
        News::create($this->article(['tieude' => 'Chưa đến lịch', 'slug' => 'chua-den-lich', 'trangthai' => 'scheduled', 'dang_luc' => now()->addDay()]));

        $this->getJson('/api/news?scope=public')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'da-dang');
    }

    public function test_article_can_be_opened_by_slug_and_view_is_counted(): void
    {
        $article = News::create($this->article(['slug' => 'chon-laptop', 'trangthai' => 'published', 'dang_luc' => now()->subMinute()]));

        $this->getJson('/api/news/chon-laptop')->assertOk()->assertJsonPath('id', $article->id);
        $this->assertDatabaseHas('tintuc', ['id' => $article->id, 'luotxem' => 1]);
    }

    public function test_search_matches_summary_and_content(): void
    {
        News::create($this->article(['slug' => 'gaming', 'tomtat' => 'Hướng dẫn chọn RTX cho sinh viên', 'trangthai' => 'published', 'dang_luc' => now()]));
        News::create($this->article(['slug' => 'office', 'tomtat' => 'Máy văn phòng', 'trangthai' => 'published', 'dang_luc' => now()]));

        $this->getJson('/api/news?scope=public&q=RTX')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'gaming');
    }

    public function test_newsletter_subscription_is_idempotent(): void
    {
        $this->postJson('/api/news-subscribe', ['email' => 'reader@example.com'])->assertOk();
        $this->postJson('/api/news-subscribe', ['email' => 'reader@example.com'])->assertOk();
        $this->assertDatabaseCount('news_subscribers', 1);
    }

    public function test_tracking_share_updates_analytics(): void
    {
        $article = News::create($this->article(['slug' => 'analytics']));
        $this->postJson("/api/news/{$article->id}/track", ['event' => 'share'])->assertCreated();
        $this->assertDatabaseHas('tintuc', ['id' => $article->id, 'share_count' => 1]);
        $this->assertDatabaseHas('news_events', ['news_id' => $article->id, 'event' => 'share']);
    }

    private function article(array $overrides = []): array
    {
        return array_merge([
            'tieude' => 'Bài viết thử nghiệm', 'slug' => 'bai-viet-thu-nghiem', 'danhmuc' => 'Công nghệ',
            'tacgia' => 'NextGen', 'trangthai' => 'draft', 'workflow_status' => 'draft',
            'tomtat' => 'Nội dung tóm tắt', 'noidung' => 'Nội dung chi tiết của bài viết.',
        ], $overrides);
    }
}
