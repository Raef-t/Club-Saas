<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('تطبيق المتدرب')->comment('اسم التطبيق - تطبيق المتدرب');
            $table->string('version_number')->comment('رقم الإصدار مثل 1.0.0');
            $table->unsignedInteger('build_number')->default(1)->comment('رقم البناء Build Number');
            $table->enum('platform', ['android', 'ios'])->default('android')->comment('نظام التشغيل');
            $table->text('download_url')->nullable()->comment('رابط التحميل المباشر');
            $table->string('file_path')->nullable()->comment('المسار النسبي للملف على السيرفر');
            $table->unsignedBigInteger('file_size')->nullable()->comment('حجم الملف بالبايت');
            $table->boolean('is_force_update')->default(false)->comment('هل التحديث إجباري');
            $table->text('release_notes')->nullable()->comment('ملاحظات الإصدار وسجل التغييرات');
            $table->boolean('is_active')->default(true)->comment('حالة تفعيل الإصدار');
            $table->unsignedBigInteger('created_by')->nullable()->comment('معرف المستخدم الذي رفع الإصدار');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['platform', 'is_active', 'build_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
