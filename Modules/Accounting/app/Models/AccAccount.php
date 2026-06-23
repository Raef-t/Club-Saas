<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccAccount",
    title: "حساب شجرة الحسابات (دليل الحسابات)",
    description: "يمثل حساباً مالياً فريداً ضمن شجرة الحسابات (الأصول، الخصوم، حقوق الملكية، الإيرادات، المصروفات). يمكن أن يكون حساباً رئيسياً (مجمعاً) أو فرعياً (يقبل الحركات المالية والقيود).",
    properties: [
        new OA\Property(property: "id", type: "integer", description: "المعرف الفريد للحساب المالي", example: 1),
        new OA\Property(property: "code", type: "string", description: "رمز الحساب المحاسبي الفريد (مثال: 1101 للصناديق، 1101001 لصندوق رئيسي)", example: "1101001"),
        new OA\Property(property: "name", type: "string", description: "اسم الحساب باللغة العربية", example: "صندوق الصالة الرئيسي"),
        new OA\Property(property: "name_en", type: "string", description: "اسم الحساب باللغة الإنجليزية", nullable: true, example: "Main Safe Cash"),
        new OA\Property(property: "type", type: "string", enum: ["asset", "liability", "equity", "revenue", "expense"], description: "تصنيف الحساب الرئيسي (أصل، التزام، حقوق ملكية، إيراد، مصروف)", example: "asset"),
        new OA\Property(property: "currency", type: "string", enum: ["USD", "SYP", "BOTH"], description: "العملة المقبولة للحساب: دولار أمريكي، ليرة سورية، أو كلتا العملتين", example: "BOTH"),
        new OA\Property(property: "parent_id", type: "integer", description: "معرف الحساب الأب (في حال كان هذا الحساب فرعياً تحت حساب تجميعي)", nullable: true, example: 2),
        new OA\Property(property: "is_active", type: "boolean", description: "حالة تفعيل الحساب لاستقبال القيود والعمليات", example: true),
        new OA\Property(property: "allow_manual_entry", type: "boolean", description: "هل يسمح النظام للمستخدمين بإدخال قيود محاسبية يدوية على هذا الحساب (غالباً الحسابات الفرعية تسمح، والرئيسية التجميعية أو الصناديق المؤتمتة لا تسمح لمنع الأخطاء)", example: false),
        new OA\Property(property: "description", type: "string", description: "شرح تفصيلي لوظيفة الحساب المحاسبي وسياق استخدامه", nullable: true, example: "صندوق استلام الكاش المالي لاشتراكات الصالة الرياضية"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", description: "تاريخ إنشاء السجل", example: "2026-06-01T08:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", description: "تاريخ آخر تحديث للسجل", example: "2026-06-30T23:59:59Z")
    ]
)]
class AccAccount extends Model
{
    use HasFactory;

    protected $table = 'acc_accounts';

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'type',
        'currency',
        'parent_id',
        'is_active',
        'allow_manual_entry',
        'description',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'allow_manual_entry' => 'boolean',
    ];

    // ==============================
    // العلاقات
    // ==============================

    /** الحساب الأب (للحسابات الفرعية) */
    public function parent()
    {
        return $this->belongsTo(AccAccount::class, 'parent_id');
    }

    /** الحسابات الفرعية */
    public function children()
    {
        return $this->hasMany(AccAccount::class, 'parent_id');
    }

    /** جميع الحسابات الفرعية بشكل متكرر (Recursive) */
    public function allChildren()
    {
        return $this->hasMany(AccAccount::class, 'parent_id')->with('allChildren');
    }

    /** قيود اليومية المرتبطة بهذا الحساب */
    public function journalEntries()
    {
        return $this->hasMany(AccJournalEntry::class, 'account_id');
    }

    /** الصناديق المرتبطة بهذا الحساب */
    public function safes()
    {
        return $this->hasMany(AccSafe::class, 'account_id');
    }

    // ==============================
    // Scopes
    // ==============================

    /** Scope: الحسابات النشطة فقط */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Scope: تصفية حسب نوع الحساب */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /** Scope: الحسابات التي تسمح بالإدخال اليدوي */
    public function scopeManualEntry($query)
    {
        return $query->where('allow_manual_entry', true);
    }

    /** Scope: الحسابات الجذرية (بدون أب) */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    // ==============================
    // Helper Methods
    // ==============================

    /**
     * حساب الرصيد الحالي بالدولار (للقيود المُرحَّلة فقط)
     * الرصيد = مجموع المدين - مجموع الدائن
     */
    public function getCurrentBalanceUsd(): float
    {
        $debit  = $this->journalEntries()
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted'))
            ->sum('debit_usd');

        $credit = $this->journalEntries()
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted'))
            ->sum('credit_usd');

        return (float) ($debit - $credit);
    }

    /**
     * حساب الرصيد الحالي بالليرة السورية (للقيود المُرحَّلة فقط)
     */
    public function getCurrentBalanceSyp(): float
    {
        $debit  = $this->journalEntries()
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted'))
            ->sum('debit_syp');

        $credit = $this->journalEntries()
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted'))
            ->sum('credit_syp');

        return (float) ($debit - $credit);
    }

    /** هل الحساب حساب أصول؟ */
    public function isAsset(): bool
    {
        return $this->type === 'asset';
    }

    /** هل الحساب حساب خصوم؟ */
    public function isLiability(): bool
    {
        return $this->type === 'liability';
    }

    /** هل الحساب حساب إيرادات؟ */
    public function isRevenue(): bool
    {
        return $this->type === 'revenue';
    }

    /** هل الحساب حساب مصاريف؟ */
    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }
}
