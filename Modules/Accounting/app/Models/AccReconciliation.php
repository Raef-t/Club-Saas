<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccReconciliation",
    title: "مطابقة وتسوية الصندوق المالي",
    description: "يمثل سجل تسوية الصندوق المالي والمطابقة بين رصيد النظام الدفتري (system_balance) والرصيد الفعلي الموجود بالخزينة (physical_balance). يوضح الفروقات وحالة العجز أو الفائض المكتشف.",
    properties: [
        new OA\Property(property: "id", type: "integer", description: "المعرف الفريد لسجل التسوية والمطابقة", example: 1),
        new OA\Property(property: "safe_id", type: "integer", description: "معرف الصندوق المالي الذي تمت مطابقته وتسويته", example: 2),
        new OA\Property(property: "period_id", type: "integer", description: "معرف الفترة المحاسبية الجارية أثناء عملية التسوية", example: 1),
        new OA\Property(property: "system_balance_usd", type: "number", format: "float", description: "الرصيد الدفتري المسجل في النظام بالدولار الأمريكي قبل المطابقة", example: 1250.00),
        new OA\Property(property: "physical_balance_usd", type: "number", format: "float", description: "الرصيد الفعلي الفعلي الموجود بالصندوق يدوياً بالدولار الأمريكي", example: 1245.00),
        new OA\Property(property: "system_balance_syp", type: "number", format: "float", description: "الرصيد الدفتري المسجل في النظام بالليرة السورية قبل المطابقة", example: 15000000.00),
        new OA\Property(property: "physical_balance_syp", type: "number", format: "float", description: "الرصيد الفعلي الموجود بالصندوق يدوياً بالليرة السورية", example: 15000000.00),
        new OA\Property(property: "reconciled_by", type: "integer", description: "معرف المستخدم (المدقق/أمين الصندوق) الذي قام بعملية الجرد الفعلي والتسوية", example: 3),
        new OA\Property(property: "reconciled_at", type: "string", format: "date-time", description: "تاريخ ووقت إجراء المطابقة والتسوية الفعلي", example: "2026-06-23T15:00:00Z"),
        new OA\Property(property: "notes", type: "string", description: "شرح لوجود فروقات أو عجز أو فائض وكيفية معالجته إدارياً", nullable: true, example: "عجز بسيط بقيمة 5 دولار تم تحميله لأمين الصندوق أو تسويته كمصروف فروقات"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", description: "تاريخ إنشاء السجل", example: "2026-06-23T15:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", description: "تاريخ آخر تحديث للسجل", example: "2026-06-23T15:00:00Z")
    ]
)]
class AccReconciliation extends Model
{
    use HasFactory;

    protected $table = 'acc_reconciliations';

    protected $fillable = [
        'safe_id',
        'period_id',
        'system_balance_usd',
        'physical_balance_usd',
        'system_balance_syp',
        'physical_balance_syp',
        'reconciled_by',
        'reconciled_at',
        'notes',
    ];

    protected $casts = [
        'reconciled_at'        => 'datetime',
        'system_balance_usd'   => 'float',
        'physical_balance_usd' => 'float',
        'system_balance_syp'   => 'float',
        'physical_balance_syp' => 'float',
    ];

    // ==============================
    // العلاقات
    // ==============================

    /** الصندوق المُتحقَّق منه */
    public function safe()
    {
        return $this->belongsTo(AccSafe::class, 'safe_id');
    }

    /** الفترة المحاسبية التي تمت فيها التسوية */
    public function period()
    {
        return $this->belongsTo(AccPeriod::class, 'period_id');
    }

    // ==============================
    // Accessors
    // ==============================

    /**
     * الفرق بالدولار (محسوب من قاعدة البيانات كـ storedAs)
     * لكن هذا accessor يوفر الوصول من PHP أيضاً
     */
    public function getDifferenceUsdAttribute(): float
    {
        return (float) ($this->physical_balance_usd - $this->system_balance_usd);
    }

    /**
     * الفرق بالليرة السورية
     */
    public function getDifferenceSypAttribute(): float
    {
        return (float) ($this->physical_balance_syp - $this->system_balance_syp);
    }

    /**
     * هل التسوية متطابقة (لا يوجد فرق) بالدولار؟
     */
    public function isBalancedUsd(): bool
    {
        return abs($this->getDifferenceUsdAttribute()) < 0.0001;
    }

    /**
     * هل التسوية متطابقة (لا يوجد فرق) بالليرة؟
     */
    public function isBalancedSyp(): bool
    {
        return abs($this->getDifferenceSypAttribute()) < 0.01;
    }
}
