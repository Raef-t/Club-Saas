<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Scopes\BranchScope;
use Modules\ClubManager\Models\Branch;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccJournal",
    title: "سند القيود اليومية ورأس القيد",
    description: "يمثل رأس السند المالي أو القيد المحاسبي المزدوج. قد يكون قيد تسوية عام (JV)، سند قبض نقدية (RV)، أو سند صرف نقدية (PV). يحتوي على التاريخ والوصف والفرع والحالة (مسودة أو مرحل).",
    properties: [
        new OA\Property(property: "id", type: "integer", description: "المعرف الفريد لسند القيد", example: 1),
        new OA\Property(property: "reference_number", type: "string", description: "الرقم المرجعي التلقائي للسند (مثال: JV-2026-0001)", example: "JV-2026-0001"),
        new OA\Property(property: "type", type: "string", enum: ["JV", "RV", "PV"], description: "نوع السند: JV (قيد عام)، RV (سند قبض وصندوق)، PV (سند صرف وصندوق)", example: "JV"),
        new OA\Property(property: "period_id", type: "integer", description: "معرف الفترة المالية والمحاسبية المفتوحة التي ينتمي إليها السند", example: 1),
        new OA\Property(property: "date", type: "string", format: "date", description: "التاريخ الفعلي لتأثير القيد محاسبياً", example: "2026-06-23"),
        new OA\Property(property: "description", type: "string", description: "الوصف والبيان العام لسبب إنشاء السند", example: "إثبات مصروف صيانة أجهزة اللياقة البدنية"),
        new OA\Property(property: "counterparty_id", type: "integer", description: "معرف الطرف الآخر المساهم أو المستلم في العملية (عميل، مورد، موظف)", nullable: true, example: 3),
        new OA\Property(property: "safe_id", type: "integer", description: "معرف الصندوق المالي المتأثر في حال كان السند سند قبض أو صرف نقدية", nullable: true, example: null),
        new OA\Property(property: "exchange_rate", type: "number", format: "float", description: "سعر الصرف المعتمد بين الدولار والليرة في تاريخ القيد", example: 15000.00),
        new OA\Property(property: "status", type: "string", enum: ["draft", "posted"], description: "حالة السند: draft (مسودة ولا تؤثر مالياً بالتقارير)، posted (مرحل ويؤثر فورياً على الأرصدة ودفتر الأستاذ والميزانية)", example: "draft"),
        new OA\Property(property: "posted_by", type: "integer", description: "معرف المستخدم الذي قام بترحيل السند والتصديق عليه مالياً", nullable: true, example: 3),
        new OA\Property(property: "posted_at", type: "string", format: "date-time", description: "تاريخ ووقت ترحيل القيد المزدوج", nullable: true, example: "2026-06-23T14:30:00Z"),
        new OA\Property(property: "reversed_journal_id", type: "integer", description: "في حال تم إلغاء القيد عبر قيد عاكس، يمثل هذا الحقل معرف القيد الجديد الذي تم إنشاؤه لإلغاء هذا القيد", nullable: true, example: null),
        new OA\Property(property: "source_type", type: "string", description: "نوع مصدر القيد المؤتمت (مثال: subscription_payment, payroll)", nullable: true, example: "subscription_payment"),
        new OA\Property(property: "source_id", type: "integer", description: "معرف السجل المصدر في جدوله الأصلي (مثال: معرف الدفعة Payment ID)", nullable: true, example: 120),
        new OA\Property(property: "notes", type: "string", description: "ملاحظات وتفاصيل إضافية إدارية حول العملية", nullable: true, example: "تمت الموافقة من قبل الإدارة الفنية"),
        new OA\Property(property: "branch_id", type: "integer", description: "معرف الفرع الجغرافي للنادي الرياضي التابع له القيد", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time", description: "تاريخ إنشاء السجل", example: "2026-06-23T10:00:00Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", description: "تاريخ آخر تحديث للسجل", example: "2026-06-23T14:30:00Z")
    ]
)]
class AccJournal extends Model
{
    use HasFactory;

    protected $table = 'acc_journals';

    protected $appends = ['is_reversal'];

    protected $fillable = [
        'reference_number',
        'type',
        'period_id',
        'date',
        'description',
        'counterparty_id',
        'safe_id',
        'exchange_rate',
        'status',
        'posted_by',
        'posted_at',
        'reversed_journal_id',
        'source_type',
        'source_id',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'date'      => 'date',
        'posted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    // ==============================
    // العلاقات
    // ==============================

    /** الفترة المحاسبية التي ينتمي إليها هذا القيد */
    public function period()
    {
        return $this->belongsTo(AccPeriod::class, 'period_id');
    }

    /** سطور القيد (بنود اليومية) */
    public function entries()
    {
        return $this->hasMany(AccJournalEntry::class, 'journal_id');
    }

    /** الطرف الآخر في المعاملة (عميل / مورد / موظف) */
    public function counterparty()
    {
        return $this->belongsTo(AccCounterparty::class, 'counterparty_id');
    }

    /** الصندوق المرتبط بهذا القيد */
    public function safe()
    {
        return $this->belongsTo(AccSafe::class, 'safe_id');
    }

    /** الفرع الجغرافي */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * القيد العاكس (Reversal Journal)
     * في حالة وجود قيد يعكس هذا القيد
     */
    public function reversedJournal()
    {
        return $this->belongsTo(AccJournal::class, 'reversed_journal_id');
    }

    /**
     * القيد الذي قام هذا السند بعكسه
     * في حال كان هذا السند سنداً عاكساً لقيد آخر
     */
    public function reversesJournal()
    {
        return $this->hasOne(AccJournal::class, 'reversed_journal_id');
    }

    // ==============================
    // Scopes
    // ==============================

    /** Scope: القيود المُرحَّلة فقط */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /** Scope: المسودات فقط */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /** Scope: تصفية حسب النوع (JV, RV, PV, SI, PI) */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /** Scope: البحث عن قيود مرتبطة بمصدر خارجي */
    public function scopeBySource($query, string $sourceType, int $sourceId)
    {
        return $query->where('source_type', $sourceType)->where('source_id', $sourceId);
    }

    // ==============================
    // Helper Methods
    // ==============================

    /** هل القيد مُرحَّل؟ */
    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    /** هل القيد في حالة مسودة؟ */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /** هل القيد معكوس؟ */
    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    // ==============================
    // Accessors (Computed Attributes)
    // ==============================

    /** إجمالي المدين بالدولار */
    public function getTotalDebitUsdAttribute(): float
    {
        return (float) $this->entries->sum('debit_usd');
    }

    /** إجمالي الدائن بالدولار */
    public function getTotalCreditUsdAttribute(): float
    {
        return (float) $this->entries->sum('credit_usd');
    }

    /** إجمالي المدين بالليرة السورية */
    public function getTotalDebitSypAttribute(): float
    {
        return (float) $this->entries->sum('debit_syp');
    }

    /** إجمالي الدائن بالليرة السورية */
    public function getTotalCreditSypAttribute(): float
    {
        return (float) $this->entries->sum('credit_syp');
    }

    /**
     * هل القيد متوازن بالدولار؟ (مجموع المدين = مجموع الدائن)
     * شرط أساسي للقيد المزدوج الصحيح
     */
    public function isBalancedUsd(): bool
    {
        return abs($this->total_debit_usd - $this->total_credit_usd) < 0.0001;
    }

    /**
     * هل القيد متوازن بالليرة؟
     */
    public function isBalancedSyp(): bool
    {
        return abs($this->total_debit_syp - $this->total_credit_syp) < 0.01;
    }

    /**
     * هل هذا السند عبارة عن قيد عاكس لسند آخر؟
     */
    public function getIsReversalAttribute(): bool
    {
        return $this->relationLoaded('reversesJournal')
            ? $this->reversesJournal !== null
            : \Modules\Accounting\Models\AccJournal::where('reversed_journal_id', $this->id)->exists();
    }
}
