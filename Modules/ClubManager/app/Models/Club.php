<?php
namespace Modules\ClubManager\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Club extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'logo_url', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function branches(): HasMany { return $this->hasMany(Branch::class); }
    public function settings() { return $this->hasOne(ClubSetting::class); }
}