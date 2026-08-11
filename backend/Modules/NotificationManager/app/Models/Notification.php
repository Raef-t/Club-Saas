<?php

namespace Modules\NotificationManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;

    protected array $cascadeDeletes = ['attachments', 'recipients'];

    protected $table = 'notifications';

    protected $fillable = [
        'title',
        'body',
        'sender_id',
        'sender_type',
        'target_snapshot',
    ];

    protected $casts = [
        'target_snapshot' => 'array',
    ];

    /**
     * العلاقة مع مرفقات الإشعار
     */
    public function attachments()
    {
        return $this->hasMany(NotificationAttachment::class);
    }

    /**
     * العلاقة مع مستلمي الإشعار
     */
    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    /**
     * المرسل (اختياري - المستخدم الذي أرسل الإشعار)
     */
    public function sender()
    {
        return $this->belongsTo(\Modules\Authentication\Models\User::class, 'sender_id');
    }
}
