<?php

namespace Modules\NotificationManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class NotificationAttachment extends Model
{
    use HasFactory;

    protected $table = 'notification_attachments';

    protected $fillable = [
        'notification_id',
        'file_name',
        'file_path',
        'mime_type',
        'size',
    ];

    protected $appends = [
        'file_url',
        'download_url',
        'formatted_size',
        'file_extension',
        'is_image',
        'is_document',
    ];

    /**
     * رابط الملف الكامل
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    /**
     * رابط التحميل
     */
    public function getDownloadUrlAttribute()
    {
        return $this->file_url;
    }

    /**
     * الحجم بصيغة مقروءة
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * امتداد الملف
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * هل الملف صورة؟
     */
    public function getIsImageAttribute()
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * هل الملف مستند؟
     */
    public function getIsDocumentAttribute()
    {
        $docMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        return in_array($this->mime_type, $docMimes);
    }

    /**
     * العلاقة مع الإشعار
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
