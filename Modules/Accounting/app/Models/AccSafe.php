<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Scopes\BranchScope;
use Modules\ClubManager\Models\Branch;

class AccSafe extends Model
{
    use HasFactory;

    protected $table = 'acc_safes';

    protected $fillable = [
        'name',
        'account_id',
        'currency',
        'responsible_user_id',
        'is_active',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    // ==============================
    // العلاقات
    // ==============================

    /** الحساب المحاسبي المرتبط بهذا الصندوق */
    public function account()
    {
        return $this->belongsTo(AccAccount::class, 'account_id');
    }

    /** القيود المحاسبية المرتبطة بهذا الصندوق */
    public function journals()
    {
        return $this->hasMany(AccJournal::class, 'safe_id');
    }

    /** تسويات الصندوق */
    public function reconciliations()
    {
        return $this->hasMany(AccReconciliation::class, 'safe_id');
    }

    /** الفرع الجغرافي */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // ==============================
    // Scopes
    // ==============================

    /** Scope: الصناديق النشطة فقط */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Scope: صناديق الدولار فقط */
    public function scopeUsd($query)
    {
        return $query->where('currency', 'USD');
    }

    /** Scope: صناديق الليرة السورية فقط */
    public function scopeSyp($query)
    {
        return $query->where('currency', 'SYP');
    }
}
