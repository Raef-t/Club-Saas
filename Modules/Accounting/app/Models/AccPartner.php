<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
