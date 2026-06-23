<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccAccount extends Model
{
    use HasFactory;

    protected $table = 'acc_accounts';

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'type',
        'currency',
        'parent_id',
        'is_active',
        'allow_manual_entry',
        'description',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'allow_manual_entry' => 'boolean',
    ];

    // ==============================
    // العلاقات
    // ==============================

    /** الحساب الأب (للحسابات الفرعية) */
    public function parent()
    {
        return $this->belongsTo(AccAccount::class, 'parent_id');
    }

    /** الحسابات الفرعية */
    public function children()
    {
        return $this->hasMany(AccAccount::class, 'parent_id');
    }

    /** جميع الحسابات الفرعية بشكل متكرر (Recursive) */
    public function allChildren()
    {
        return $this->hasMany(AccAccount::class, 'parent_id')->with('allChildren');
    }

    /** قيود اليومية المرتبطة بهذا الحساب */
    public function journalEntries()
    {
        return $this->hasMany(AccJournalEntry::class, 'account_id');
    }

    /** الصناديق المرتبطة بهذا الحساب */
    public function safes()
    {
        return $this->hasMany(AccSafe::class, 'account_id');
    }

    // ==============================
    // Scopes
    // ==============================

    /** Scope: الحسابات النشطة فقط */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Scope: تصفية حسب نوع الحساب */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /** Scope: الحسابات التي تسمح بالإدخال اليدوي */
    public function scopeManualEntry($query)
    {
        return $query->where('allow_manual_entry', true);
    }

    /** Scope: الحسابات الجذرية (بدون أب) */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    // ==============================
    // Helper Methods
    // ==============================

    /**
     * حساب الرصيد الحالي بالدولار (للقيود المُرحَّلة فقط)
     * الرصيد = مجموع المدين - مجموع الدائن
     */
    public function getCurrentBalanceUsd(): float
    {
        $debit  = $this->journalEntries()
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted'))
            ->sum('debit_usd');

        $credit = $this->journalEntries()
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted'))
            ->sum('credit_usd');

        return (float) ($debit - $credit);
    }

    /**
     * حساب الرصيد الحالي بالليرة السورية (للقيود المُرحَّلة فقط)
     */
    public function getCurrentBalanceSyp(): float
    {
        $debit  = $this->journalEntries()
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted'))
            ->sum('debit_syp');

        $credit = $this->journalEntries()
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted'))
            ->sum('credit_syp');

        return (float) ($debit - $credit);
    }

    /** هل الحساب حساب أصول؟ */
    public function isAsset(): bool
    {
        return $this->type === 'asset';
    }

    /** هل الحساب حساب خصوم؟ */
    public function isLiability(): bool
    {
        return $this->type === 'liability';
    }

    /** هل الحساب حساب إيرادات؟ */
    public function isRevenue(): bool
    {
        return $this->type === 'revenue';
    }

    /** هل الحساب حساب مصاريف؟ */
    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }
}
