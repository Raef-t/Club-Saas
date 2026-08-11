<?php

namespace Modules\NotificationManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'system_key',
        'subject',
        'body',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * معالجة نص القالب واستبدال المتغيرات بالقيم الممررة
     * 
     * @param array $data المصفوفة التي تحوي القيم (مثال: ['club_name' => 'تكنوجيم', ...])
     * @return string
     */
    public function parseBody(array $data): string
    {
        $parsedBody = $this->body;

        foreach ($data as $key => $value) {
            $parsedBody = str_replace('{' . $key . '}', $value, $parsedBody);
        }

        return $parsedBody;
    }
}
