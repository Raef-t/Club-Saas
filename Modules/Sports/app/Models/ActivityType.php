<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class ActivityType extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'branch_id',
        'is_active',
    ];

    /**
     * The attributes that are translatable.
     */
    public $translatable = ['name'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the activities for the activity type.
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the branch that owns the activity type.
     */
    public function branch()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Branch::class);
    }
}
