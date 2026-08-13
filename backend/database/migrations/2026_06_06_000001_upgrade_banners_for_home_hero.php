<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->string('image');
                $table->string('media_type')->default('image');
                $table->string('mobile_image')->nullable();
                $table->string('mobile_media_type')->nullable();
                $table->string('link_url', 500)->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'eyebrow')) {
                $table->string('eyebrow', 120)->nullable()->after('subtitle');
            }
            if (! Schema::hasColumn('banners', 'highlight')) {
                $table->string('highlight', 180)->nullable()->after('eyebrow');
            }
            if (! Schema::hasColumn('banners', 'description')) {
                $table->text('description')->nullable()->after('highlight');
            }
            if (! Schema::hasColumn('banners', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('link_url');
            }
            if (! Schema::hasColumn('banners', 'primary_label')) {
                $table->string('primary_label', 60)->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('banners', 'secondary_label')) {
                $table->string('secondary_label', 60)->nullable()->after('primary_label');
            }
            if (! Schema::hasColumn('banners', 'product_badge')) {
                $table->string('product_badge', 80)->nullable()->after('secondary_label');
            }
            if (! Schema::hasColumn('banners', 'product_feature')) {
                $table->string('product_feature', 120)->nullable()->after('product_badge');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('banners')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table) {
            foreach ([
                'eyebrow',
                'highlight',
                'description',
                'product_id',
                'primary_label',
                'secondary_label',
                'product_badge',
                'product_feature',
            ] as $column) {
                if (Schema::hasColumn('banners', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
