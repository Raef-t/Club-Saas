<?php

namespace Modules\NotificationManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationRecipient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notification_recipients';

    protected $fillable = [
        'notification_id',
        'user_id',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
    ];

    /**
     * العلاقة مع الإشعار
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * العلاقة مع المستخدم
     */
    public function user()
    {
        return $this->belongsTo(\Modules\Authentication\Models\User::class);
    }
}
