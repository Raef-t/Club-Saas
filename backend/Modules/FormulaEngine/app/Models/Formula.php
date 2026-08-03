<?php

namespace Modules\FormulaEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;

class Formula extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    protected array $cascadeDeletes = ['variables'];
    protected $fillable = [
        'name',
        'key',
        'expression',
        'description',
        'category',
        'return_type',
        'unit',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function variables(): HasMany
    {
        return $this->hasMany(FormulaVariable::class);
    }
}
