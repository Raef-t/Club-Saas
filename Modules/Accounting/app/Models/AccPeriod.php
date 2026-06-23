<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
