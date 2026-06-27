<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccCounterparty",
    title: "الطرف الآخر في المعاملة المالية (عميل، مورد، موظف)",
    description: "يمثل الكيان الذي تتم معه المعاملات المالية كعميل (مشترك في النادي)، أو مورد خارجي للمعدات، أو موظف يتلقى سلف ورواتب. يمكن ربطه برقم مرجعي للموديلات الخارجية للمشروع.",
    properties: [
        new OA\Property(property: "id", type: "integer", description: "المعرف الفريد للطرف", example: 1),
        new OA\Property(property: "name", type: "string", description: "اسم الطرف بالكامل", example: "شركة الهدى للتوريدات الرياضية"),
        new OA\Property(property: "type", type: "string", enum: ["customer", "vendor", "employee", "other"], description: "تصنيف الطرف: عميل، مورد، موظف، أو تصنيف آخر", example: "vendor"),
        new OA\Property(property: "ar_account_id", type: "integer", description: "معرف حساب الذمم (المدينة للعملاء والدائنة للموردين) المرتبط بهذا الطرف في شجرة الحسابات", nullable: true, example: 15),
        new OA\Property(property: "country_code", type: "string", description: "رمز الدولة", nullable: true, example: "+963"),
        new OA\Property(property: "phone", type: "string", description: "رقم هاتف التواصل الخاص بالطرف", nullable: true, example: "0500000000"),
        new OA\Property(property: "email", type: "string", format: "email", description: "البريد الإلكتروني للطرف للاتصال أو إرسال كشوف الحسابات", nullable: true, example: "info@alhudasports.com"),
        new OA\Property(property: "reference_type", type: "string", description: "نوع الكيان المرجعي في قواعد البيانات الخارجية (مثال: Player, Staff, Vendor)", nullable: true, example: "Player"),
        new OA\Property(property: "reference_id", type: "integer", description: "معرف السجل المرتبط في جدول الكيان المرجعي الخارجي", nullable: true, example: 42),
        new OA\Property(property: "notes", type: "string", description: "ملاحظات إضافية أو تفاصيل التواصل والاتفاقيات", nullable: true, example: "مورد الأجهزة والمشايات الرياضية الخاص بالصالة الرئيسية"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", description: "تاريخ إنشاء السجل", example: "2026-06-01T08:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", description: "تاريخ آخر تحديث للسجل", example: "2026-06-30T23:59:59Z")
    ]
)]
class AccCounterparty extends Model
{
    use HasFactory;

    protected $table = 'acc_counterparties';

    protected $fillable = [
        'name',
        'type',
        'ar_account_id',
        'country_code',
        'phone',
        'email',
        'reference_type',
        'reference_id',
        'notes',
    ];

    // ==============================
    // العلاقات
    // ==============================

    /** حساب الذمم المدينة/الدائنة المرتبط بهذا الطرف */
    public function arAccount()
    {
        return $this->belongsTo(AccAccount::class, 'ar_account_id');
    }

    /** القيود المحاسبية المرتبطة بهذا الطرف */
    public function journals()
    {
        return $this->hasMany(AccJournal::class, 'counterparty_id');
    }

    // ==============================
    // Scopes
    // ==============================

    /**
     * Scope: البحث عن طرف مرتبط بمرجع خارجي (Polymorphic)
     * مثال: AccCounterparty::byReference('Student', 42)->first()
     */
    public function scopeByReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)->where('reference_id', $id);
    }

    /** Scope: العملاء فقط */
    public function scopeCustomers($query)
    {
        return $query->where('type', 'customer');
    }

    /** Scope: الموردون فقط */
    public function scopeVendors($query)
    {
        return $query->where('type', 'vendor');
    }

    /** Scope: الموظفون فقط */
    public function scopeEmployees($query)
    {
        return $query->where('type', 'employee');
    }
}
