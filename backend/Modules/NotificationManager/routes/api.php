<?php

use Illuminate\Support\Facades\Route;
use Modules\NotificationManager\Http\Controllers\Api\V1\NotificationController;
use Modules\NotificationManager\Http\Controllers\Api\V1\FcmTokenController;

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

// ========================================
// 🔹 FCM Tokens — يعتمد على جدول user_devices
// ========================================
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    Route::prefix('fcm-tokens')->as('api.fcm-tokens.')->group(function () {
        // POST /v1/fcm-tokens → تسجيل توكن جهاز
        Route::post('/', [FcmTokenController::class, 'store'])->name('store');

        // DELETE /v1/fcm-tokens/{token} → حذف التوكن عند تسجيل الخروج
        Route::delete('/{token}', [FcmTokenController::class, 'destroy'])->name('destroy');
    });
});

// ========================================
// 🔹 واجهات المستخدم العادي (auth فقط)
// ========================================
Route::middleware(['auth:sanctum'])
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
// 🔹 واجهات الإدارة (auth + role أدمن)
// ========================================
Route::middleware(['auth:sanctum', 'role:admin|manager'])
    ->prefix('v1/admin/notifications')
    ->as('api.admin.notifications.')
    ->group(function () {

        // POST   /v1/admin/notifications        → إنشاء إشعار جديد
        Route::post('/', [NotificationController::class, 'store'])->name('store');

        // GET    /v1/admin/notifications        → عرض جميع الإشعارات مع فلترة
        Route::get('/', [NotificationController::class, 'adminIndex'])->name('index');

        // GET    /v1/admin/notifications/{id}   → تفاصيل إشعار مع المستلمين
        Route::get('/{notification}', [NotificationController::class, 'adminShow'])->name('show');

        // DELETE /v1/admin/notifications/{id}   → حذف إشعار من النظام بالكامل
        Route::delete('/{notification}', [NotificationController::class, 'adminDestroy'])->name('destroy');
    });
