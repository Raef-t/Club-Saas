<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('notification_templates')
            ->where('system_key', 'attendance_checkout')
            ->update([
                'body' => 'عزيزي {اسم اللاعب}، نتمنى أن تكون قد حظيت بتمرين رائع! تم تسجيل خروجك بنجاح بتاريخ {التاريخ} الموافق ليوم {اليوم} الساعة {الوقت}. لقد استمر تدريبك لمدة {مدة التدريب} ضمن اشتراكك الحالي: {اسم الاشتراك}. نشكر التزامك ونتطلع لرؤيتك قريباً.',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('notification_templates')
            ->where('system_key', 'attendance_checkout')
            ->update([
                'body' => 'عزيزي {اسم اللاعب}، نتمنى أن تكون قد حظيت بتمرين رائع! تم تسجيل خروجك بنجاح بتاريخ {التاريخ} الموافق ليوم {اليوم} الساعة {الوقت}. لقد استمر تدريبك لمدة {مدة التدريب} دقيقة ضمن اشتراكك الحالي: {اسم الاشتراك}. نشكر التزامك ونتطلع لرؤيتك قريباً.',
            ]);
    }
};
