<?php
namespace Modules\ClubManager\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;

class Branch extends Model {
    use HasFactory, SoftDeletes, CascadeSoftDeletes;

    protected array $cascadeDeletes = [
        'settings',
        'holidays',
        'shifts',
        'facilities',
        'lockers',
        'members',
        'attendances',
        'invoices',
        'subscriptionPlans',
        'activities',
        'offers',
        'gateDevices',
    ];

    protected $fillable = ['club_id', 'name', 'gender_restriction', 'type', 'address', 'country_code', 'phone', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
    public function facilities(): HasMany { return $this->hasMany(Facility::class); }
    public function lockers(): HasMany { return $this->hasMany(Locker::class); }
    public function shifts(): HasMany { return $this->hasMany(BranchShift::class); }
    public function settings() { return $this->hasOne(BranchSetting::class); }
    public function holidays(): HasMany { return $this->hasMany(BranchHoliday::class); }
    public function members(): HasMany { return $this->hasMany(\Modules\MemberManager\Models\Member::class, 'branch_id'); }
    public function attendances(): HasMany { return $this->hasMany(\Modules\AttendanceManager\Models\Attendance::class, 'branch_id'); }
    public function invoices(): HasMany { return $this->hasMany(\Modules\SubscriptionManager\Models\Invoice::class, 'branch_id'); }
    public function subscriptionPlans(): HasMany { return $this->hasMany(\Modules\SubscriptionManager\Models\SubscriptionPlan::class, 'branch_id'); }
    public function activities(): HasMany { return $this->hasMany(\Modules\Sports\Models\Activity::class, 'branch_id'); }
    public function offers(): HasMany { return $this->hasMany(\Modules\SubscriptionManager\Models\Offer::class, 'branch_id'); }
    public function gateDevices(): HasMany { return $this->hasMany(\Modules\AttendanceManager\Models\GateDevice::class, 'branch_id'); }
}