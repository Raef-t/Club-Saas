<?php

namespace Modules\FormulaEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formula extends Model
{
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
