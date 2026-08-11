<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClubSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'club_id',
        'theme_colors',
        'language',
        'allowed_debt_limit',
        'grace_period_days',
        'allow_partial_payment',
        'enabled_features',
        'bg_image_url',
    ];

    protected $casts = [
        'theme_colors' => 'array',
        'enabled_features' => 'array',
        'allowed_debt_limit' => 'decimal:2',
        'grace_period_days' => 'integer',
        'allow_partial_payment' => 'boolean',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
