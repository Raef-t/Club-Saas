<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\StaffManager\Models\Staff;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccSalaryPayment",
    title: "سند صرف راتب للموظف / الكادر",
    description: "يمثل حركة صرف راتب أو مكافأة لأحد كوادر أو موظفي النادي الرياضي عبر صندوق محدد وقيد محاسبي مزدوج.",
    properties: [
        new OA\Property(property: "id", type: "integer", description: "المعرف الفريد لحركة صرف الراتب", example: 1),
        new OA\Property(property: "staff_id", type: "integer", description: "معرف الموظف/الكادر في النادي", example: 5),
        new OA\Property(property: "safe_id", type: "integer", description: "معرف الصندوق المالي الذي تم الصرف منه", example: 2),
        new OA\Property(property: "period_id", type: "integer", description: "معرف الفترة المالية والمحاسبية", example: 1),
        new OA\Property(property: "amount", type: "number", format: "float", description: "مبلغ الراتب المصروف", example: 500.00),
        new OA\Property(property: "currency", type: "string", description: "العملة (USD أو SYP)", example: "USD"),
        new OA\Property(property: "date", type: "string", format: "date", description: "تاريخ صرف الراتب", example: "2026-07-01"),
        new OA\Property(property: "notes", type: "string", description: "ملاحظات إضافية حول الراتب أو الدفعة", nullable: true, example: "راتب شهر حزيران"),
        new OA\Property(property: "journal_id", type: "integer", description: "معرف السند المحاسبي المولد تلقائياً", nullable: true, example: 12),
        new OA\Property(property: "created_at", type: "string", format: "date-time", description: "تاريخ الإنشاء", example: "2026-07-01T10:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", description: "تاريخ التحديث", example: "2026-07-01T10:00:00Z")
    ]
)]
class AccSalaryPayment extends Model
{
    use HasFactory;

    protected $table = 'acc_salary_payments';

    protected $fillable = [
        'staff_id',
        'safe_id',
        'period_id',
        'payslip_id',
        'amount',
        'currency',
        'date',
        'notes',
        'journal_id',
    ];

    protected $casts = [
        'date'   => 'date:Y-m-d',
        'amount' => 'float',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function safe()
    {
        return $this->belongsTo(AccSafe::class, 'safe_id');
    }

    public function period()
    {
        return $this->belongsTo(AccPeriod::class, 'period_id');
    }

    public function payslip()
    {
        return $this->belongsTo(\Modules\StaffManager\Models\Payslip::class, 'payslip_id');
    }

    public function journal()
    {
        return $this->belongsTo(AccJournal::class, 'journal_id');
    }
}
