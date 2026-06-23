<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
