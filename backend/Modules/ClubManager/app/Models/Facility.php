<?php
namespace Modules\ClubManager\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;

class Facility extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['branch_id', 'name', 'capacity', 'gender_restriction'];
    protected $casts = ['capacity' => 'integer'];
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function workingHours() { return $this->hasMany(FacilityWorkingHour::class); }

    protected static function booted(): void
    {
        static::deleted(function ($facility) {
            if ($facility->isForceDeleting()) {
                return;
            }

            $facility->workingHours()->delete();

            if (class_exists(BranchShift::class)) {
                BranchShift::where('facility_id', $facility->id)->delete();
            }

            if (class_exists(\Modules\Sports\Models\SportSessionTemplate::class)) {
                \Modules\Sports\Models\SportSessionTemplate::where('facility_id', $facility->id)->delete();
            }
        });

        static::restored(function ($facility) {
            $facility->workingHours()->onlyTrashed()->restore();

            if (class_exists(BranchShift::class)) {
                BranchShift::onlyTrashed()->where('facility_id', $facility->id)->restore();
            }

            if (class_exists(\Modules\Sports\Models\SportSessionTemplate::class)) {
                \Modules\Sports\Models\SportSessionTemplate::onlyTrashed()->where('facility_id', $facility->id)->restore();
            }
        });
    }
}