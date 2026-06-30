<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccPartner",
    title: "شريك النادي الرياضي",
    description: "يمثل شريكاً مساهماً في رأسمال المشروع أو النادي. يتم ربطه بحساب رأس المال المخصص وحساب جاري (المسحوبات الشخصية) لتوزيع الأرباح والخسائر وحركات السحب المباشر.",
    properties: [
        new OA\Property(property: "id", type: "integer", description: "المعرف الفريد للشريك المساهم", example: 1),
        new OA\Property(property: "name", type: "string", description: "اسم الشريك المساهم بالكامل", example: "محمد بن فهد الرميح"),
        new OA\Property(property: "capital_account_id", type: "integer", description: "معرف الحساب المخصص لرأس مال الشريك في حقوق الملكية", example: 12),
        new OA\Property(property: "drawings_account_id", type: "integer", description: "معرف الحساب المخصص للمسحوبات الشخصية للشريك", nullable: true, example: 13),
        new OA\Property(property: "profit_share_pct", type: "number", format: "float", description: "نسبة الشريك المئوية من الأرباح والخسائر الموزعة (0 - 100%)", example: 35.50),
        new OA\Property(property: "joined_at", type: "string", format: "date", description: "تاريخ انضمام وتأسيس الشراكة مع النادي", example: "2026-06-01"),
        new OA\Property(property: "is_active", type: "boolean", description: "حالة شراكة الشريك (نشطة أو مجمدة)", example: true),
        new OA\Property(property: "notes", type: "string", description: "تفاصيل عقد الشراكة أو شروط إضافية للمساهمة", nullable: true, example: "شراكة استثمارية بنسبة ثابتة مع حق الإدارة"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", description: "تاريخ إنشاء السجل", example: "2026-06-01T08:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", description: "تاريخ آخر تحديث للسجل", example: "2026-06-30T23:59:59Z")
    ]
)]
class AccPartner extends Model
{
    use HasFactory;

    protected $table = 'acc_partners';

    protected $fillable = [
        'name',
        'capital_account_id',
        'drawings_account_id',
        'profit_share_pct',
        'joined_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'joined_at'        => 'date',
        'is_active'        => 'boolean',
        'profit_share_pct' => 'float',
    ];

    // ==============================
    // العلاقات
    // ==============================

    /** حساب رأس المال الخاص بهذا الشريك */
    public function capitalAccount()
    {
        return $this->belongsTo(AccAccount::class, 'capital_account_id');
    }

    /** حساب المسحوبات الشخصية لهذا الشريك */
    public function drawingsAccount()
    {
        return $this->belongsTo(AccAccount::class, 'drawings_account_id');
    }

    // ==============================
    // Scopes
    // ==============================

    /** Scope: الشركاء النشطون فقط */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
