<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tintuc')) {
            Schema::create('tintuc', function (Blueprint $table) {
                $table->id();
                $table->string('tieude');
                $table->string('slug')->unique();
                $table->string('danhmuc')->default('Tin tức');
                $table->string('tacgia')->default('Admin');
                $table->string('hinhanh')->nullable();
                $table->string('mota_hinhanh')->nullable();
                $table->enum('trangthai', ['draft', 'scheduled', 'published'])->default('draft');
                $table->timestamp('dang_luc')->nullable();
                $table->unsignedBigInteger('luotxem')->default(0);
                $table->text('tomtat')->nullable();
                $table->longText('noidung')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('tintuc', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('mota_hinhanh');
            $table->string('seo_description', 320)->nullable()->after('seo_title');
            $table->string('seo_keywords')->nullable()->after('seo_description');
            $table->string('canonical_url')->nullable()->after('seo_keywords');
            $table->boolean('no_index')->default(false)->after('canonical_url');
            $table->boolean('noi_bat')->default(false)->after('no_index');
            $table->boolean('ghim')->default(false)->after('noi_bat');
            $table->string('workflow_status', 30)->default('draft')->after('trangthai');
            $table->unsignedInteger('reading_time')->default(1)->after('luotxem');
            $table->unsignedBigInteger('share_count')->default(0)->after('reading_time');
            $table->timestamp('reviewed_at')->nullable()->after('dang_luc');
            $table->string('reviewed_by')->nullable()->after('reviewed_at');
            $table->index(['trangthai', 'dang_luc']);
            $table->index(['noi_bat', 'ghim']);
        });

        Schema::create('news_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        Schema::create('news_tag', function (Blueprint $table) {
            $table->foreignId('news_id')->constrained('tintuc')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('news_tags')->cascadeOnDelete();
            $table->primary(['news_id', 'tag_id']);
        });

        Schema::create('news_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('tintuc')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('editor')->nullable();
            $table->string('note')->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['news_id', 'version']);
        });

        Schema::create('news_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('tintuc')->cascadeOnDelete();
            $table->string('event', 30);
            $table->string('session_hash', 64)->nullable();
            $table->string('referrer')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['news_id', 'event', 'created_at']);
        });

        Schema::create('news_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->boolean('active')->default(true);
            $table->string('token', 64)->unique();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_subscribers');
        Schema::dropIfExists('news_events');
        Schema::dropIfExists('news_revisions');
        Schema::dropIfExists('news_tag');
        Schema::dropIfExists('news_tags');

        Schema::table('tintuc', function (Blueprint $table) {
            $table->dropIndex(['trangthai', 'dang_luc']);
            $table->dropIndex(['noi_bat', 'ghim']);
            $table->dropColumn([
                'seo_title', 'seo_description', 'seo_keywords', 'canonical_url', 'no_index',
                'noi_bat', 'ghim', 'workflow_status', 'reading_time', 'share_count',
                'reviewed_at', 'reviewed_by',
            ]);
        });
    }
};
