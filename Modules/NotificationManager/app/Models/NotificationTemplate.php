<?php

namespace Modules\NotificationManager\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToTenant;
use Spatie\Translatable\HasTranslations;

class NotificationTemplate extends Model
{
    use BelongsToTenant, HasTranslations;

    protected $fillable = [
        'tenant_id',
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
