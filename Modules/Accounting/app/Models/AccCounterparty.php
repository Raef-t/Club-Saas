<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccCounterparty extends Model
{
    use HasFactory;

    protected $table = 'acc_counterparties';

    protected $fillable = [
        'name',
        'type',
        'ar_account_id',
        'phone',
        'email',
        'reference_type',
        'reference_id',
        'notes',
    ];

    // ==============================
    // العلاقات
    // ==============================

    /** حساب الذمم المدينة/الدائنة المرتبط بهذا الطرف */
    public function arAccount()
    {
        return $this->belongsTo(AccAccount::class, 'ar_account_id');
    }

    /** القيود المحاسبية المرتبطة بهذا الطرف */
    public function journals()
    {
        return $this->hasMany(AccJournal::class, 'counterparty_id');
    }

    // ==============================
    // Scopes
    // ==============================

    /**
     * Scope: البحث عن طرف مرتبط بمرجع خارجي (Polymorphic)
     * مثال: AccCounterparty::byReference('Student', 42)->first()
     */
    public function scopeByReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)->where('reference_id', $id);
    }

    /** Scope: العملاء فقط */
    public function scopeCustomers($query)
    {
        return $query->where('type', 'customer');
    }

    /** Scope: الموردون فقط */
    public function scopeVendors($query)
    {
        return $query->where('type', 'vendor');
    }

    /** Scope: الموظفون فقط */
    public function scopeEmployees($query)
    {
        return $query->where('type', 'employee');
    }
}
