<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'theme_colors',
        'language',
        'enabled_features',
        'bg_image_url',
    ];

    protected $casts = [
        'theme_colors' => 'array',
        'enabled_features' => 'array',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
