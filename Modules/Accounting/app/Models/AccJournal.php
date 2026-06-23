<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Scopes\BranchScope;
use Modules\ClubManager\Models\Branch;

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
