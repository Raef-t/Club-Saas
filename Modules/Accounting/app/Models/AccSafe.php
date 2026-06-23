<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Scopes\BranchScope;
use Modules\ClubManager\Models\Branch;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccSafe",
    title: "الصندوق / الخزينة المالية",
    description: "يمثل صندوق الكاش الفعلي في النادي الرياضي لاستلام مدفوعات المشتركين أو صرف المصاريف. يتم ربطه بحساب محاسبي أصل لسهولة الترحيل.",
    properties: [
        new OA\Property(property: "id", type: "integer", description: "المعرف الفريد للصندوق المالي", example: 1),
        new OA\Property(property: "name", type: "string", description: "اسم الصندوق (مثال: صندوق استقبال فرع دبي)", example: "صندوق استقبال فرع دبي"),
        new OA\Property(property: "account_id", type: "integer", description: "معرف الحساب المحاسبي المرتبط بهذا الصندوق من شجرة الحسابات (أصل متداول)", example: 4),
        new OA\Property(property: "currency", type: "string", enum: ["USD", "SYP"], description: "العملة الوحيدة المحددة للصندوق لكافة حركاته (دولار أو ليرة سورية)", example: "USD"),
        new OA\Property(property: "responsible_user_id", type: "integer", description: "معرف المستخدم المسؤول عن عهدة الصندوق (الكاشير/موظف الاستقبال)", nullable: true, example: 5),
        new OA\Property(property: "is_active", type: "boolean", description: "حالة تفعيل الصندوق لاستقبال دفوعات المشتركين أو الصرف منه", example: true),
        new OA\Property(property: "notes", type: "string", description: "ملاحظات وتفاصيل إضافية حول عهدة الصندوق أو موقعه", nullable: true, example: "صندوق الفترة الصباحية لخدمة المشتركين"),
        new OA\Property(property: "branch_id", type: "integer", description: "معرف فرع النادي الذي ينتمي له هذا الصندوق", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time", description: "تاريخ إنشاء السجل", example: "2026-06-01T08:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", description: "تاريخ آخر تحديث للسجل", example: "2026-06-30T23:59:59Z")
    ]
)]
class AccSafe extends Model
{
    use HasFactory;

    protected $table = 'acc_safes';

    protected $fillable = [
        'name',
        'account_id',
        'currency',
        'responsible_user_id',
        'is_active',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    // ==============================
    // العلاقات
    // ==============================

    /** الحساب المحاسبي المرتبط بهذا الصندوق */
    public function account()
    {
        return $this->belongsTo(AccAccount::class, 'account_id');
    }

    /** القيود المحاسبية المرتبطة بهذا الصندوق */
    public function journals()
    {
        return $this->hasMany(AccJournal::class, 'safe_id');
    }

    /** تسويات الصندوق */
    public function reconciliations()
    {
        return $this->hasMany(AccReconciliation::class, 'safe_id');
    }

    /** الفرع الجغرافي */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // ==============================
    // Scopes
    // ==============================

    /** Scope: الصناديق النشطة فقط */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Scope: صناديق الدولار فقط */
    public function scopeUsd($query)
    {
        return $query->where('currency', 'USD');
    }

    /** Scope: صناديق الليرة السورية فقط */
    public function scopeSyp($query)
    {
        return $query->where('currency', 'SYP');
    }
}
