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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            
            // اسم القالب (مثل: إغلاق النادي)
            $table->string('name');
            
            // مفتاح النظام الفريد (مثل: club_closure)
            $table->string('system_key')->unique();
            
            // عنوان الإشعار
            $table->string('subject')->nullable();
            
            // محتوى الإشعار الذي يحوي المتغيرات مثل {start_date}
            $table->text('body');
            
            // أسماء المتغيرات المتوقعة كـ JSON لتسهيل التعامل معها (مثل: ["club_name", "start_date", "end_date", "reason"])
            $table->json('variables')->nullable();
            
            // حالة القالب
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
