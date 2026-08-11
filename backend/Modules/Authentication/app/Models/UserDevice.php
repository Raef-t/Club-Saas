<?php

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDevice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'fcm_token',
        'device_info',
    ];

    protected $casts = [
        'device_info' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
