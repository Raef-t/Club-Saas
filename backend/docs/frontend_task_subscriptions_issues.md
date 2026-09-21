# مهام وتعديلات الواجهة الأمامية (Frontend Tasks Specification)
## نظام الاشتراكات والتقارير واشتراكات الأجهزة الخاصة (Private Equipment Subscriptions)

تم إعداد هذا الدليل التقني الموجه لفريق الواجهة الأمامية (Frontend Team) لتوضيح التعديلات المطلوبة وتحديثات الـ API الخاصة باشتراكات الأجهزة الخاصة، والتقارير المالية، وأسماء المدربين، والحسابات المالية.

---

### 1. حل مشكلة ظهور اسم الكوتش `undefined undefined`

#### **المشكلة السابقة:**
في اشتراكات الأجهزة الخاصة (`is_private_equipment = true`)، كان اسم الكوتش يظهر في الواجهة كـ `undefined undefined` بينما يظهر في الجلسات الجماعية بشكل سليم. كان السبب هو محاولة قراءة `coach.first_name + ' ' + coach.last_name` بينما كان الحقل يعود أحياناً كنص `coach_name` فقط أو كائن غير متطابق.

#### **تحديث الـ Backend:**
أصبح الـ API (سواء في تقرير كافة الاشتراكات `AllSubscriptionsReport` أو في `PlayerSubscriptionResource`) يُرجع الكائن الكامل والمفصل للمدرب داخل كل عنصر (`item`):

```json
{
  "item_id": 123,
  "activity_name": "تدريب خاص أجهزة",
  "coach_name": "آية مزور",
  "coach": {
    "id": 5,
    "name": "آية مزور",
    "full_name": "آية مزور",
    "first_name": "آية",
    "last_name": "مزور"
  }
}
```

#### **المطلوب من الفرونت (Frontend Implementation):**
استخدام دالة مساعدة (Helper function) لضمان عدم ظهور `undefined` تحت أي ظرف:

```typescript
export function getCoachDisplayName(item: any): string {
  if (!item) return 'لا يوجد مدرب';
  
  if (item.coach) {
    if (item.coach.full_name) return item.coach.full_name;
    if (item.coach.name) return item.coach.name;
    if (item.coach.first_name || item.coach.last_name) {
      return `${item.coach.first_name || ''} ${item.coach.last_name || ''}`.trim();
    }
  }
  
  if (item.coach_name) return item.coach_name;
  
  return 'لا يوجد مدرب مسند';
}
```

---

### 2. إظهار رقم هاتف المشترك (Member Phone)

#### **المشكلة السابقة:**
رقم هاتف المشترك كان متوفراً في استجابة الـ API ولكن العمود كان فارغاً في جدول الاشتراكات.

#### **تحديث الـ Backend:**
الـ API يوفر رقم الهاتف مباشرة في الحقول التالية:
- في تقرير كافة الاشتراكات: `record.member_phone`
- في تفاصيل الاشتراك `PlayerSubscriptionResource`: `record.member.person.phone` أو `record.member_phone`
- مصفوفة جهات الاتصال المفصلة: `record.member_contacts`

#### **المطلوب من الفرونت (Frontend Implementation):**
في عمود جدول المشتركين، اربط القيمة بالشكل التالي:

```tsx
<Table.Column
  title="رقم الهاتف"
  dataIndex="member_phone"
  key="member_phone"
  render={(_, record) => (
    <span>
      {record.member_phone || record.member?.person?.phone || '-'}
    </span>
  )}
/>
```

---

### 3. إظهار اسم الحساب / الصندوق (Account Name & Safe Name)

#### **المشكلة السابقة:**
لم يكن عمود "اسم الحساب" أو "اسم الصندوق" معروضاً في جدول الاشتراكات عند تحصيل الدفعات.

#### **تحديث الـ Backend:**
تمت إضافة حقول اسم الحساب والخزينة تلقائياً في استجابة كل اشتراك:
- `account_name`: اسم الحساب المالي في شجرة الحسابات (مثل: "صندوق الصالة الرئيسي").
- `safe_name`: اسم الخزينة / الصندوق الفعلي (مثل: "خزينة الصالة").

```json
{
  "subscription_id": 149,
  "member_name": "روان مزكتلي",
  "account_name": "صندوق الصالة الرئيسي",
  "safe_name": "خزينة الصالة",
  "total_amount": 500,
  "paid_amount": 500,
  "remaining_amount": 0
}
```

#### **المطلوب من الفرونت (Frontend Implementation):**
إضافة عمود "اسم الحساب / الصندوق" في جدول الاشتراكات:

```tsx
<Table.Column
  title="اسم الحساب"
  key="account_name"
  render={(_, record) => (
    <div>
      <span className="font-semibold">{record.account_name || record.safe_name || 'غير محدد'}</span>
      {record.safe_name && record.account_name && record.safe_name !== record.account_name && (
        <small className="text-gray-500 block">({record.safe_name})</small>
      )}
    </div>
  )}
/>
```

---

### 4. واجهة دفع واشتراك الأجهزة الخاصة (Private Equipment)

#### **فهم منطق العمل (Business Logic):**
في خطط الأجهزة الخاصة (`is_private_equipment = true`):
- سعر الاشتراك ينقسم إلى:
  - **سعر النادي / الصالة (`branch_price`):** يدخل في صندوق النادي (`safe_id = 2`).
  - **سعر الكوتش (`coach_price`):** يستلمه الكوتش ولا يدخل صندوق النادي أبداً (`safe_id = null`).
- إذا دفع المشترك سعر النادي فقط، أو دفع المبلغين معاً، يجب أن تعكس الواجهة ذلك بدقة دون اعتبار الاشتراك "مدفوعاً جزئياً" إذا كان قد سدد التزامه كاملاً.

#### **Payload المطلوب عند إنشاء الاشتراك أو السداد:**
عند إرسال طلب إنشاء الاشتراك أو إضافة دفعة لاشتراك خاص، يرجى إرسال التوزيع المالي:

```json
{
  "member_id": 149,
  "plan_id": 32,
  "start_date": "2026-09-08",
  "months_count": 1,
  "paid_amount": 500.00,
  "branch_paid_amount": 275.00,
  "coach_paid_amount": 225.00,
  "safe_id": 2,
  "branch_receipt_number": "REC-112",
  "coach_receipt_number": "REC-COACH-11"
}
```

---

### 5. استثناء "الدخول اليومي" (Daily Entry) من الاشتراكات المنتهية

#### **تحديث الـ Backend:**
- استبعد الـ Backend تلقائياً بطاقات الدخول اليومي (`is_daily_entry = true`) من تقرير "الاشتراكات المنتهية التي لم تجدد" (`RenewalReportService`).
- تم تزويد كل سجل بالخاصية البوليانية `is_daily_entry: boolean`.
- كما يدعم فلتر تقرير الاشتراكات المعامل: `exclude_daily_entry=true`.

#### **المطلوب من الفرونت (Frontend Implementation):**
- يمكن للفرونت تمييز بطاقات الدخول اليومي بـ Badge خاص (`دخول يومي`) بجانب نوع الخطة.
- في حال توفير فلتر في شريط التصفية، يمكن تمرير `exclude_daily_entry=true` لإخفاء الدخول اليومي عند طلب استعراض اشتراكات العضويات الدورية فقط.
