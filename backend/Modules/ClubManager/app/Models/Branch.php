<?php
namespace Modules\ClubManager\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;

class Branch extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['club_id', 'name', 'gender_restriction', 'type', 'address', 'country_code', 'phone', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
    public function facilities(): HasMany { return $this->hasMany(Facility::class); }
    public function lockers(): HasMany { return $this->hasMany(Locker::class); }
    public function shifts(): HasMany { return $this->hasMany(BranchShift::class); }
    public function settings() { return $this->hasOne(BranchSetting::class); }
    public function holidays(): HasMany { return $this->hasMany(BranchHoliday::class); }

    protected static function booted(): void
    {
        static::deleted(function ($branch) {
            if ($branch->isForceDeleting()) {
                return;
            }

            if ($branch->settings) {
                $branch->settings->delete();
            }

            $branch->holidays()->delete();
            $branch->facilities()->delete();
            $branch->lockers()->delete();
            $branch->shifts()->delete();

            if (class_exists(\Modules\MemberManager\Models\Member::class)) {
                \Modules\MemberManager\Models\Member::where('branch_id', $branch->id)->get()->each(function ($member) {
                    $member->delete();
                });
            }

            if (class_exists(\Modules\StaffManager\Models\Staff::class)) {
                \Modules\StaffManager\Models\Staff::whereHas('branches', function ($q) use ($branch) {
                    $q->where('branch_id', $branch->id);
                })->get()->each(function ($staff) {
                    $staff->delete();
                });
            }

            if (class_exists(\Modules\SubscriptionManager\Models\SubscriptionPlan::class)) {
                \Modules\SubscriptionManager\Models\SubscriptionPlan::where('branch_id', $branch->id)->get()->each(function ($plan) {
                    $plan->delete();
                });
            }

            if (class_exists(\Modules\Sports\Models\Activity::class)) {
                \Modules\Sports\Models\Activity::where('branch_id', $branch->id)->get()->each(function ($activity) {
                    $activity->delete();
                });
            }

            if (class_exists(\Modules\SubscriptionManager\Models\Invoice::class)) {
                \Modules\SubscriptionManager\Models\Invoice::where('branch_id', $branch->id)->get()->each(function ($invoice) {
                    $invoice->delete();
                });
            }
        });

        static::restored(function ($branch) {
            if ($branch->settings()->onlyTrashed()->exists()) {
                $branch->settings()->onlyTrashed()->restore();
            }

            $branch->holidays()->onlyTrashed()->restore();
            $branch->facilities()->onlyTrashed()->restore();
            $branch->lockers()->onlyTrashed()->restore();
            $branch->shifts()->onlyTrashed()->restore();

            if (class_exists(\Modules\MemberManager\Models\Member::class)) {
                \Modules\MemberManager\Models\Member::onlyTrashed()->where('branch_id', $branch->id)->get()->each(function ($member) {
                    $member->restore();
                });
            }

            if (class_exists(\Modules\StaffManager\Models\Staff::class)) {
                \Modules\StaffManager\Models\Staff::onlyTrashed()->whereHas('branches', function ($q) use ($branch) {
                    $q->where('branch_id', $branch->id);
                })->get()->each(function ($staff) {
                    $staff->restore();
                });
            }

            if (class_exists(\Modules\SubscriptionManager\Models\SubscriptionPlan::class)) {
                \Modules\SubscriptionManager\Models\SubscriptionPlan::onlyTrashed()->where('branch_id', $branch->id)->get()->each(function ($plan) {
                    $plan->restore();
                });
            }

            if (class_exists(\Modules\Sports\Models\Activity::class)) {
                \Modules\Sports\Models\Activity::onlyTrashed()->where('branch_id', $branch->id)->get()->each(function ($activity) {
                    $activity->restore();
                });
            }

            if (class_exists(\Modules\SubscriptionManager\Models\Invoice::class)) {
                \Modules\SubscriptionManager\Models\Invoice::onlyTrashed()->where('branch_id', $branch->id)->get()->each(function ($invoice) {
                    $invoice->restore();
                });
            }
        });
    }
}