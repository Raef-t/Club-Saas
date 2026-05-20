<?php

namespace Modules\BranchManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'gender_restriction',
        'address',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'name' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: A branch has many facilities.
     */
    public function facilities()
    {
        return $this->hasMany(Facility::class);
    }
}
