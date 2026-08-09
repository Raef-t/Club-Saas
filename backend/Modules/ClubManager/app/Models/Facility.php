<?php
namespace Modules\ClubManager\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;

class Facility extends Model {
    use HasFactory, SoftDeletes, CascadeSoftDeletes;

    protected array $cascadeDeletes = ['workingHours', 'sessionTemplates'];

    protected $fillable = ['branch_id', 'name', 'capacity', 'gender_restriction'];
    protected $casts = ['capacity' => 'integer'];
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function workingHours() { return $this->hasMany(FacilityWorkingHour::class); }
    public function sessionTemplates() { return $this->hasMany(\Modules\Sports\Models\SportSessionTemplate::class, 'facility_id'); }
}