# مهمة فريق الفرونت إند: ميزة تطبيق وحساب نسبة الحسم لاشتراكات الأعضاء تلقائياً
# Frontend Task Ticket: Subscription Discount & Automatic Rate Calculation

---

## 📌 1. نظرة عامة والهدف (Overview & Goal)
تمكين موظفة الاستقبال (أو المستخدم المخول) أثناء تسجيل اشتراك جديد لعضو أو تعديل اشتراك قائم من تطبيق **حسم (Discount)**، بحيث:
- تختار الموظفة خيار **"مع حسم"** (Checkbox).
- يظهر حقلان متكاملان ومترابطان حسابياً:
  1. **السعر بعد الحسم / السعر المدفوع المطلوب** (مثلاً السعر الأصلي 300,000 ليرة، وأدخلت الموظفة 150,000 ليرة).
  2. **نسبة الحسم %** (يتم احتسابها تلقائياً لتصبح 50%).
- الحساب يتم تلقائياً بالاتجاهين (Two-Way Calculation):
  - إذا أدخلت السعر بعد الحسم، تُحسب النسبة تلقائياً.
  - إذا أدخلت النسبة، يُحسب السعر بعد الحسم تلقائياً.
- إرسال نسبة الحسم وتفاصيلها إلى الباك إند ليتم تثبيتها في قاعدة البيانات وحساب الفاتورة والمبلغ الصافي والمتبقي وأنصبة الإيرادات بدقة تامة.

---

## 🖥️ 2. سلوك الواجهة وتجربة المستخدم (UI/UX Requirements)

### أ. في نافذة إنشاء اشتراك جديد (Subscribe Member Modal / Form)
1. **عرض السعر الأصلي للخطة (Base Price)**:
   - عند اختيار الخطة وعدد الأشهر، يظهر السعر الأصلي:
     `السعر الأصلي = plan.base_price * months_count`
2. **خانة تفعيل الحسم (Checkbox)**:
   - اسم الحقل: `مع حسم` أو `تطبيق حسم`.
   - القيمة: `is_discount` (Boolean: `true` / `false`).
   - افتراضياً: غير مفعل (`false`).
3. **عند تفعيل خيار الحسم (`is_discount = true`)**:
   - يظهر قسم الحسم ويحتوي على:
     - **حقل السعر النهائي بعد الحسم (`discounted_price` / `final_price`)**:
       - إمكانية الكتابة فيه مباشرة (مثال: إدخال 150000).
     - **حقل نسبة الحسم % (`discount_percentage`)**:
       - يظهر النسبة المئوية تلقائياً (مثال: 50%).
       - يمكن أيضاً للموظفة إدخال النسبة مباشرة (مثلاً كتابة 20% فيتحول السعر إلى 240000).
     - **حقل مبلغ الحسم (`discount_amount`)** (اختياري للعرض أو الإدخال):
       - يوضح للموظفة مقدار التوفير المالي (مثلاً: 150,000 ل.س).
     - **حقل سبب الحسم (`discount_reason`)** (نص اختياري):
       - مثال: "حسم خاص لمشتركة قديمة"، "عرض عيد الأضحى"، "حسم نقابي".
4. **حقل المبلغ المدفوع (`paid_amount`)**:
   - يتم تحديث القيمة الافتراضية المقترحة للمبلغ المدفوع لتساوي **السعر بعد الحسم** (وليس السعر الأصلي).
   - يمكن للموظفة تقليل المبلغ المدفوع إذا كان مسموحاً بالتقسيط في الفرع (مثلاً دفع 100,000 ويبقى 50,000).

---

## 🧮 3. معادلات الحساب التلقائي بالـ JavaScript (Frontend Formulas)

```javascript
// 1. حساب السعر الإجمالي الأصلي قبل الحسم
const originalTotal = (plan.base_price || 0) * (months_count || 1);

// 2. إذا قامت الموظفة بإدخال السعر النهائي بعد الحسم (مثلاً 150,000 من أصل 300,000)
function handleFinalPriceChange(enteredPrice) {
  const finalPrice = Math.max(0, Math.min(originalTotal, Number(enteredPrice) || 0));
  const discountAmount = originalTotal - finalPrice;
  
  // حساب نسبة الحسم تلقائياً
  const discountPercentage = originalTotal > 0 
    ? Number(((discountAmount / originalTotal) * 100).toFixed(2)) 
    : 0;

  // تحديث حالة الفورم
  setFormValues((prev) => ({
    ...prev,
    final_price: finalPrice,
    discount_amount: discountAmount,
    discount_percentage: discountPercentage,
    paid_amount: finalPrice, // تعيين المدفوع افتراضياً للسعر الصافي
  }));
}

// 3. إذا قامت الموظفة بإدخال نسبة الحسم مباشرة (مثلاً 20%)
function handleDiscountPercentageChange(enteredPercentage) {
  const percentage = Math.max(0, Math.min(100, Number(enteredPercentage) || 0));
  
  // حساب مبلغ الحسم والسعر النهائي تلقائياً
  const discountAmount = Number(((originalTotal * percentage) / 100).toFixed(2));
  const finalPrice = Math.max(0, originalTotal - discountAmount);

  // تحديث حالة الفورم
  setFormValues((prev) => ({
    ...prev,
    discount_percentage: percentage,
    discount_amount: discountAmount,
    final_price: finalPrice,
    paid_amount: finalPrice,
  }));
}

// 4. في حال إلغاء تفعيل خيار الحسم (is_discount = false)
function handleToggleDiscount(checked) {
  setFormValues((prev) => ({
    ...prev,
    is_discount: checked,
    discount_percentage: 0,
    discount_amount: 0,
    discount_reason: '',
    final_price: originalTotal,
    paid_amount: originalTotal,
  }));
}
```

---

## 🌐 4. مواصفات الـ API والتكامل (API Specifications)

### 1) إنشاء اشتراك جديد (Create Subscription)
- **Method**: `POST`
- **URL**: `/v1/player-subscriptions`
- **Request Body (JSON Example)**:

```json
{
  "member_id": 47,
  "plan_id": 83,
  "months_count": 1,
  "start_date": "2026-09-15",
  "end_date": "2026-10-15",
  "paid_amount": 150000,
  "is_discount": true,
  "discount_percentage": 50,
  "discount_amount": 150000,
  "discount_reason": "حسم خاص للمشتركة",
  "payment_method": "cash",
  "receipt_number": "REC-2026-09-001",
  "currency": "SYP",
  "notes": "تم تطبيق حسم موظفة الاستقبال"
}
```

> [!TIP]
> الباك إند مرن جداً: حتى لو تم إرسال `discount_percentage: 50` فقط، أو تم إرسال `discount_amount: 150000` فقط، سيقوم الباك إند بالتحقق والحساب بدقة متناهية. ومع ذلك، يُفضل إرسال `is_discount: true` و `discount_percentage` و `discount_amount`.

---

### 2) تعديل اشتراك قائم (Update Subscription)
- **Method**: `PUT`
- **URL**: `/v1/player-subscriptions/{id}`
- **Request Body (JSON Example)**:

```json
{
  "reason": "تعديل نسبة الحسم والمبلغ المدفوع",
  "is_discount": true,
  "discount_percentage": 20,
  "discount_amount": 60000,
  "discount_reason": "تعديل الحسم إلى 20%",
  "paid_amount": 240000
}
```

---

### 3) استجابة الـ API (API Response - Data Structure)
في كل من `POST`, `PUT`, `GET /v1/player-subscriptions/{id}`، و `GET /v1/player-subscriptions`:

```json
{
  "status": "success",
  "message": "Member subscribed successfully",
  "data": {
    "id": 105,
    "subscription_number": "SUB-2026-105",
    "member": {
      "id": 47,
      "member_number": "MEM-0047",
      "person": {
        "full_name": "سارة الأحمد"
      }
    },
    "plan": {
      "id": 83,
      "name": "اشتراك شهري فتنس",
      "base_price": 300000.00,
      "currency": "SYP"
    },
    "months_count": 1,
    "start_date": "2026-09-15",
    "end_date": "2026-10-15",
    "status": "active",
    "status_label": "نشط",
    "original_total_amount": 300000.00,
    "is_discount": true,
    "discount_percentage": 50.00,
    "discount_amount": 150000.00,
    "discount_reason": "حسم خاص للمشتركة",
    "coach_discount_percentage": 50.00,
    "branch_discount_percentage": 50.00,
    "total_amount": 150000.00,
    "paid_amount": 150000.00,
    "remaining_amount": 0.00,
    "currency": "SYP",
    "currency_type": "SYP"
  }
}
```

---

## 🎨 5. إرشادات عرض الحسم في الواجهات والإيصالات (UI & Receipt Printing Guidelines)

1. **في بطاقة الاشتراك (Subscription Details Card / Table)**:
   - إذا كان `is_discount === true`:
     - إظهار شارة (Badge) مميزة: `حسم 50%`.
     - عرض السعر الأصلي مشطوباً: `~300,000 ل.س~`.
     - عرض السعر النهائي الصافي: `150,000 ل.س`.
     - إظهار حقل سبب الحسم في تلميح (Tooltip) أو نص فرعي.
2. **في وصل القبض / طباعة الإيصال (Receipt / Invoice Print)**:
   - إضافة أسطر الفاتورة:
     - **السعر الأصلي**: `300,000 ل.س`
     - **قيمة الحسم (50%)**: `- 150,000 ل.س`
     - **الصافي المستحق**: `150,000 ل.س`
     - **المبلغ المدفوع**: `150,000 ل.س`
     - **المتبقي**: `0 ل.س`

---

## ✅ 6. قائمة التحقق للاختبار لدى فريق الفرونت (Frontend Acceptance Checklist)
- [ ] عند فتح فورم الاشتراك، يكون خيار "مع حسم" غير مفعل والسعر يساوي سعر الخطة الأصلي.
- [ ] عند تفعيل "مع حسم"، تظهر حقول الحسم بسلاسة.
- [ ] عند إدخال السعر 150,000 وخطة الاشتراك 300,000، يتم حساب نسبة الحسم 50% وتظهر فوراً.
- [ ] عند إدخال نسبة 25%، يتم حساب السعر النهائي 225,000 فوراً.
- [ ] لا يُسمح بإدخال نسبة حسم سالبة أو أكبر من 100%.
- [ ] لا يُسمح بإدخال سعر نهائي بعد الحسم أكبر من السعر الأصلي أو أقل من صفر.
- [ ] عند إرسال الطلب، تظهر رسالة النجاح وتنعكس الحقول (`original_total_amount`, `discount_percentage`, `discount_amount`, `total_amount`) بشكل صحيح في الجدول وبطاقة العرض والإيصال.
