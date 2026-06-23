<?php

return [
    // العملة الافتراضية
    'default_currency' => 'USD',

    // العملات المدعومة
    'supported_currencies' => ['USD', 'SYP'],

    // معرف الصندوق الافتراضي
    'default_safe_id' => env('ACCOUNTING_DEFAULT_SAFE_ID', 1),

    // رموز الحسابات الأساسية في دليل الحسابات
    'cash_usd_account_code'  => '1101',
    'cash_syp_account_code'  => '1102',
    'revenue_account_code'   => '4100',
    'expense_account_code'   => '5100',

    // بادئات ترقيم السندات حسب النوع
    'journal_number_prefix' => [
        'JV' => 'JV',  // سند يومية
        'RV' => 'RV',  // سند قبض
        'PV' => 'PV',  // سند صرف
        'SI' => 'SI',  // فاتورة مبيعات
        'PI' => 'PI',  // فاتورة مشتريات
    ],

    // السندات غير قابلة للتعديل بعد الترحيل
    'immutable_after_posting' => true,

    // يُشترط وجود فترة محاسبية مفتوحة لإنشاء القيود
    'require_period_open' => true,
];
