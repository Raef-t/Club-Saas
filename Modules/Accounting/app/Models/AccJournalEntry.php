<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccJournalEntry",
    title: "سطر حركة الحساب في القيد (القيد المزدوج)",
    description: "يمثل سطراً تفصيلياً (حركة) ضمن قيد اليومية المزدوج. يجب أن ترتبط كل حركة بحساب مالي فرعي محدد، وتحدد قيمتها إما مدينة (Debit) أو دائنة (Credit) بالدولار الأمريكي و/أو الليرة السورية.",
    properties: [
        new OA\Property(property: "id", type: "integer", description: "المعرف الفريد لسطر حركة القيد المالي", example: 1),
        new OA\Property(property: "journal_id", type: "integer", description: "معرف سند القيد اليومي التابع له هذا السطر", example: 12),
        new OA\Property(property: "account_id", type: "integer", description: "معرف الحساب المالي من دليل الحسابات المتأثر بهذه الحركة", example: 8),
        new OA\Property(property: "debit_usd", type: "number", format: "float", description: "القيمة المدينة بالدولار الأمريكي (يجب أن تكون 0 في حال كان السطر دائناً)", example: 150.00),
        new OA\Property(property: "credit_usd", type: "number", format: "float", description: "القيمة الدائنة بالدولار الأمريكي (يجب أن تكون 0 في حال كان السطر مديناً)", example: 0.00),
        new OA\Property(property: "debit_syp", type: "number", format: "float", description: "القيمة المدينة بالليرة السورية", example: 2250000.00),
        new OA\Property(property: "credit_syp", type: "number", format: "float", description: "القيمة الدائنة بالليرة السورية", example: 0.00),
        new OA\Property(property: "memo", type: "string", description: "شرح وتفصيل مخصص لسبب حركة هذا الحساب بالذات", nullable: true, example: "شراء وتوريد 10 كرات لياقة بدنية للفرع"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", description: "تاريخ إنشاء السجل", example: "2026-06-23T10:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", description: "تاريخ آخر تحديث للسجل", example: "2026-06-23T10:00:00Z")
    ]
)]
class AccJournalEntry extends Model
{
    use HasFactory;

    protected $table = 'acc_journal_entries';

    protected $fillable = [
        'journal_id',
        'account_id',
        'debit_usd',
        'credit_usd',
        'debit_syp',
        'credit_syp',
        'memo',
    ];

    protected $casts = [
        'debit_usd'  => 'float',
        'credit_usd' => 'float',
        'debit_syp'  => 'float',
        'credit_syp' => 'float',
    ];

    // ==============================
    // العلاقات
    // ==============================

    /** القيد (السند) الذي ينتمي إليه هذا السطر */
    public function journal()
    {
        return $this->belongsTo(AccJournal::class, 'journal_id');
    }

    /** الحساب المحاسبي المُدان أو الدائن */
    public function account()
    {
        return $this->belongsTo(AccAccount::class, 'account_id');
    }

    // ==============================
    // Accessors
    // ==============================

    /**
     * صافي الحركة بالدولار لهذا السطر
     * موجب = مدين، سالب = دائن
     */
    public function getNetUsdAttribute(): float
    {
        return (float) ($this->debit_usd - $this->credit_usd);
    }

    /**
     * صافي الحركة بالليرة السورية لهذا السطر
     */
    public function getNetSypAttribute(): float
    {
        return (float) ($this->debit_syp - $this->credit_syp);
    }
}
