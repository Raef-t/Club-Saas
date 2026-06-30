<?php

namespace Modules\NotificationManager\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class NotificationTemplate extends Model
{
    use HasTranslations;

    protected $fillable = [
        'slug',
        'subject',
        'content',
        'channel',
        'is_active',
    ];

    public $translatable = ['subject', 'content'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
