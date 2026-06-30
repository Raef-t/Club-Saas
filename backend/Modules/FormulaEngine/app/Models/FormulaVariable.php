<?php

namespace Modules\FormulaEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormulaVariable extends Model
{
    protected $fillable = [
        'formula_id',
        'variable_name',
        'source_type',
        'db_column',
        'computed_formula_key',
        'is_required',
        'default_value',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function formula(): BelongsTo
    {
        return $this->belongsTo(Formula::class);
    }

    public function isInput(): bool
    {
        return $this->source_type === 'input';
    }

    public function isMeasurement(): bool
    {
        return $this->source_type === 'measurement';
    }

    public function isComputed(): bool
    {
        return $this->source_type === 'computed';
    }
}
