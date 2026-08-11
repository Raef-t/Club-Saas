<?php

use Illuminate\Support\Facades\Route;
use Modules\NotificationManager\Http\Controllers\Api\V1\NotificationController;

/*
|--------------------------------------------------------------------------
| واجهات برمجة تطبيقات الإشعارات - NotificationManager
|--------------------------------------------------------------------------
|
| 📌 تقسيم المسارات:
|   1. /v1/notifications/*         → واجهات المستخدم العادي (auth فقط)
|   2. /v1/admin/notifications/*   → واجهات الإدارة (auth + role:admin|manager)
|   3. /v1/fcm-tokens/*            → إدارة توكنات الإشعارات Push (auth فقط)
|
| 🔒 الحماية:
|   - المستخدم العادي: auth:sanctum
|   - الإدارة:         auth:sanctum + role:admin|manager (Spatie)
|
*/

// تم نقل إدارة توكنات FCM بالكامل إلى موديول Authentication
// هذا الموديول (NotificationManager) سيعتمد فقط على القراءة من جدول user_devices عند الإرسال

// ========================================
// 🔹 واجهات المستخدم العادي (auth فقط)
// ========================================
Route::middleware(['auth:sanctum', 'check.permission'])
    ->prefix('v1/notifications')
    ->as('api.notifications.')
    ->group(function () {

        // GET  /v1/notifications           → قائمة إشعاراتي
        Route::get('/', [NotificationController::class, 'index'])->name('index');

        // GET  /v1/notifications/unread/count → عدد غير المقروءة
        Route::get('unread/count', [NotificationController::class, 'unreadCount'])->name('unread-count');

        // POST /v1/notifications/mark-all-as-read → تعليم الكل مقروء
        Route::post('mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');

        // GET  /v1/notifications/{reception}       → تفاصيل إشعار
        Route::get('{reception}', [NotificationController::class, 'show'])->name('show');

        // PATCH /v1/notifications/{reception}/read → تعليم كمقروء
        Route::patch('{reception}/read', [NotificationController::class, 'markAsRead'])->name('read');

        // DELETE /v1/notifications/{reception}     → حذف من قائمتي
        Route::delete('{reception}', [NotificationController::class, 'destroy'])->name('destroy');
    });

// ========================================
// 🔹 واجهات الإدارة (auth + check.permission)
// ========================================
Route::middleware(['auth:sanctum', 'check.permission'])
    ->prefix('v1/admin/notifications')
    ->as('api.admin.notifications.')
    ->group(function () {

        // POST   /v1/admin/notifications        → إنشاء إشعار جديد
        Route::post('/', [NotificationController::class, 'store'])->name('store');

        // POST   /v1/admin/notifications/send-to-users → إرسال إشعار لمجموعة user_ids مخصصة
        Route::post('send-to-users', [NotificationController::class, 'sendToUsers'])->name('send-to-users');

        // GET    /v1/admin/notifications        → عرض جميع الإشعارات مع فلترة
        Route::get('/', [NotificationController::class, 'adminIndex'])->name('index');

        // GET    /v1/admin/notifications/{id}   → تفاصيل إشعار مع المستلمين
        Route::get('/{notification}', [NotificationController::class, 'adminShow'])->name('show');

        // DELETE /v1/admin/notifications/{id}   → حذف إشعار من النظام بالكامل
        Route::delete('/{notification}', [NotificationController::class, 'adminDestroy'])->name('destroy');
    });
