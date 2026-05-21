<?php
namespace Modules\ClubManager\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
class Branch extends Model {
    use HasFactory, SoftDeletes, HasTranslations;
    protected $fillable = ['club_id', 'name', 'gender_restriction', 'type', 'address', 'phone', 'is_active'];
    public $translatable = ['name'];
    protected $casts = ['is_active' => 'boolean'];
    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
    public function facilities(): HasMany { return $this->hasMany(Facility::class); }
    public function lockers(): HasMany { return $this->hasMany(Locker::class); }
}