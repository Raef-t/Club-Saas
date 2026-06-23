<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
