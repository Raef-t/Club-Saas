# Mobile Application API Contracts (Implementation Reference)

This document contains the backend API contracts implemented for the mobile application user interface. It serves as an implementation reference accurately reflecting the backend structure rather than the original UI-derived proposal.

---

## Screen 1: Member Dashboard (Home Screen)

### 1. Unified Dashboard Endpoint

**Status:** Implemented by modifying an existing API

*   **Implemented As:** `GET /api/v1/member/dashboard`
*   **Authentication:** Required (Bearer Token / Sanctum)

**Location:**
*   **Module:** MemberManager
*   **Route:** `Modules/MemberManager/routes/api.php`
*   **Controller:** `MemberDashboardController@index`
*   **Service:** `Me\MemberDashboardService`

#### Response Payload (`200 OK`):
```json
{
  "status": "success",
  "data": {
    "profile": {
      "first_name": "نايا",
      "full_name": "نايا الأسعد",
      "avatar_url": "https://api.clubsaas.com/storage/avatars/naya.jpg"
    },
    "subscription_card": {
      "status": "active",
      "plan_name": "باقة vip",
      "end_date": "2026-06-23",
      "formatted_end_date": "23/6/2026",
      "membership_number": "23455667",
      "price": 345.00,
      "formatted_price": "345$"
    },
    "stats": {
      "remaining_sessions": 60,
      "total_attendance": 34,
      "training_hours": 50,
      "last_payment": 543.00,
      "formatted_last_payment": "543$"
    },
    "recent_activities": [
      {
        "id": 102,
        "title": "جلسة تدريب القوة",
        "description": "08:00",
        "duration": 1.5,
        "duration_label": "1.5 hours",
        "created_at": "2026-06-10T08:00:00Z"
      }
    ],
    "upcoming_events": [
      {
        "id": 501,
        "title": "chest day",
        "exercises_count": 8,
        "duration_minutes": 45,
        "calories": 2300,
        "available_spots": 5
      }
    ]
  }
}
```

---

## 2. Interaction & Navigation Endpoints

#### A. Member Access QR Code (Screen 7)

**Status:** Implemented by modifying an existing API

*   **Implemented As:** `GET /api/v1/attendance/qr`
*   **Authentication:** Required

**Location:**
*   **Module:** AttendanceManager
*   **Route:** `Modules/AttendanceManager/routes/api.php`
*   **Controller:** `QRController@show`
*   **Service:** `QRSecurityService`

*   **Response Payload (`200 OK`):**
    ```json
    {
      "status": "success",
      "data": {
        "member_name": "نايا الأسعد",
        "plan_name": "اشتراك شهري vip",
        "avatar_url": "https://api.clubsaas.com/storage/avatars/naya.jpg",
        "qr_code_value": "eyJpdiI6...",
        "expires_in_seconds": 30,
        "remaining_sessions": 4,
        "is_inside_facility": true
      }
    }
    ```

#### B. Activities & Attendance Page (Screen 2)

**Status:** Implemented by modifying an existing API (added new route and method)

*   **Implemented As:** `GET /api/v1/attendance/my-activities`
*   **Authentication:** Required
*   **Query Parameters:**
    *   `period` (optional): `weekly` | `monthly` | `yearly` (default: `weekly`)
    *   `per_page` (optional): integer (default: `15`)

**Location:**
*   **Module:** AttendanceManager
*   **Route:** `Modules/AttendanceManager/routes/api.php`
*   **Controller:** `MemberAttendanceController@myActivities`

##### Response Payload (`200 OK`):
```json
{
  "status": "success",
  "data": {
    "stats": {
      "total_attendance": 43,
      "training_hours": 87.0
    },
    "items": [
      {
        "id": 1056,
        "title": "Training Session",
        "date": "2026-06-02",
        "day": "02",
        "month": "يونيو",
        "time_label": "08:00 AM",
        "duration_hours": 1.5,
        "duration_label": "1.5 hours"
      }
    ],
    "pagination": {
      "total": 43,
      "per_page": 15,
      "current_page": 1,
      "last_page": 3
    }
  }
}
```

#### C. Payments & Invoices (paginated)

**Status:** Implemented as a new API

*   **Implemented As:** `GET /api/v1/my-invoices`
*   **Authentication:** Required

**Location:**
*   **Module:** SubscriptionManager
*   **Route:** `Modules/SubscriptionManager/routes/api.php`
*   **Controller:** `InvoiceController@myInvoices`
*   **Resource:** `InvoiceResource`
 
*   **Response Payload (`200 OK`):**
    ```json
    {
      "status": "success",
      "data": [
        {
          "invoice_id": 988,
          "amount": 543.00,
          "formatted_amount": "$543",
          "status": "paid",
          "payment_method": "cash",
          "paid_at": "2026-06-01",
          "created_at": "2026-06-01"
        }
      ]
    }
    ```

#### D. Trainers / Coaches List

**Status:** Reused from an existing API

*   **Implemented As:** `GET /api/v1/staff?role=coach`
*   **Authentication:** Required

**Location:**
*   **Module:** StaffManager
*   **Route:** `Modules/StaffManager/routes/api.php`
*   **Controller:** `StaffController@index`

#### E. Upcoming Workouts / Classes

**Status:** Reused from an existing API

*   **Implemented As:** `GET /api/v1/sessions`
*   **Authentication:** Required

**Location:**
*   **Module:** Sports
*   **Route:** `Modules/Sports/routes/api.php`
*   **Controller:** `SessionController@index`

#### F. Notifications List (Screen 3)

**Status:** Implemented by modifying an existing API

*   **Implemented As:** `GET /api/v1/my-notifications`
*   **Authentication:** Required

**Location:**
*   **Module:** NotificationManager
*   **Route:** `Modules/NotificationManager/routes/api.php`
*   **Controller:** `NotificationLogController@myNotifications`
*   **Resource:** `NotificationLogResource`

*   **Response Payload (`200 OK`):**
    ```json
    {
      "status": "success",
      "data": {
        "items": [
          {
            "id": 401,
            "type": "offer",
            "type_label": "عرض خاص",
            "body": "خصم 20% على الاشتراك السنوي حتى نهاية الشهر.",
            "is_read": false,
            "created_at": "2026-06-10T09:30:00Z",
            "formatted_time": "1 hour ago",
            "sent_at": "2026-06-10 09:30:00"
          }
        ],
        "pagination": { ... }
      }
    }
    ```

*   **Action Endpoint - Mark Specific Notification as Read:**
    **Status:** Implemented as a new API
    *   **Implemented As:** `POST /api/v1/my-notifications/{id}/read`
    *   **Controller:** `NotificationLogController@markAsRead`

*   **Action Endpoint - Mark All Notifications as Read:**
    **Status:** Implemented as a new API
    *   **Implemented As:** `POST /api/v1/my-notifications/read-all`
    *   **Controller:** `NotificationLogController@markAllAsRead`

#### G. Subscription Details (Screen 4)

**Status:** Reused from an existing API

*   **Implemented As:** `GET /api/v1/player-subscriptions?member_id={id}&status=active`
*   **Authentication:** Required

**Location:**
*   **Module:** SubscriptionManager
*   **Route:** `Modules/SubscriptionManager/routes/api.php`
*   **Controller:** `PlayerSubscriptionController@index`

#### H. Full Subscription Details (Screen 5)

**Status:** Implemented by modifying an existing API (enhanced resource)

*   **Implemented As:** `GET /api/v1/player-subscriptions/{id}`
*   **Authentication:** Required

**Location:**
*   **Module:** SubscriptionManager
*   **Route:** `Modules/SubscriptionManager/routes/api.php`
*   **Controller:** `PlayerSubscriptionController@show`
*   **Resource:** `PlayerSubscriptionResource`

*   **Response Payload (`200 OK`):** Includes enhanced fields like `coach_id`, `total_amount`, `remaining_amount`, `notes`, and nested `items` array to track session usage.

#### I. Subscription Renewal Selection & Request (Screen 6)

*   **Endpoint 1: Fetch Available Renewal Plans**
    **Status:** Reused from an existing API
    *   **Implemented As:** `GET /api/v1/subscription-plans`
    *   **Authentication:** Required
    **Location:**
    *   **Module:** SubscriptionManager
    *   **Controller:** `SubscriptionPlanController@index`

*   **Endpoint 2: Submit Renewal Request**
    **Status:** Reused from an existing API
    *   **Implemented As:** `POST /api/v1/player-subscriptions/{id}/renew`
    *   **Authentication:** Required
    **Location:**
    *   **Module:** SubscriptionManager
    *   **Controller:** `PlayerSubscriptionController@renew`

#### J. Settings Page (Screen 8) & Change Password (Screen 9)

*   **Endpoint 1: Update App Preferences**
    **Status:** Implemented as a new API
    *   **Implemented As:** `PUT /api/v1/me/preferences`
    *   **Authentication:** Required
    **Location:**
    *   **Module:** MemberManager
    *   **Route:** `Modules/MemberManager/routes/api.php`
    *   **Controller:** `MePreferencesController@update`

*   **Endpoint 2: Change Password**
    **Status:** Implemented as a new API
    *   **Implemented As:** `POST /api/v1/auth/change-password`
    *   **Authentication:** Required
    **Location:**
    *   **Module:** Authentication
    *   **Route:** `Modules/Authentication/routes/api.php`
    *   **Controller:** `AuthController@changePassword`

*   **Endpoint 3: Log Out**
    **Status:** Reused from an existing API
    *   **Implemented As:** `POST /api/v1/auth/logout`
    *   **Authentication:** Required
    **Location:**
    *   **Module:** Authentication
    *   **Controller:** `AuthController@logout`

#### K. Member Profile (Screen 10)

**Status:** Implemented by modifying an existing API (enhanced resource)

*   **Implemented As:** `GET /api/v1/auth/me`
*   **Authentication:** Required

**Location:**
*   **Module:** Authentication
*   **Route:** `Modules/Authentication/routes/api.php`
*   **Controller:** `AuthController@me`

*   **Response Payload (`200 OK`):** Enriched to include member status, latest `measurements` (weight, bmi, height), `age`, and `health_status`.

---

## 3. Database Schema Mapping
The API contracts map to the database tables as follows:

| UI Element / Field | DB Table | DB Column(s) / Logic |
| :--- | :--- | :--- |
| **User Greeting / Photo** | `people` | `full_name`, `photo_url` (linked via `members.person_id` -> `people.id`) |
| **Subscription Expiry** | `player_subscriptions` | `end_date` |
| **Subscription Status** | `player_subscriptions` | `status` |
| **Plan / Package Name** | `subscription_plans` | `name` |
| **Membership ID** | `members` | `member_number` |
| **Subscription Price** | `subscription_plans` | `base_price` |
| **Remaining Sessions** | `player_subscriptions` | `remaining_sessions` |
| **Total Attendance** | `attendances` | `COUNT(id)` where `attendable_type = 'player_subscription'` |
| **Training Hours** | `attendances` | `SUM(duration_hours)` derived from `check_in_at` and `check_out_at` |
| **Last Payment** | `payments` | `amount` of the latest payment linked to the member's invoice |
| **Recent Activities** | `attendances` | Query returning recent logs ordered by date |
| **Upcoming Events** | `sports_sessions` / `activities` | Scheduled sessions with start time in the future |
| **QR Code Value** | `members` | Dynamically generated short-lived AES token via `QRSecurityService` |
| **Notifications** | `notification_logs` | Recipient-filtered logs for the member |
| **Subscription Usage** | `player_subscription_items` | `sessions_allocated` & `sessions_consumed` |
| **Assigned Trainer** | `staff` | Linked coach's details via `coach_id` on the subscription |
| **User Preferences** | `player_profiles` | JSON column `preferences` stores language and notification settings |
| **User Password** | `authentication_users` | Updated via Auth module securely |
| **Member Weight Measurement**| `member_measurements`| `weight` column tracked for physical profile details |
