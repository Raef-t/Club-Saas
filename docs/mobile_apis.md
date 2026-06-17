# Mobile APIs Documentation

This document contains the inferred APIs required for various mobile application UI screens based on UI designs.

---

## 1. Login Screen (شاشة تسجيل الدخول)

**UI Context:** Technogym Login Screen (اهلا بعودتك)

### 1.1. Login API
This is the primary API triggered when the "تسجيل الدخول" button is pressed.

*   **Method:** `POST`
*   **Endpoint:** `/api/v1/auth/login`
*   **Request Payload:**
    ```json
    {
      "email": "user@example.com",
      "password": "user_password123"
    }
    ```
*   **Response (Success - 200 OK):** Returns an access token (e.g., JWT) and basic user profile.
    ```json
    {
      "token": "eyJhbGciOiJIUzI1NiIsInR5c...",
      "user": {
        "id": 1,
        "name": "أحمد",
        "email": "user@example.com"
      }
    }
    ```
*   **Response (Error - 401 Unauthorized):** Invalid credentials.
*   **Response (Error - 422 Unprocessable Entity):** Validation errors (e.g., empty email or password fields).

### 1.2. Forgot Password API (نسيان كلمة المرور)
Triggered indirectly via the "نسيت كلمة المرور؟" link.

*   **Method:** `POST`
*   **Endpoint:** `/api/v1/auth/forgot-password` OR `/api/v1/auth/password/email`
*   **Request Payload:**
    ```json
    {
      "email": "user@example.com"
    }
    ```
*   **Response:** Success message confirming an OTP or reset link has been sent.

### 1.3. Register / Signup API (إنشاء حساب جديد)
Triggered indirectly via the "سجل الان" link which navigates to the registration screen.

*   **Method:** `POST`
*   **Endpoint:** `/api/v1/auth/register`

---

## 2. Member Dashboard Screen (شاشة الرئيسية - لوحة تحكم العضو)

**UI Context:** Member Home/Dashboard (مرحباً نايا)

This screen aggregates data from multiple sources. It is best implemented as a consolidated "Dashboard API" for performance, along with separate endpoints for specific sections if needed.

### 2.1. Main Dashboard Data API (البيانات الأساسية للوحة التحكم)
Provides the profile, subscription status, and quick statistics.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/member/dashboard` (or `/api/v1/me`)
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "profile": {
        "name": "نايا الأسعد",
        "avatar_url": "https://...",
        "date_string": "الأحد ، 8 أغسطس",
        "unread_notifications_count": 2
      },
      "current_subscription": {
        "status": "active",
        "status_label": "نشط",
        "package_name": "باقة vip",
        "expiry_date": "23/6/2026",
        "subscription_number": "23455667",
        "price_formatted": "345$"
      },
      "statistics": {
        "remaining_sessions": 60,
        "total_attendance": 34,
        "training_hours": 50,
        "last_payment_amount": "543$"
      }
    }
    ```

### 2.2. Quick Actions APIs (الإجراءات السريعة)
These are primarily navigation buttons, but the "QR" button directly fetches a QR code for gym access.

*   **Generate/Fetch Access QR Code:**
    *   **Method:** `GET`
    *   **Endpoint:** `/api/v1/attendance/qr` (Already exists as per existing documentation)
*   **Other actions (Payments, Trainers, Attendance):** Navigate to their respective screens, calling `/api/v1/member/payments`, `/api/v1/trainers`, etc.

### 2.3. Recent Activities API (النشاطات الأخيرة)
Populates the list of recent sessions.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/member/activities/recent`
*   **Response (Success - 200 OK):**
    ```json
    {
      "data": [
        {
          "id": 1,
          "title": "جلسة تدريب القوة",
          "date_time": "اليوم : 8 صباحاً",
          "duration": "1.5 ساعة",
          "type": "strength"
        }
      ]
    }
    ```

### 2.4. Upcoming Events/Classes API (الفعاليات القادمة)
Populates the horizontal list of upcoming events or workouts.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/member/events/upcoming`
*   **Response (Success - 200 OK):**
    ```json
    {
      "data": [
        {
          "id": 101,
          "title": "chest day",
          "image_url": "https://...",
          "exercises_count": 8,
          "duration_minutes": 45,
          "calories_burned": 2300
        }
      ]
    }
    ```

---

## 3. Activities Screen (شاشة النشاطات)

**UI Context:** Activities List with Filters (النشاطات)

This screen displays the user's activities/sessions with statistics and time-based filtering (Weekly, Monthly, Yearly).

### 3.1. Activities Statistics API (إحصائيات النشاطات)
Provides the aggregated numbers at the top of the screen based on the selected filter. Can be a separate API or included in the main activities list API metadata.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/member/activities/statistics`
*   **Query Parameters:**
    *   `filter` (string): e.g., `weekly`, `monthly`, `yearly`
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "statistics": {
        "total_attendance": 43,
        "training_hours_formatted": "87 س",
        "training_hours": 87.5
      }
    }
    ```

### 3.2. Activities List API (قائمة النشاطات)
Provides the paginated list of sessions based on the selected time filter.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/member/activities`
*   **Query Parameters:**
    *   `filter` (string): e.g., `weekly`, `monthly`, `yearly`
    *   `page` (integer): For pagination (e.g., 1, 2)
*   **Response (Success - 200 OK):**
    ```json
    {
      "data": [
        {
          "id": 201,
          "title": "جلسة تدريب القوة",
          "date_day_month": "02 يونيو",
          "time_string": "اليوم : 8 صباحاً",
          "duration_string": "1.5 ساعة"
        },
        {
          "id": 202,
          "title": "جلسة تدريب القوة",
          "date_day_month": "02 يونيو",
          "time_string": "اليوم : 8 صباحاً",
          "duration_string": "1.5 ساعة"
        }
      ],
      "meta": {
        "current_page": 1,
        "last_page": 5,
        "total": 50
      }
    }
    ```

---

## 4. Notifications Screen (شاشة الإشعارات)

**UI Context:** List of user notifications (الإشعارات)

This screen lists system notifications, alerts, and reminders for the user.

### 4.1. Notifications List API (قائمة الإشعارات)
Fetches the paginated list of notifications.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/notifications`
*   **Query Parameters:**
    *   `page` (integer): For pagination (e.g., 1, 2)
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "data": [
        {
          "id": "notif-1",
          "title": "عرض خاص",
          "body": "خصم 20% على الاشتراك السنوي حتى نهاية الشهر.",
          "type": "offer",
          "time_ago": "قبل ساعة",
          "is_read": true
        },
        {
          "id": "notif-2",
          "title": "اشتراكك سينتهي قريبا",
          "body": "بقي 4 أيام لانتهاء الاشتراك الشهري vip",
          "type": "warning",
          "time_ago": "قبل ساعة",
          "is_read": false
        },
        {
          "id": "notif-3",
          "title": "فاتورة متأخرة",
          "body": "لديك فاتورة متأخرة بقيمة 300 ر.س.",
          "type": "error",
          "time_ago": "قبل ساعة",
          "is_read": true
        }
      ],
      "meta": {
        "current_page": 1,
        "last_page": 3,
        "unread_count": 1
      }
    }
    ```
    *Note: `type` field determines the icon (offer/tag, warning/triangle, error/circle-exclamation, success/check, reminder/bell).*

### 4.2. Mark Notification as Read API (تحديد كمقروء)
Triggered when a user clicks on an unread notification.

*   **Method:** `PUT` (or `POST` / `PATCH`)
*   **Endpoint:** `/api/v1/notifications/{id}/read`
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "message": "Notification marked as read successfully."
    }
    ```

### 4.3. Mark All as Read API (تحديد الكل كمقروء)
(Optional) Used if there's a "Read All" button or when opening the screen to mark everything as read.

*   **Method:** `POST`
*   **Endpoint:** `/api/v1/notifications/read-all`
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "message": "All notifications marked as read."
    }
    ```

---

## 5. My Subscription Screen (شاشة اشتراكي)

**UI Context:** Current Subscription Details and Renewal (اشتراكي)

This screen displays detailed information about the user's current active subscription and allows them to renew it.

### 5.1. Subscription Details API (تفاصيل الاشتراك الحالي)
Fetches the active subscription details, usage stats, and assigned trainer.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/member/subscription/current`
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "status": "active",
      "status_label": "نشط",
      "plan_name": "اشتراك شهري VIP",
      "price_formatted": "344$/شهر",
      "start_date": "12/23/2026",
      "end_date": "12/23/2026",
      "sessions_total": 24,
      "sessions_used": 10,
      "sessions_remaining": 23,
      "usage_progress_label": "10/25 حصة",
      "trainer": {
        "id": 5,
        "name": "الكابتن دانيا مولوي",
        "title": "المدرب المعتمد",
        "avatar_url": "https://..."
      }
    }
    ```

### 5.2. Renew Subscription API (تجديد الاشتراك)
Triggered when the user clicks "تجديد الاشتراك". It usually creates a new payment intent or redirects to checkout.

*   **Method:** `POST`
*   **Endpoint:** `/api/v1/member/subscription/renew`
*   **Headers:** `Authorization: Bearer {token}`
*   **Request Payload:**
    ```json
    {
      "plan_id": 12  // Optional if renewing the exact same plan, but good practice
    }
    ```
*   **Response (Success - 200 OK):**
    ```json
    {
      "checkout_url": "https://payment-gateway.com/checkout/...",
      "payment_intent_id": "pi_12345..."
    }
    ```

---

## 6. Plans/Renew Subscription Screen (شاشة خطط التجديد)

**UI Context:** List of available subscription plans for renewal (تجديد الاشتراك)

This screen lists all available packages the user can purchase to renew or upgrade their subscription.

### 6.1. Subscription Plans List API (قائمة خطط الاشتراك)
Fetches all available plans with their features and pricing.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/plans` (or `/api/v1/subscriptions/plans`)
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "data": [
        {
          "id": 1,
          "name": "خطة حصص",
          "price_formatted": "34$",
          "price": 34,
          "currency": "USD",
          "sessions_label": "1 حصة",
          "duration_label": "ـــ",
          "features": [
            "دخول واحد",
            "بدون التزام",
            "صلاحية 30 يوم"
          ],
          "is_recommended": false
        },
        {
          "id": 2,
          "name": "خطة شهري",
          "price_formatted": "34$",
          "sessions_label": "1 حصة",
          "duration_label": "4 أيام",
          "features": [
            "جميع الاجهزة",
            "خزانة شخصية",
            "تقارير اسبوعية"
          ],
          "is_recommended": false
        },
        {
          "id": 3,
          "name": "خطة سنوي",
          "price_formatted": "34$",
          "sessions_label": "1 حصة",
          "duration_label": "5 ساعات",
          "features": [
            "وفر 20%",
            "حصص جماعية",
            "تجميد مجاني"
          ],
          "is_recommended": true
        },
        {
          "id": 4,
          "name": "خطة VIP",
          "price_formatted": "34$",
          "sessions_label": "1 حصة",
          "duration_label": "7 ساعات",
          "features": [
            "جميع الاجهزة",
            "خزانة شخصية",
            "تقارير اسبوعية"
          ],
          "is_recommended": false
        }
      ]
    }
    ```

### 6.2. Submit Renewal Request API (طلب التجديد)
Triggered when the user selects a plan and clicks "طلب التجديد".

*   **Method:** `POST`
*   **Endpoint:** `/api/v1/member/subscription/renew` (or `/api/v1/checkout/initialize`)
*   **Headers:** `Authorization: Bearer {token}`
*   **Request Payload:**
    ```json
    {
      "plan_id": 3
    }
    ```
*   **Response (Success - 200 OK):**
    ```json
    {
      "checkout_url": "https://payment-gateway.com/checkout/...",
      "payment_intent_id": "pi_12345..."
    }
    ```

---

## 7. Subscription Details Screen (شاشة تفاصيل الاشتراك)

**UI Context:** Detailed view of a specific subscription (تفاصيل الاشتراك)

This screen shows the comprehensive details of a single subscription, including its identifier, dates, and session usage.

### 7.1. Subscription Full Details API (تفاصيل الاشتراك الكاملة)
Fetches the complete details for a given subscription ID.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/member/subscriptions/{id}`
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "subscription_number": "7698494-593572032",
      "plan_type_name": "اشتراك شهري vip",
      "price_formatted": "564$",
      "start_date": "12/2/2026",
      "end_date": "14/3/2026",
      "status": "active",
      "status_label": "نشط",
      "used_sessions": 10,
      "remaining_sessions": 18,
      "trainer_name": "دانيا مولوي"
    }
    ```

---

## 8. Access QR Code Screen (شاشة رمز الدخول)

**UI Context:** Dynamic QR code for gym check-in/check-out (رمز الدخول)

This screen displays a QR code that the user scans at the gym gates to register attendance. It also shows a quick overview of their current status.

### 8.1. Access QR Code Data API (بيانات رمز الدخول)
Fetches the dynamic QR code string (often a JWT) and the user's current basic status.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/attendance/qr`
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "user_name": "نايا الأسعد",
      "plan_name": "اشتراك شهري vip",
      "avatar_url": "https://...",
      "qr_code_data": "eyJhbGciOiJIUzI1NiIsInR5c...",
      "status": "active",
      "status_label": "نشط",
      "remaining_sessions": 4
    }
    ```
    *Note: `qr_code_data` is the string that the mobile app will encode into the visual QR code image, or it could be a base64 encoded image if generated on the server.*

---

## 9. Change Password Screen (شاشة تغيير كلمة المرور)

**UI Context:** A form to update the user's password (تغيير كلمة المرور)

This screen allows an authenticated user to change their current password.

### 9.1. Change Password API (تغيير كلمة المرور)
Triggered when the user clicks "حفظ التغيرات" (Save Changes).

*   **Method:** `PUT` (or `POST`)
*   **Endpoint:** `/api/v1/member/profile/password` (or `/api/v1/auth/password/change`)
*   **Headers:** `Authorization: Bearer {token}`
*   **Request Payload:**
    ```json
    {
      "current_password": "old_password123",
      "new_password": "new_password456",
      "new_password_confirmation": "new_password456"
    }
    ```
*   **Response (Success - 200 OK):**
    ```json
    {
      "message": "Password changed successfully."
    }
    ```
*   **Response (Error - 422 Unprocessable Entity):** If the `current_password` is incorrect, or `new_password` doesn't match `new_password_confirmation`.

---

## 10. Settings Screen (شاشة الإعدادات)

**UI Context:** Application settings and profile management links (الإعدادات)

This screen provides links to update profile information, change passwords, adjust app preferences, and log out.

### 10.1. Update Preferences API (تحديث تفضيلات التطبيق)
Triggered when the user toggles notifications or changes the language.

*   **Method:** `PUT` (or `PATCH`)
*   **Endpoint:** `/api/v1/member/preferences`
*   **Headers:** `Authorization: Bearer {token}`
*   **Request Payload:**
    ```json
    {
      "language": "ar",
      "notifications_enabled": true
    }
    ```
*   **Response (Success - 200 OK):**
    ```json
    {
      "message": "Preferences updated successfully."
    }
    ```

### 10.2. Logout API (تسجيل الخروج)
Triggered when the user clicks "تسجيل الخروج".

*   **Method:** `POST`
*   **Endpoint:** `/api/v1/auth/logout`
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "message": "Successfully logged out."
    }
    ```

---

## 11. Profile Screen (شاشة الملف الشخصي)

**UI Context:** Displaying user profile details and settings links (الملف الشخصي)

This screen shows the user's avatar, name, VIP status, and personal details like phone number, address, weight, age, gender, and health status.

### 11.1. Get Profile Details API (تفاصيل الملف الشخصي)
Fetches the detailed profile information for the authenticated user.

*   **Method:** `GET`
*   **Endpoint:** `/api/v1/member/profile` (or `/api/v1/me/details`)
*   **Headers:** `Authorization: Bearer {token}`
*   **Response (Success - 200 OK):**
    ```json
    {
      "name": "نايا الأسعد",
      "avatar_url": "https://...",
      "has_vip_badge": true,
      "subscription_tier": "VIP",
      "phone_number": "+963 65436890",
      "address": "سوريا-حلب",
      "weight_kg": 24,
      "age": 25,
      "gender": "female",
      "gender_label": "أنثى",
      "medical_history": "لاتوجد أمراض مزمنة"
    }
    ```
