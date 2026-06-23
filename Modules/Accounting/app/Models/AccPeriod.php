<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccPeriod",
    title: "الفترة المالية والمحاسبية",
    description: "تمثل فترة زمنية محاسبية (شهرية أو سنوية) يتم تسجيل الحركات والقيود المالية ضمنها ويتم إغلاقها أو قفلها لمنع التلاعب بالبيانات التاريخية.",
    properties: [
        new OA\Property(property: "id", type: "integer", description: "المعرف الفريد للفترة المالية", example: 1),
        new OA\Property(property: "name", type: "string", description: "اسم الفترة المالية (مثال: شهر حزيران 2026)", example: "شهر حزيران 2026"),
        new OA\Property(property: "start_date", type: "string", format: "date", description: "تاريخ بداية الفترة المالية", example: "2026-06-01"),
        new OA\Property(property: "end_date", type: "string", format: "date", description: "تاريخ نهاية الفترة المالية", example: "2026-06-30"),
        new OA\Property(property: "status", type: "string", enum: ["open", "closed", "locked"], description: "حالة الفترة المالية: open (مفتوحة لتسجيل القيود)، closed (مغلقة مؤقتاً)، locked (مقفلة نهائياً للتدقيق والميزانية ولا يمكن إعادة فتحها)", example: "open"),
        new OA\Property(property: "closed_by", type: "integer", description: "معرف المستخدم (المدير/المحاسب) الذي قام بإغلاق أو قفل الفترة", nullable: true, example: 3),
        new OA\Property(property: "closed_at", type: "string", format: "date-time", description: "تاريخ ووقت إغلاق/قفل الفترة", nullable: true, example: "2026-06-30T23:59:59Z"),
        new OA\Property(property: "notes", type: "string", description: "ملاحظات إضافية حول الفترة المالية أو سبب الإغلاق", nullable: true, example: "فترة الصيف وتحديثات الاشتراكات"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", description: "تاريخ إنشاء السجل", example: "2026-06-01T08:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", description: "تاريخ آخر تحديث للسجل", example: "2026-06-30T23:59:59Z")
    ]
)]
class AccPeriod extends Model
{
    use HasFactory;

    protected $table = 'acc_periods';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'closed_at'  => 'datetime',
    ];

    // ==============================
    // العلاقات
    // ==============================

    /** القيود المحاسبية المرتبطة بهذه الفترة */
    public function journals()
    {
        return $this->hasMany(AccJournal::class, 'period_id');
    }

    /** تسويات الصناديق لهذه الفترة */
    public function reconciliations()
    {
        return $this->hasMany(AccReconciliation::class, 'period_id');
    }

    // ==============================
    // Helper Methods
    // ==============================

    /** هل الفترة مفتوحة؟ */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /** هل الفترة مغلقة أو مقفلة؟ */
    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'locked']);
    }

    /** هل الفترة مقفلة نهائياً؟ */
    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    // ==============================
    // Scopes
    // ==============================

    /** Scope: الفترات المفتوحة فقط */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /** Scope: الفترات المغلقة */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /** Scope: الفترات المقفلة نهائياً */
    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }
}
