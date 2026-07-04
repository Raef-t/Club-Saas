<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ملاحظة: لا يُنشأ جدول fcm_tokens هنا
        // النظام يعتمد على جدول user_devices الموجود في Authentication module
        // (يحتوي على: user_id, fcm_token, device_info)

        // ========================================
        // جدول الإشعارات الرئيسي
        // ========================================
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('body');

            // المرسل (admin أو system)
            $table->foreignId('sender_id')
                  ->nullable()
                  ->constrained('authentication_users')
                  ->nullOnDelete();

            $table->string('sender_type')->nullable(); // admin | system

            // لقطة عن الهدف (من أُرسل الإشعار إليهم؟)
            $table->json('target_snapshot')->nullable();

            $table->timestamps();
        });

        // ========================================
        // جدول مستلمي الإشعارات
        // ========================================
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notification_id')
                  ->constrained('notifications')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained('authentication_users')
                  ->cascadeOnDelete();

            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // منع تكرار نفس الإشعار لنفس المستخدم
            $table->unique(['notification_id', 'user_id']);
        });

        // ========================================
        // جدول مرفقات الإشعارات
        // ========================================
        Schema::create('notification_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notification_id')
                  ->constrained('notifications')
                  ->cascadeOnDelete();

            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // بالبايت

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_attachments');
        Schema::dropIfExists('notification_recipients');
        Schema::dropIfExists('notifications');
    }
};
