<?php

namespace Modules\Accounting\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ClubManager\Models\Branch;

class AccBranchSetting extends Model
{
    use HasFactory;

    protected $table = 'acc_branch_settings';

    protected $fillable = [
        'branch_id',
        'default_safe_id',
        'cash_usd_account_code',
        'cash_syp_account_code',
        'revenue_account_code',
        'expense_account_code',
        'supported_currencies',
    ];

    protected $casts = [
        'supported_currencies' => 'array',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function defaultSafe()
    {
        return $this->belongsTo(AccSafe::class, 'default_safe_id');
    }
}
