<?php
namespace Modules\ClubManager\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Club extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'logo_url', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function branches(): HasMany { return $this->hasMany(Branch::class); }
    public function settings() { return $this->hasOne(ClubSetting::class); }

    protected static function booted(): void
    {
        static::deleted(function ($club) {
            if ($club->isForceDeleting()) {
                return;
            }

            if ($club->settings) {
                $club->settings->delete();
            }

            $club->branches()->get()->each(function ($branch) {
                $branch->delete();
            });
        });

        static::restored(function ($club) {
            if ($club->settings()->onlyTrashed()->exists()) {
                $club->settings()->onlyTrashed()->restore();
            }

            $club->branches()->onlyTrashed()->get()->each(function ($branch) {
                $branch->restore();
            });
        });
    }
}